# Client Extensions

AtpClient provides an extension system that allows you to add custom functionality. You can register domain clients (like `$client->myDomain`) or request clients on existing domains (like `$client->bsky->myFeature`).

## Quick Reference

### Available Methods

| Method | Description |
|--------|-------------|
| `AtpClient::extend($name, $callback)` | Register a domain client extension |
| `AtpClient::extendDomain($domain, $name, $callback)` | Register a request client on an existing domain |
| `AtpClient::hasExtension($name)` | Check if a domain extension is registered |
| `AtpClient::hasDomainExtension($domain, $name)` | Check if a request client extension is registered |
| `AtpClient::flushExtensions()` | Clear all extensions (useful for testing) |

### Extension Types

| Type | Access Pattern | Use Case |
|------|----------------|----------|
| Domain Client | `$client->myDomain` | Group related functionality under a namespace |
| Request Client | `$client->bsky->myFeature` | Add methods to an existing domain |

### Generator Commands

Quickly scaffold extension classes using artisan commands:

```bash
# Create a domain client extension
php artisan make:atp-client AnalyticsClient

# Create a request client extension for an existing domain
php artisan make:atp-request MetricsClient --domain=bsky
```

The generated files are placed in configurable directories. You can customize these paths in `config/client.php`:

```php
'generators' => [
    'client_path' => 'app/Services/Clients',
    'request_path' => 'app/Services/Clients/Requests',
],
```

## Understanding Extensions

Extensions follow a lazy-loading pattern. When you register an extension, the callback is stored but not executed. The extension is only instantiated when first accessed:

```php
// Registration - callback stored, not executed
AtpClient::extend('analytics', fn($atp) => new AnalyticsClient($atp));

// First access - callback executed, instance cached
$client->analytics->trackEvent('login');

// Subsequent access - cached instance returned
$client->analytics->trackEvent('post_created');
```

This ensures extensions don't add overhead unless they're actually used.

## Creating a Domain Client

A domain client adds a new namespace to AtpClient, accessible as a property.

### Step 1: Create Your Client Class

```php
<?php

namespace App\Atp;

use SocialDept\AtpClient\AtpClient;

class AnalyticsClient
{
    protected AtpClient $atp;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
    }

    public function trackEvent(string $event, array $properties = []): void
    {
        // Your analytics logic here
        // You have full access to the authenticated client via $this->atp
    }

    public function getEngagementStats(string $actor): array
    {
        $profile = $this->atp->bsky->actor->getProfile($actor);

        return [
            'followers' => $profile->followersCount,
            'following' => $profile->followsCount,
            'posts' => $profile->postsCount,
        ];
    }
}
```

### Step 2: Register the Extension

In your `AppServiceProvider`:

```php
<?php

namespace App\Providers;

use App\Atp\AnalyticsClient;
use Illuminate\Support\ServiceProvider;
use SocialDept\AtpClient\AtpClient;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        AtpClient::extend('analytics', fn(AtpClient $atp) => new AnalyticsClient($atp));
    }
}
```

### Step 3: Use Your Extension

```php
use SocialDept\AtpClient\Facades\Atp;

$client = Atp::as('user.bsky.social');

$client->analytics->trackEvent('page_view', ['page' => '/feed']);

$stats = $client->analytics->getEngagementStats('someone.bsky.social');
```

## Creating a Request Client

A request client extends an existing domain (like `bsky`, `atproto`, `chat`, or `ozone`). This is useful when you want to add methods that logically belong alongside the built-in functionality.

### Step 1: Create Your Request Client Class

Extend the base `Request` class to get access to the parent AtpClient:

```php
<?php

namespace App\Atp;

use SocialDept\AtpClient\Client\Requests\Request;

class BskyMetricsClient extends Request
{
    public function getPostEngagement(string $uri): array
    {
        $thread = $this->atp->bsky->feed->getPostThread($uri);
        $post = $thread->thread['post'] ?? null;

        if (! $post) {
            return [];
        }

        return [
            'likes' => $post['likeCount'] ?? 0,
            'reposts' => $post['repostCount'] ?? 0,
            'replies' => $post['replyCount'] ?? 0,
            'quotes' => $post['quoteCount'] ?? 0,
        ];
    }

    public function getAuthorMetrics(string $actor): array
    {
        $feed = $this->atp->bsky->feed->getAuthorFeed($actor, limit: 100);
        $posts = $feed->feed;

        $totalLikes = 0;
        $totalReposts = 0;

        foreach ($posts as $item) {
            $totalLikes += $item['post']['likeCount'] ?? 0;
            $totalReposts += $item['post']['repostCount'] ?? 0;
        }

        return [
            'posts_analyzed' => count($posts),
            'total_likes' => $totalLikes,
            'total_reposts' => $totalReposts,
            'avg_likes' => count($posts) > 0 ? $totalLikes / count($posts) : 0,
        ];
    }
}
```

### Step 2: Register the Extension

```php
use App\Atp\BskyMetricsClient;
use SocialDept\AtpClient\AtpClient;

public function boot(): void
{
    AtpClient::extendDomain('bsky', 'metrics', fn($bsky) => new BskyMetricsClient($bsky));
}
```

The callback receives the domain client instance (`BskyClient` in this case), which is passed to your request client's constructor.

### Step 3: Use Your Extension

```php
$client = Atp::as('user.bsky.social');

$engagement = $client->bsky->metrics->getPostEngagement('at://did:plc:.../app.bsky.feed.post/...');

$authorMetrics = $client->bsky->metrics->getAuthorMetrics('someone.bsky.social');
```

## Public vs Authenticated Mode

The `AtpClient` class works in both public and authenticated modes. Both `Atp::public()` and `Atp::as()` return the same `AtpClient` class:

```php
// Public mode - no authentication
$publicClient = Atp::public('https://public.api.bsky.app');
$publicClient->bsky->actor->getProfile('someone.bsky.social');

// Authenticated mode - with session
$authClient = Atp::as('did:plc:xxx');
$authClient->bsky->actor->getProfile('someone.bsky.social');
```

Extensions registered on `AtpClient` work in both modes. The underlying HTTP layer automatically handles authentication based on whether a session is present.

## Registering Multiple Extensions

You can register multiple extensions in your service provider:

```php
public function boot(): void
{
    // Domain clients
    AtpClient::extend('analytics', fn($atp) => new AnalyticsClient($atp));
    AtpClient::extend('moderation', fn($atp) => new ModerationClient($atp));

    // Request clients
    AtpClient::extendDomain('bsky', 'metrics', fn($bsky) => new BskyMetricsClient($bsky));
    AtpClient::extendDomain('bsky', 'lists', fn($bsky) => new BskyListsClient($bsky));
    AtpClient::extendDomain('atproto', 'backup', fn($atproto) => new RepoBackupClient($atproto));
}
```

## Conditional Registration

Register extensions conditionally based on environment or configuration:

```php
public function boot(): void
{
    if (config('services.analytics.enabled')) {
        AtpClient::extend('analytics', fn($atp) => new AnalyticsClient($atp));
    }

    if (app()->environment('local')) {
        AtpClient::extend('debug', fn($atp) => new DebugClient($atp));
    }
}
```

## Testing Extensions

### Test Isolation

Use `flushExtensions()` to clear registered extensions between tests:

```php
use SocialDept\AtpClient\AtpClient;
use PHPUnit\Framework\TestCase;

class MyExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AtpClient::flushExtensions();
    }

    protected function tearDown(): void
    {
        AtpClient::flushExtensions();
        parent::tearDown();
    }

    public function test_extension_is_registered(): void
    {
        AtpClient::extend('test', fn($atp) => new TestClient($atp));

        $this->assertTrue(AtpClient::hasExtension('test'));
    }
}
```

### Checking Registration

Use the static methods to verify extensions are registered:

```php
// Check domain extension
if (AtpClient::hasExtension('analytics')) {
    $client->analytics->trackEvent('test');
}

// Check request client extension
if (AtpClient::hasDomainExtension('bsky', 'metrics')) {
    $metrics = $client->bsky->metrics->getAuthorMetrics($actor);
}
```

## Advanced Patterns

### Accessing the HTTP Client

Domain client extensions can access the underlying HTTP client for custom API calls:

```php
class CustomApiClient
{
    protected AtpClient $atp;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
    }

    public function customEndpoint(array $params): array
    {
        // Access the authenticated HTTP client
        $response = $this->atp->client->get('com.example.customEndpoint', $params);

        return $response->json();
    }

    public function customProcedure(array $data): array
    {
        $response = $this->atp->client->post('com.example.customProcedure', $data);

        return $response->json();
    }
}
```

### Using Typed Responses

Return typed response objects for better IDE support:

```php
use SocialDept\AtpClient\Data\Responses\Response;

class MetricsResponse extends Response
{
    public function __construct(
        public readonly int $likes,
        public readonly int $reposts,
        public readonly int $replies,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            likes: $data['likes'] ?? 0,
            reposts: $data['reposts'] ?? 0,
            replies: $data['replies'] ?? 0,
        );
    }
}

class BskyMetricsClient extends Request
{
    public function getPostMetrics(string $uri): MetricsResponse
    {
        $thread = $this->atp->bsky->feed->getPostThread($uri);
        $post = $thread->thread['post'] ?? [];

        return MetricsResponse::fromArray([
            'likes' => $post['likeCount'] ?? 0,
            'reposts' => $post['repostCount'] ?? 0,
            'replies' => $post['replyCount'] ?? 0,
        ]);
    }
}
```

### Composing Multiple Clients

Extensions can use other extensions or built-in clients:

```php
class DashboardClient
{
    protected AtpClient $atp;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
    }

    public function getOverview(string $actor): array
    {
        // Use built-in clients
        $profile = $this->atp->bsky->actor->getProfile($actor);
        $feed = $this->atp->bsky->feed->getAuthorFeed($actor, limit: 10);

        // Use other extensions (if registered)
        $metrics = AtpClient::hasDomainExtension('bsky', 'metrics')
            ? $this->atp->bsky->metrics->getAuthorMetrics($actor)
            : null;

        return [
            'profile' => $profile,
            'recent_posts' => $feed->feed,
            'metrics' => $metrics,
        ];
    }
}
```

### Documenting Scope Requirements

Use the `#[RequiresScope]` attribute to document which OAuth scopes your extension methods require. This helps with documentation and enables scope checking in authenticated mode:

```php
use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;

class BskyMetricsClient extends Request
{
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getTimeline')]
    public function getTimelineMetrics(): array
    {
        $timeline = $this->atp->bsky->feed->getTimeline();
        // Process and return metrics...
    }

    // Methods without #[RequiresScope] work in both public and authenticated modes
    public function getPublicPostMetrics(string $uri): array
    {
        $thread = $this->atp->bsky->feed->getPostThread($uri);
        // Process and return metrics...
    }
}
```

Methods with `#[RequiresScope]` indicate they require authentication, while methods without it can work in public mode. See [scopes.md](scopes.md) for full documentation on scope handling.

## Available Domains

You can extend these built-in domains with `extendDomain()`:

| Domain | Description |
|--------|-------------|
| `bsky` | Bluesky-specific operations (app.bsky.*) |
| `atproto` | AT Protocol core operations (com.atproto.*) |
| `chat` | Direct messaging operations (chat.bsky.*) |
| `ozone` | Moderation tools (tools.ozone.*) |
