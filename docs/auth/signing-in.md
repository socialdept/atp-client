# Signing in

Part of the [atp-client docs](../introduction.md#documentation). This page covers signing in with an app password or OAuth, refreshing tokens and signing out.

## App Password Flow

The simplest way to authenticate. Generate an app password in your Bluesky settings.

```php
use SocialDept\AtpClient\Facades\Atp;

$client = Atp::login('yourhandle.bsky.social', 'your-app-password');

// Client is now authenticated and ready to use
$profile = $client->bsky->actor->getProfile('yourhandle.bsky.social');
```

## OAuth 2.0 Flow

For user-facing applications where users authenticate with their own accounts.

**Step 1: Initiate authorization**
```php
use SocialDept\AtpClient\Facades\Atp;

public function redirect()
{
    $auth = Atp::oauth()->authorize('user.bsky.social');

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
        request: $auth,
        issuer: $request->get('iss'),
    );

    // Store credentials using your CredentialProvider
    // $token contains: accessJwt, refreshJwt, did, handle, issuer, expiresAt, scope, authType
}
```

Pass `iss` from the redirect: a server naming a different issuer than the flow started at is refused before the code is exchanged (RFC 9207).

`callback()` also checks the account before storing anything. The token response names the account, but any authorization server could name any DID, so the DID's own PDS must use the issuing server, and a flow started from a handle must land on that handle. A failure throws `AuthenticationException`. If you exchange the code yourself after `callbackWithoutExchange()`, call `Atp::oauth()->verifyAccount($did, $issuer, $handle)` before trusting the account.

**Starting at a host instead of a handle**

For a "Log in with" button, start at the host's authorization server without naming an account. The server asks who is signing in, and the callback checks the account it returns belongs there.

```php
$auth = Atp::oauth()->authorizeAt('https://bsky.social');

// Open on account creation, where the server supports it
$auth = Atp::oauth()->authorizeAt('https://bsky.social', prompt: 'create');
```

`authorize($handle)` sends the handle as `login_hint`, so the account's server opens with it selected, and takes `prompt` too.

**Step 3: Use stored credentials**
```php
// After storing credentials, use them with Atp::as()
$client = Atp::as('user.bsky.social');
```

## Token Refresh

Sessions automatically refresh when tokens are about to expire (default: 5 minutes before expiration). The new tokens are saved through your `CredentialProvider`'s `updateCredentials()` before `SessionUpdated` fires. Listen to it if you need to react to a refresh:

```php
use SocialDept\AtpClient\Events\SessionUpdated;

Event::listen(SessionUpdated::class, function ($event) {
    // $event->session - the Session being refreshed
    // $event->token - the new AccessToken
    // Credentials are already updated, react to the refresh here

    // Check auth type if needed
    if ($event->session->isLegacy()) {
        // App password session
    }
});
```

## Signing Out

`revoke()` ends a stored session at the server and forgets it locally. OAuth sessions are revoked at the authorization server's `revocation_endpoint` (RFC 7009), authenticated the same way a refresh is. App password sessions are deleted with `com.atproto.server.deleteSession`.

```php
$result = Atp::oauth()->revoke($did);

$result->revoked; // the server confirmed it
$result->failure; // otherwise why not, a RevocationFailure
```

The credentials and DPoP key are forgotten whatever the server says, so a network error, a refusal or a server without a revocation endpoint never leaves the user signed in, and never throws. Call `$result->throw()` when you need the grant confirmed dead. See [Sessions](sessions.md#signing-out).
