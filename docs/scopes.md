# OAuth Scopes

The AT Protocol uses OAuth scopes to control what actions an application can perform on behalf of a user. AtpClient provides tools for documenting, checking, and enforcing scope requirements.

## Quick Reference

### Scope Enum

```php
use SocialDept\AtpClient\Enums\Scope;

// Transition scopes (current AT Protocol scopes)
Scope::Atproto              // 'atproto' - Full access
Scope::TransitionGeneric    // 'transition:generic' - General API access
Scope::TransitionEmail      // 'transition:email' - Email access
Scope::TransitionChat       // 'transition:chat.bsky' - Chat access

// Granular scope builders (future AT Protocol scopes)
Scope::repo('app.bsky.feed.post', ['create', 'delete'])  // Record operations
Scope::rpc('app.bsky.feed.getTimeline')                  // RPC endpoint access
Scope::blob('image/*')                                    // Blob upload access
Scope::account('email')                                   // Account attribute access
Scope::identity('handle')                                 // Identity attribute access
```

### ScopedEndpoint Attribute

```php
use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Enums\Scope;

#[ScopedEndpoint(Scope::TransitionGeneric)]
public function getTimeline(): GetTimelineResponse
{
    // Method implementation
}
```

## Understanding AT Protocol Scopes

### Current Transition Scopes

The AT Protocol is currently in a transition period where broad "transition scopes" are used:

| Scope | Description |
|-------|-------------|
| `atproto` | Full access to the AT Protocol |
| `transition:generic` | General API access for most operations |
| `transition:email` | Access to email-related operations |
| `transition:chat.bsky` | Access to Bluesky chat features |

### Future Granular Scopes

The AT Protocol is moving toward granular scopes that provide fine-grained access control:

```php
// Record operations
'repo:app.bsky.feed.post'                      // All operations on posts
'repo:app.bsky.feed.post?action=create'        // Only create posts
'repo:app.bsky.feed.like?action=create&action=delete'  // Create or delete likes
'repo:*'                                       // All collections, all actions

// RPC endpoint access
'rpc:app.bsky.feed.getTimeline'               // Access to timeline endpoint
'rpc:app.bsky.feed.*'                         // All feed endpoints

// Blob operations
'blob:image/*'                                 // Upload images
'blob:*/*'                                     // Upload any blob type

// Account and identity
'account:email'                                // Access email
'identity:handle'                              // Manage handle
```

## The ScopedEndpoint Attribute

The `#[ScopedEndpoint]` attribute documents and optionally enforces scope requirements on methods.

### Basic Usage

```php
<?php

namespace App\Atp;

use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;

class CustomClient extends Request
{
    #[ScopedEndpoint(Scope::TransitionGeneric)]
    public function getTimeline(): array
    {
        return $this->atp->client->get('app.bsky.feed.getTimeline')->json();
    }
}
```

### With Granular Scope

Document the future granular scope that will replace the transition scope:

```php
#[ScopedEndpoint(
    Scope::TransitionGeneric,
    granular: 'rpc:app.bsky.feed.getTimeline'
)]
public function getTimeline(): GetTimelineResponse
{
    // ...
}
```

### With Description

Add a human-readable description for documentation:

```php
#[ScopedEndpoint(
    Scope::TransitionGeneric,
    granular: 'rpc:app.bsky.feed.getTimeline',
    description: 'Access to the user\'s home timeline'
)]
public function getTimeline(): GetTimelineResponse
{
    // ...
}
```

### Multiple Scopes (AND Logic)

When a method requires multiple scopes, all must be present:

```php
#[ScopedEndpoint([Scope::TransitionGeneric, Scope::TransitionEmail])]
public function getEmailPreferences(): array
{
    // Requires BOTH scopes
}
```

### Multiple Attributes (OR Logic)

Use multiple attributes for alternative scope requirements:

```php
#[ScopedEndpoint(Scope::Atproto)]
#[ScopedEndpoint(Scope::TransitionGeneric)]
public function getProfile(string $actor): ProfileViewDetailed
{
    // Either scope satisfies the requirement
}
```

## Scope Enforcement

### Configuration

Configure scope enforcement in `config/client.php` or via environment variables:

```php
'scope_enforcement' => ScopeEnforcementLevel::Permissive,
```

| Level | Behavior |
|-------|----------|
| `Strict` | Throws `MissingScopeException` if required scopes are missing |
| `Permissive` | Logs a warning but attempts the request anyway |

Set via environment variable:

```env
ATP_SCOPE_ENFORCEMENT=strict
```

### Programmatic Scope Checking

Check scopes programmatically using the `ScopeChecker`:

```php
use SocialDept\AtpClient\Auth\ScopeChecker;
use SocialDept\AtpClient\Facades\Atp;

$checker = app(ScopeChecker::class);
$session = Atp::as($did)->client->session();

// Check if session has a scope
if ($checker->hasScope($session, Scope::TransitionGeneric)) {
    // Session has the scope
}

// Check multiple scopes
if ($checker->check($session, [Scope::TransitionGeneric, Scope::TransitionEmail])) {
    // Session has ALL required scopes
}

// Check and fail if missing (respects enforcement level)
$checker->checkOrFail($session, [Scope::TransitionGeneric]);

// Check repo scope for specific action
if ($checker->checkRepoScope($session, 'app.bsky.feed.post', 'create')) {
    // Can create posts
}
```

### Granular Pattern Matching

The scope checker supports wildcard patterns:

```php
// Check if session can access any feed endpoint
$checker->matchesGranular($session, 'rpc:app.bsky.feed.*');

// Check if session can upload images
$checker->matchesGranular($session, 'blob:image/*');

// Check if session has any repo access
$checker->matchesGranular($session, 'repo:*');
```

## Route Middleware

Protect Laravel routes based on ATP session scopes:

```php
use Illuminate\Support\Facades\Route;

// Single scope
Route::get('/timeline', TimelineController::class)
    ->middleware('atp.scope:transition:generic');

// Multiple scopes (AND logic)
Route::get('/email-settings', EmailSettingsController::class)
    ->middleware('atp.scope:transition:generic,transition:email');
```

### Middleware Configuration

Configure middleware behavior in `config/client.php`:

```php
'scope_authorization' => [
    // What to do when scope check fails
    'failure_action' => ScopeAuthorizationFailure::Abort,  // abort, redirect, or exception

    // Where to redirect (when failure_action is 'redirect')
    'redirect_to' => '/login',
],
```

| Failure Action | Behavior |
|----------------|----------|
| `Abort` | Returns 403 Forbidden response |
| `Redirect` | Redirects to configured URL |
| `Exception` | Throws `ScopeAuthorizationException` |

Set via environment variables:

```env
ATP_SCOPE_FAILURE_ACTION=redirect
ATP_SCOPE_REDIRECT=/auth/login
```

### User Model Integration

For the middleware to work, your User model must implement `HasAtpSession`:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use SocialDept\AtpClient\Contracts\HasAtpSession;

class User extends Authenticatable implements HasAtpSession
{
    public function getAtpDid(): ?string
    {
        return $this->atp_did;
    }
}
```

## Public Mode and Scopes

When using `Atp::public()`, no scope checking occurs because there's no authenticated session:

```php
// Public mode - no authentication, no scopes
$client = Atp::public('https://public.api.bsky.app');
$client->bsky->actor->getProfile('someone.bsky.social');  // Works without scopes

// Authenticated mode - scopes are checked
$client = Atp::as($did);
$client->bsky->feed->getTimeline();  // Requires transition:generic scope
```

Methods that work in public mode typically don't have `#[ScopedEndpoint]` attributes, while authenticated-only methods do.

## Exception Handling

### MissingScopeException

Thrown when required scopes are missing and enforcement is strict:

```php
use SocialDept\AtpClient\Exceptions\MissingScopeException;

try {
    $timeline = $client->bsky->feed->getTimeline();
} catch (MissingScopeException $e) {
    $missing = $e->getMissingScopes();   // Scopes that are missing
    $granted = $e->getGrantedScopes();   // Scopes the session has

    // Handle missing scope
}
```

### ScopeAuthorizationException

Thrown by middleware when route access is denied:

```php
use SocialDept\AtpClient\Exceptions\ScopeAuthorizationException;

try {
    // Route protected by atp.scope middleware
} catch (ScopeAuthorizationException $e) {
    $required = $e->getRequiredScopes();
    $granted = $e->getGrantedScopes();
    $message = $e->getMessage();
}
```

## Best Practices

### 1. Document All Scope Requirements

Always add `#[ScopedEndpoint]` to methods that require authentication:

```php
#[ScopedEndpoint(
    Scope::TransitionGeneric,
    granular: 'rpc:app.bsky.feed.getTimeline',
    description: 'Fetches the authenticated user\'s home timeline'
)]
public function getTimeline(): GetTimelineResponse
```

### 2. Use the Scope Enum

Prefer the `Scope` enum over string literals for type safety:

```php
// Good
#[ScopedEndpoint(Scope::TransitionGeneric)]

// Avoid
#[ScopedEndpoint('transition:generic')]
```

### 3. Request Minimal Scopes

When implementing OAuth, request only the scopes your application needs:

```php
$authUrl = Atp::oauth()->getAuthorizationUrl([
    'scope' => 'atproto transition:generic',
]);
```

### 4. Handle Missing Scopes Gracefully

Check for scope availability before attempting operations:

```php
$checker = app(ScopeChecker::class);
$session = $client->client->session();

if ($checker->hasScope($session, Scope::TransitionChat)) {
    $conversations = $client->chat->getConversations();
} else {
    // Inform user they need to re-authorize with chat scope
}
```

### 5. Use Permissive Mode in Development

Start with permissive enforcement during development, then switch to strict for production:

```env
# .env.local
ATP_SCOPE_ENFORCEMENT=permissive

# .env.production
ATP_SCOPE_ENFORCEMENT=strict
```
