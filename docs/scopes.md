# OAuth Scopes

The AT Protocol uses OAuth scopes to control what actions an application can perform on behalf of a user. AtpClient provides attributes for documenting scope requirements on endpoints.

> **Note:** The `#[ScopedEndpoint]` and `#[PublicEndpoint]` attributes currently serve as documentation only. Runtime scope validation and enforcement will be implemented in a future release. Using these attributes correctly now ensures forward compatibility.

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
Scope::include('com.example.authFull')                   // Permission set
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

### Permission Sets

A permission set bundles permissions under one lexicon, so an app asks for `include:com.example.authFull` instead of listing every collection and endpoint. The authorization server resolves the set from its NSID and shows the user its title and description on the consent screen. A set may only cover resources under its own NSID namespace.

```php
Scope::include('com.example.authFull');
// include:com.example.authFull

Scope::include('com.example.authFull', 'did:web:api.example.com#svc_appview');
// include:com.example.authFull?aud=did:web:api.example.com%23svc_appview
```

`aud` fills in the audience of any rpc permission in the set marked `inheritAud`, written in the combined `did#serviceId` form.

**The granted scope never contains `include:`.** When the authorization server issues a token it expands each set into the granular permissions it contains, and those are what the session's scopes list. A set covering several collections expands into the named form:

```
repo?collection=com.example.post&collection=com.example.publication&action=create&action=update
```

The scope checker reads both `repo:<collection>` and `repo?collection=...`, and treats a repo scope with no `action` as granting all three, so checks against an expanded set work without listing its contents yourself.

## The ScopedEndpoint Attribute

The `#[ScopedEndpoint]` attribute documents scope requirements on methods that require authentication.

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

### What is checked

The client checks scopes itself before a **repo write**: `createRecord()`, `putRecord()`, `deleteRecord()` and `applyWrites()` each check the session for the collection and action involved. `transition:generic` always passes. Everything else is left to the PDS, which enforces the session's scopes on every request regardless, and `#[ScopedEndpoint]` remains documentation.

### Configuration

Set the enforcement level in `config/atp-client.php` or through the environment:

```php
'scope_enforcement' => ScopeEnforcementLevel::Permissive,
```

```env
ATP_SCOPE_ENFORCEMENT=strict
```

| Level | Behavior |
|-------|----------|
| `Strict` | Throws `MissingScopeException` before the request is sent |
| `Permissive` | Logs a warning and sends the request anyway, so the PDS decides |

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

The `AtpScope` facade wraps the same checks for the current user or a given session: `can()`, `canAny()`, `canAll()`, `cannot()`, `authorize()` and `granted()`, with `forSession()` and `forUser()` to choose whose scopes to read.

### Granular Pattern Matching

The scope checker supports wildcard patterns:

```php
// Check if session can access any feed endpoint
$checker->matchesGranular($session, 'rpc:app.bsky.feed.*');

// Check if session can upload images
$checker->matchesGranular($session, 'blob:image/*');

// Check if session has any repo access
$checker->matchesGranular($session, 'repo:*');

// Granted scopes from an expanded permission set are matched too
$checker->matchesGranular($session, 'repo:com.example.post?action=create');
// true when granted 'repo?collection=com.example.post&collection=com.example.publication&action=create'
```

## Route Middleware

Protect Laravel routes based on ATP session scopes with `atp.scope`. It uses the same checker, so scopes from an expanded permission set count:

```php
use Illuminate\Support\Facades\Route;

// Single scope
Route::get('/timeline', TimelineController::class)
    ->middleware('atp.scope:transition:generic');

// Multiple scopes (AND logic)
Route::get('/email-settings', EmailSettingsController::class)
    ->middleware('atp.scope:transition:generic,transition:email');
```

The middleware refuses a request with no authenticated user, a user model that does not implement `HasAtpSession`, a user with no ATP session, or a session missing any listed scope.

### Middleware Configuration

Configure what a refusal does in `config/atp-client.php`:

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

Methods marked with `#[PublicEndpoint]` can be called without authentication using `Atp::public()`:

```php
// Public mode - no authentication required
$client = Atp::public('https://public.api.bsky.app');
$client->bsky->actor->getProfile('someone.bsky.social');  // Works without auth

// Authenticated mode - for endpoints requiring scopes
$client = Atp::as($did);
$client->bsky->feed->getTimeline();  // Requires transition:generic scope
```

Methods with `#[PublicEndpoint]` work in both modes, while methods with `#[ScopedEndpoint]` require authentication.

## Exception Handling

### MissingScopeException

Thrown by a repo write when the session lacks the scope for it and enforcement is strict. Nothing is sent to the PDS:

```php
use SocialDept\AtpClient\Exceptions\MissingScopeException;

try {
    $client->atproto->repo->createRecord('app.bsky.feed.post', $record);
} catch (MissingScopeException $e) {
    $e->required;   // Scopes the call needed
    $e->granted;    // Scopes the session has

    // Ask the user to re-authorize with the missing scope
}
```

### ScopeAuthorizationException

Raised by the `atp.scope` middleware when it refuses a request. It extends `MissingScopeException`, so `required` and `granted` are available, and it renders according to `failure_action`: a 403, a redirect, or the exception itself.

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

Request only the scopes your application needs. Authorization requests use `atp-client.client.scopes`, and a permission set is often the tidiest way to ask for several:

```php
// config/atp-client.php
'client' => [
    'scopes' => ['atproto', Scope::include('com.example.authFull')],
],
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
