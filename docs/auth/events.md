# Events

Part of the [atp-client docs](../introduction.md#documentation). This page covers the events dispatched on sign in and session refresh.

The package dispatches events you can listen to:

## SessionAuthenticated

Fired after a successful OAuth callback or app password login. `isOAuth()` and `isLegacy()` tell the two apart. Use this to create or update users in your application:

```php
use SocialDept\AtpClient\Events\SessionAuthenticated;
use SocialDept\AtpClient\Facades\Atp;

Event::listen(SessionAuthenticated::class, function (SessionAuthenticated $event) {
    // $event->token contains: did, accessJwt, refreshJwt, handle, issuer, expiresAt, scope, authType

    // Check granted scopes
    if (in_array('atproto', $event->token->scope)) {
        // User granted AT Protocol access
    }

    // Fetch the user's profile. An OAuth callback fires this before you store
    // the credentials, so read it from the public API.
    $profile = Atp::public()->bsky->actor->getProfile($event->token->did);

    // Create or update user in your database
    $user = User::updateOrCreate(
        ['did' => $event->token->did],
        [
            'handle' => $event->token->handle,
            'name' => $profile->displayName,
            'avatar' => $profile->avatar,
        ]
    );

    // Log them in
    Auth::login($user);
});
```

## SessionRefreshing / SessionUpdated

Fired before and after automatic token refresh for both OAuth and legacy sessions. Use `SessionRefreshing` to invalidate your stored refresh token before it's used (refresh tokens are single-use):

```php
use SocialDept\AtpClient\Events\SessionRefreshing;
use SocialDept\AtpClient\Events\SessionUpdated;

// Before token refresh - invalidate old refresh token
Event::listen(SessionRefreshing::class, function (SessionRefreshing $event) {
    // $event->session gives access to did(), handle(), authType(), isLegacy(), etc.
    Log::info('Refreshing token for: ' . $event->session->did());
});

// After token refresh - new tokens available
Event::listen(SessionUpdated::class, function (SessionUpdated $event) {
    // $event->session - the session being refreshed
    // $event->token - the new AccessToken with fresh tokens
    // CredentialProvider.updateCredentials() is already called automatically
    Log::info('Token refreshed for: ' . $event->session->did());

    // Check auth type if needed
    if ($event->session->isLegacy()) {
        // Legacy (app password) session
    }
});
```

## SessionRefreshFailed

Fired when a refresh fails. Carries a legacy `reason` string (backwards compatible) and a typed `failureReason` (`RefreshFailureReason`) so you can flag a session **only** on a terminal failure and leave transient ones (nonce/5xx/network) alone:

```php
use SocialDept\AtpClient\Events\SessionRefreshFailed;

Event::listen(SessionRefreshFailed::class, function (SessionRefreshFailed $event) {
    if ($event->failureReason?->isTerminal()) {
        // Dead grant or account gone. Prompt the user to reconnect.
        markSessionInvalid($event->session->did());
    }
    // Transient failures are retried automatically on the next call/keep-alive.
});
```

## SessionInvalid

Fired when a session is found unusable before a refresh can run, because no credentials are stored for the DID or the DPoP key behind an OAuth grant is gone. No `Session` exists yet, so it carries `did`, a typed `reason` (`RefreshFailureReason`) and an optional `exception` instead. Flag sessions for reconnect from this event as well as from `SessionRefreshFailed`.

```php
use SocialDept\AtpClient\Events\SessionInvalid;

Event::listen(SessionInvalid::class, function (SessionInvalid $event) {
    markSessionInvalid($event->did);
});
```
