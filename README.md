[![Resolver Header](./header.png)](https://github.com/socialdept/atp-signals)

<h3 align="center">
    Type-safe AT Protocol HTTP client with OAuth 2.0 support for Laravel.
</h3>

<p align="center">
    <br>
    <a href="https://packagist.org/packages/socialdept/atp-client" title="Latest Version on Packagist"><img src="https://img.shields.io/packagist/v/socialdept/atp-client.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/socialdept/atp-client" title="Total Downloads"><img src="https://img.shields.io/packagist/dt/socialdept/atp-client.svg?style=flat-square"></a>
    <a href="https://github.com/socialdept/atp-client/actions/workflows/tests.yml" title="GitHub Tests Action Status"><img src="https://img.shields.io/github/actions/workflow/status/socialdept/atp-client/tests.yml?branch=main&label=tests&style=flat-square"></a>
    <a href="LICENSE" title="Software License"><img src="https://img.shields.io/github/license/socialdept/atp-client?style=flat-square"></a>
</p>

---

## What is AtpClient?

**AtpClient** is a Laravel package for interacting with Bluesky and the AT Protocol. It provides a fluent, type-safe API for authentication, posting, profiles, follows, likes, and feeds. Supports both OAuth 2.0 (with PKCE, PAR, and DPoP) and app passwords.

Think of it as Laravel's HTTP client, but for the decentralized social web.

## Why use AtpClient?

- **Laravel-style code** - Familiar patterns you already know
- **OAuth 2.0 support** - Full PKCE, PAR, and DPoP implementation
- **App password support** - Simple authentication for scripts and bots
- **Automatic token refresh** - Sessions stay alive without manual intervention
- **Type-safe API** - Method chaining with IDE autocompletion
- **Rich text builder** - Fluent API for mentions, links, and hashtags
- **Full Bluesky coverage** - Posts, profiles, follows, likes, and feeds
- **AT Protocol operations** - Low-level repository access when needed

## Quick Example

```php
use SocialDept\AtpClient\Facades\Atp;

// Login with app password
$client = Atp::login('yourhandle.bsky.social', 'your-app-password');

// Create a post
$post = $client->bsky->post->create('Hello from Laravel!');

// Get your timeline
$timeline = $client->bsky->feed->getTimeline(limit: 50);
```

## Installation

```bash
composer require socialdept/atp-client
```

Optionally publish the configuration:

```bash
php artisan vendor:publish --tag=atp-client-config
```

## Getting Started

Once installed, you're three steps away from using the AT Protocol:

### 1. Choose Your Authentication Method

**App Password** (recommended for bots/scripts):
```php
$client = Atp::login('yourhandle.bsky.social', 'your-app-password');
```

**OAuth 2.0** (recommended for user-facing apps):
```php
$auth = Atp::oauth()->authorize('user@bsky.social');
return redirect($auth->url);
```

### 2. Make API Calls

```php
// Create posts
$client->bsky->post->create('Hello world!');

// Get profiles
$client->bsky->actor->getProfile('someone.bsky.social');

// Browse feeds
$client->bsky->feed->getTimeline();
```

### 3. Store Credentials (OAuth only)

Implement the `CredentialProvider` interface to persist tokens between requests.

## What can you build?

- **Bluesky integrations** - Connect your app to the AT Protocol
- **Social media management** - Post and manage content programmatically
- **Automated posting** - Schedule and automate content delivery
- **Analytics dashboards** - Track engagement and activity
- **Moderation tools** - Build bots for community moderation
- **Cross-platform syndication** - Mirror content across networks

## Authentication

### App Password Flow

The simplest way to authenticate. Generate an app password in your Bluesky settings.

```php
use SocialDept\AtpClient\Facades\Atp;

$client = Atp::login('yourhandle.bsky.social', 'your-app-password');

// Client is now authenticated and ready to use
$profile = $client->bsky->actor->getProfile('yourhandle.bsky.social');
```

### OAuth 2.0 Flow

For user-facing applications where users authenticate with their own accounts.

**Step 1: Initiate authorization**
```php
use SocialDept\AtpClient\Facades\Atp;

public function redirect()
{
    $auth = Atp::oauth()->authorize('user@bsky.social');

    // Store auth request in session for callback
    session(['atp_auth' => $auth]);

    return redirect($auth->url);
}
```

**Step 2: Handle callback**
```php
public function callback(Request $request)
{
    $auth = session('atp_auth');

    $token = Atp::oauth()->callback(
        code: $request->get('code'),
        state: $request->get('state'),
        request: $auth
    );

    // Store credentials using your CredentialProvider
    // $token contains: accessJwt, refreshJwt, did, handle, expiresAt
}
```

**Step 3: Use stored credentials**
```php
// After storing credentials, use them with Atp::as()
$client = Atp::as('user@bsky.social');
```

### Token Refresh

Sessions automatically refresh when tokens are about to expire (default: 5 minutes before expiration). Listen to events if you need to persist refreshed tokens:

```php
use SocialDept\AtpClient\Events\OAuthTokenRefreshed;

Event::listen(OAuthTokenRefreshed::class, function ($event) {
    // $event->did - the user's DID (e.g., did:plc:abc123...)
    // $event->token - the new AccessToken
    // Update your credential storage here
});
```

## Working with Posts

### Create a Simple Post

```php
$post = $client->bsky->post->create('Hello, Bluesky!');

// Returns StrongRef with uri and cid
echo $post->uri;  // at://did:plc:.../app.bsky.feed.post/...
echo $post->cid;  // bafyre...
```

### Rich Text with Mentions, Links, and Hashtags

Use the `TextBuilder` for posts with rich text formatting:

```php
use SocialDept\AtpClient\RichText\TextBuilder;

$content = TextBuilder::make()
    ->text('Check out ')
    ->mention('someone.bsky.social')
    ->text(' and visit ')
    ->link('our website', 'https://example.com')
    ->text(' ')
    ->tag('Laravel')
    ->toArray();

$post = $client->bsky->post->create($content);
```

Or use auto-detection on plain text:

```php
// Facets are automatically detected
$post = $client->bsky->post->create(
    'Hello @someone.bsky.social! Check out https://example.com #Bluesky'
);
```

### Reply to a Post

```php
$parent = new StrongRef(uri: 'at://...', cid: 'bafyre...');
$root = $parent; // Same as parent for direct replies

$reply = $client->bsky->post->reply(
    parent: $parent,
    root: $root,
    content: 'This is a reply!'
);
```

### Quote Post

```php
$quotedPost = new StrongRef(uri: 'at://...', cid: 'bafyre...');

$quote = $client->bsky->post->quote(
    quotedPost: $quotedPost,
    content: 'Interesting take!'
);
```

### Post with Images

```php
// Upload from a Laravel request
$blob = $client->atproto->repo->uploadBlob($request->file('image'));

// Or from a file path
$blob = $client->atproto->repo->uploadBlob(new SplFileInfo('/path/to/image.jpg'));

// Or from raw binary data (mimeType required)
$blob = $client->atproto->repo->uploadBlob(
    file: file_get_contents('/path/to/image.jpg'),
    mimeType: 'image/jpeg'
);

$post = $client->bsky->post->withImages(
    content: 'Check out this photo!',
    images: [
        [
            'image' => $blob->json('blob'),
            'alt' => 'Description of the image',
        ],
    ]
);
```

### Post with External Link Card

```php
$post = $client->bsky->post->withLink(
    content: 'Great article about Laravel',
    uri: 'https://example.com/article',
    title: 'Article Title',
    description: 'A brief description of the article...'
);
```

### Delete a Post

```php
// Extract rkey from the post URI
$rkey = basename($post->uri);

$client->bsky->post->delete($rkey);
```

## Working with Profiles

### Get a Profile

```php
$profile = $client->bsky->actor->getProfile('someone.bsky.social');

echo $profile->json('displayName');
echo $profile->json('description');
echo $profile->json('followersCount');
```

### Update Your Profile

```php
// Update display name
$client->bsky->profile->updateDisplayName('New Name');

// Update bio/description
$client->bsky->profile->updateDescription('Laravel developer building on AT Protocol');

// Update multiple fields at once
$client->bsky->profile->update([
    'displayName' => 'New Name',
    'description' => 'New bio here',
]);
```

### Update Avatar

```php
$blob = $client->atproto->repo->uploadBlob(new SplFileInfo('/path/to/avatar.jpg'));

$client->bsky->profile->updateAvatar($blob->json('blob'));
```

## Social Graph

### Follow a User

```php
// Follow requires the user's DID
$follow = $client->bsky->follow->create('did:plc:...');
```

### Unfollow a User

```php
// Get the rkey from the follow record URI
$client->bsky->follow->delete($rkey);
```

### Like a Post

```php
$postRef = new StrongRef(uri: 'at://...', cid: 'bafyre...');

$like = $client->bsky->like->create($postRef);
```

### Unlike a Post

```php
$client->bsky->like->delete($rkey);
```

## Feed Operations

### Get Your Timeline

```php
$timeline = $client->bsky->feed->getTimeline(limit: 50);

foreach ($timeline->json('feed') as $item) {
    $post = $item['post'];
    echo $post['author']['handle'] . ': ' . $post['record']['text'];
}
```

### Pagination with Cursors

```php
$cursor = null;

do {
    $timeline = $client->bsky->feed->getTimeline(limit: 100, cursor: $cursor);

    foreach ($timeline->json('feed') as $item) {
        // Process posts
    }

    $cursor = $timeline->json('cursor');
} while ($cursor);
```

### Get Author Feed

```php
$feed = $client->bsky->feed->getAuthorFeed(
    actor: 'someone.bsky.social',
    limit: 50
);
```

### Search Posts

```php
$results = $client->bsky->feed->searchPosts(
    q: 'laravel php',
    limit: 25
);
```

### Get Post Thread

```php
$thread = $client->bsky->feed->getPostThread(
    uri: 'at://did:plc:.../app.bsky.feed.post/...',
    depth: 6
);
```

### Get Likes on a Post

```php
$likes = $client->bsky->feed->getLikes(uri: 'at://...');
```

### Get Reposts

```php
$reposts = $client->bsky->feed->getRepostedBy(uri: 'at://...');
```

## Configuration

After publishing the config file, you can customize these options:

```php
// config/client.php

return [
    // OAuth client metadata
    'client' => [
        'name' => env('ATP_CLIENT_NAME', config('app.name')),
        'url' => env('ATP_CLIENT_URL', config('app.url')),
        'redirect_uris' => [
            env('ATP_CLIENT_REDIRECT_URI', config('app.url').'/auth/atp/callback'),
        ],
        'scopes' => ['atproto', 'transition:generic'],
    ],

    // Credential storage provider
    'credential_provider' => \SocialDept\AtpClient\Providers\ArrayCredentialProvider::class,

    // Session behavior
    'session' => [
        'refresh_threshold' => 300,      // Refresh if expires within 5 minutes
        'dpop_key_rotation' => 86400,    // Rotate DPoP keys after 24 hours
    ],

    // OAuth settings
    'oauth' => [
        'disabled' => false,
        'prefix' => '/atp/oauth/',
        'private_key' => env('ATP_OAUTH_PRIVATE_KEY'),
        'kid' => env('ATP_OAUTH_KID', 'atp-client-key'),
    ],

    // HTTP client settings
    'http' => [
        'timeout' => 30,
        'retry' => [
            'times' => 3,
            'sleep' => 100,
        ],
    ],
];
```

### Environment Variables

```env
ATP_CLIENT_NAME="My App"
ATP_CLIENT_URL="https://myapp.com"
ATP_CLIENT_REDIRECT_URI="https://myapp.com/auth/atp/callback"
ATP_OAUTH_PRIVATE_KEY="base64-encoded-private-key"
ATP_OAUTH_KID="atp-client-key"
ATP_REFRESH_THRESHOLD=300
ATP_HTTP_TIMEOUT=30
```

The `ATP_OAUTH_KID` is the Key ID used in your JWKS endpoint. Some developers may require this to match a specific value. The default is `atp-client-key`.

## Credential Storage

The package uses a `CredentialProvider` interface for token storage. The default `ArrayCredentialProvider` stores credentials in memory (lost on request end). For production applications, you need to implement persistent storage.

### Why You Need a Credential Provider

AT Protocol OAuth uses **single-use refresh tokens**. When a token is refreshed:
1. The old refresh token is immediately invalidated
2. A new refresh token is issued
3. You must store the new token before using it again

If you lose the refresh token, the user must re-authenticate. The `CredentialProvider` ensures tokens are safely persisted.

### How Handle Resolution Works

When you call `Atp::as('user.bsky.social')` or `Atp::login('user.bsky.social', $password)`, the package automatically resolves the handle to a DID (Decentralized Identifier). The DID is then used as the storage key for credentials. This ensures consistency even if a user changes their handle.

If resolution fails (invalid handle, network error, etc.), a `HandleResolutionException` is thrown.

### The CredentialProvider Interface

```php
interface CredentialProvider
{
    // Get stored credentials by DID
    public function getCredentials(string $did): ?Credentials;

    // Store credentials after initial OAuth or app password login
    public function storeCredentials(string $did, AccessToken $token): void;

    // Update credentials after token refresh (CRITICAL: refresh tokens are single-use!)
    public function updateCredentials(string $did, AccessToken $token): void;

    // Remove credentials (logout)
    public function removeCredentials(string $did): void;
}
```

### Database Migration

Create a migration for storing credentials:

```bash
php artisan make:migration create_atp_credentials_table
```

```php
Schema::create('atp_credentials', function (Blueprint $table) {
    $table->id();
    $table->string('did')->unique();         // Decentralized identifier (primary key)
    $table->string('handle')->nullable();    // User's handle (e.g., user.bsky.social)
    $table->string('issuer')->nullable();    // PDS endpoint URL
    $table->text('access_token');            // JWT access token
    $table->text('refresh_token');           // Single-use refresh token
    $table->timestamp('expires_at');         // Token expiration time
    $table->json('scope')->nullable();       // Granted OAuth scopes
    $table->timestamps();
});
```

### Implementing a Database Provider

```php
<?php

namespace App\Providers;

use App\Models\AtpCredential;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\Credentials;

class DatabaseCredentialProvider implements CredentialProvider
{
    public function getCredentials(string $did): ?Credentials
    {
        $record = AtpCredential::where('did', $did)->first();

        if (! $record) {
            return null;
        }

        return new Credentials(
            did: $record->did,
            accessToken: $record->access_token,
            refreshToken: $record->refresh_token,
            expiresAt: $record->expires_at,
            handle: $record->handle,
            issuer: $record->issuer,
            scope: $record->scope ?? [],
        );
    }

    public function storeCredentials(string $did, AccessToken $token): void
    {
        AtpCredential::updateOrCreate(
            ['did' => $did],
            [
                'handle' => $token->handle,
                'issuer' => $token->issuer,
                'access_token' => $token->accessJwt,
                'refresh_token' => $token->refreshJwt,
                'expires_at' => $token->expiresAt,
                'scope' => $token->scope,
            ]
        );
    }

    public function updateCredentials(string $did, AccessToken $token): void
    {
        AtpCredential::where('did', $did)->update([
            'access_token' => $token->accessJwt,
            'refresh_token' => $token->refreshJwt,
            'expires_at' => $token->expiresAt,
            'handle' => $token->handle,
            'issuer' => $token->issuer,
            'scope' => $token->scope,
        ]);
    }

    public function removeCredentials(string $did): void
    {
        AtpCredential::where('did', $did)->delete();
    }
}
```

### The AtpCredential Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtpCredential extends Model
{
    protected $fillable = [
        'did',
        'handle',
        'issuer',
        'access_token',
        'refresh_token',
        'expires_at',
        'scope',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'scope' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];
}
```

### Register Your Provider

Update your config file:

```php
// config/client.php

'credential_provider' => App\Providers\DatabaseCredentialProvider::class,
```

Or bind it in a service provider:

```php
// app/Providers/AppServiceProvider.php

use SocialDept\AtpClient\Contracts\CredentialProvider;
use App\Providers\DatabaseCredentialProvider;

public function register(): void
{
    $this->app->singleton(CredentialProvider::class, DatabaseCredentialProvider::class);
}
```

### Linking to Your User Model

If you want to associate ATP credentials with your application's users:

```php
// Migration
Schema::table('atp_credentials', function (Blueprint $table) {
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
});

// AtpCredential model
public function user()
{
    return $this->belongsTo(User::class);
}

// User model
public function atpCredential()
{
    return $this->hasOne(AtpCredential::class);
}
```

Then update your provider to work with the authenticated user:

```php
public function storeCredentials(string $did, AccessToken $token): void
{
    AtpCredential::updateOrCreate(
        ['did' => $did],
        [
            'user_id' => auth()->id(),  // Link to current user
            'handle' => $token->handle,
            'issuer' => $token->issuer,
            'access_token' => $token->accessJwt,
            'refresh_token' => $token->refreshJwt,
            'expires_at' => $token->expiresAt,
        ]
    );
}
```

### Understanding the Credential Fields

| Field | Description |
|-------|-------------|
| `did` | Decentralized Identifier - the stable, permanent user ID (e.g., `did:plc:abc123...`) |
| `handle` | User's handle (e.g., `user.bsky.social`) - can change |
| `issuer` | The user's PDS endpoint URL (avoids repeated lookups) |
| `accessToken` | JWT for API authentication (short-lived) |
| `refreshToken` | Token to get new access tokens (single-use!) |
| `expiresAt` | When the access token expires |
| `scope` | Array of granted scopes (e.g., `['atproto', 'transition:generic']`) |

### Handling Token Refresh Events

When tokens are automatically refreshed, you can listen for events:

```php
use SocialDept\AtpClient\Events\OAuthTokenRefreshed;

// In EventServiceProvider or via Event::listen()
Event::listen(OAuthTokenRefreshed::class, function (OAuthTokenRefreshed $event) {
    // The CredentialProvider.updateCredentials() is already called,
    // but you can do additional logging or notifications here
    Log::info("Token refreshed for: {$event->session->did()}");
});
```

## Events

The package dispatches events you can listen to:

### OAuthUserAuthenticated

Fired after a successful OAuth callback. Use this to create or update users in your application:

```php
use SocialDept\AtpClient\Events\OAuthUserAuthenticated;
use SocialDept\AtpClient\Facades\Atp;

Event::listen(OAuthUserAuthenticated::class, function (OAuthUserAuthenticated $event) {
    // $event->token contains: did, accessJwt, refreshJwt, handle, issuer, expiresAt, scope

    // Check granted scopes
    if (in_array('atproto', $event->token->scope)) {
        // User granted AT Protocol access
    }

    // Fetch the user's profile
    $client = Atp::as($event->token->did);
    $profile = $client->bsky->actor->getProfile($event->token->did);

    // Create or update user in your database
    $user = User::updateOrCreate(
        ['did' => $event->token->did],
        [
            'handle' => $event->token->handle,
            'name' => $profile->json('displayName'),
            'avatar' => $profile->json('avatar'),
        ]
    );

    // Log them in
    Auth::login($user);
});
```

### OAuthTokenRefreshing / OAuthTokenRefreshed

Fired before and after automatic token refresh. Use `OAuthTokenRefreshing` to invalidate your stored refresh token before it's used (refresh tokens are single-use):

```php
use SocialDept\AtpClient\Events\OAuthTokenRefreshing;
use SocialDept\AtpClient\Events\OAuthTokenRefreshed;

// Before token refresh - invalidate old refresh token
Event::listen(OAuthTokenRefreshing::class, function (OAuthTokenRefreshing $event) {
    // $event->session gives access to did(), handle(), etc.
    Log::info('Refreshing token for: ' . $event->session->did());
});

// After token refresh - new tokens available
Event::listen(OAuthTokenRefreshed::class, function (OAuthTokenRefreshed $event) {
    // $event->session - the session being refreshed
    // $event->token - the new AccessToken with fresh tokens
    // CredentialProvider.updateCredentials() is already called automatically
    Log::info('Token refreshed for: ' . $event->session->did());
});
```

## Available Commands

```bash
# Generate OAuth private key
php artisan atp-client:generate-key
```

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- [socialdept/atp-schema](https://github.com/socialdept/atp-schema) ^0.2
- [socialdept/atp-resolver](https://github.com/socialdept/atp-resolver) ^1.0

## Testing

```bash
composer test
```

## Resources

- [AT Protocol Documentation](https://atproto.com/)
- [Bluesky API Docs](https://docs.bsky.app/)
- [CRYPTO.md](CRYPTO.md) - Cryptographic implementation details

## Support & Contributing

Found a bug or have a feature request? [Open an issue](https://github.com/socialdept/atp-client/issues).

Want to contribute? Check out the [contribution guidelines](contributing.md).

## Changelog

Please see [changelog](changelog.md) for recent changes.

## Credits

- [Miguel Batres](https://batres.co) - founder & lead maintainer
- [All contributors](https://github.com/socialdept/atp-client/graphs/contributors)

## License

AtpClient is open-source software licensed under the [MIT license](license.md).

---

**Built for the Federation** - By Social Dept.
