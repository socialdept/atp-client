# Service Auth

Inter-service auth: how one AT Protocol service proves who it is to another, and how to verify that on the way in.

## What this is, and what it is not

OAuth authenticates **a user to your app**. Service auth authenticates **a service to your service**: a PDS proxying an XRPC call to an appview, an authority asking a managing app whether to admit a user, a collector accepting events from a publisher.

The caller signs a short-lived JWT with its repo signing key. You resolve the caller's DID document and check the signature against the key published there. There is no registration step, no shared secret, and nothing to provision on either side: identity comes from the DID document the caller already publishes.

| | OAuth | Service auth |
|---|---|---|
| Proves | a user authorized your app | a service is who it claims to be |
| Credential | access token, DPoP-bound | short-lived signed JWT, bearer |
| Direction here | outbound (you call a PDS) | inbound (someone calls you) |
| Lifetime | minutes, refreshable | 60 seconds, minted per call |

This package verifies **inbound** tokens. Minting is available (see [Minting](#minting)) but most applications never mint: a client asks its own PDS for a token with `com.atproto.server.getServiceAuth`.

## Quick start

Protect an XRPC route with the middleware, naming the method it accepts tokens for:

```php
Route::get('/xrpc/com.example.doThing', DoThingController::class)
    ->middleware('atp.service-auth:com.example.doThing');
```

Tell the package which service identifier it answers to:

```env
ATP_SERVICE_AUTH_AUDIENCE=did:web:example.com#forum
```

The verified token is attached to the request, so the handler asks who is calling without re-parsing anything:

```php
use SocialDept\AtpClient\Http\Middleware\VerifyServiceAuthMiddleware;

public function __invoke(Request $request)
{
    $token = $request->attributes->get(VerifyServiceAuthMiddleware::ATTRIBUTE);

    return response()->json(['caller' => $token->did()]);
}
```

## Always pass the NSID

The middleware argument is the lexicon method the token must be bound to, and it is the whole point of the `lxm` claim. A token minted to call one endpoint should not be spendable at another, and that check only happens if the route says what it is:

```php
// Good: a token for checkUserAccess cannot be replayed here
->middleware('atp.service-auth:com.example.doThing')

// Weak: any token addressed to this service is accepted
->middleware('atp.service-auth')
```

> **Note:** the check compares two present values. A token that carries **no** `lxm` at all is unbound and passes, even on a route that names a method. Callers should always request a method-bound token; `com.atproto.server.getServiceAuth` takes an `lxm` parameter for exactly this reason.

## Configuration

```php
'service_auth' => [
    'audience' => env('ATP_SERVICE_AUTH_AUDIENCE'),
],
```

`audience` is the identifier this application answers to, compared against the token's `aud`. It is a DID, optionally with a service fragment, for example `did:web:example.com#forum`. The fragment matters when one DID publishes several services and a token for one must not be spendable at another.

Leaving it unset **skips the audience check**, which is only safe when nothing else distinguishes this service from another the caller could reach. Set it.

## What gets verified

In order, cheapest first, so a malformed token never reaches the crypto:

| # | Check | Failure |
|---|-------|---------|
| 1 | Three dot-separated parts, header and payload decode as JSON | `BadJwt` |
| 2 | `alg` is on the `ES256K` / `ES256` allowlist, so `none` never reaches the signature check | `BadJwt` |
| 3 | Signature decodes to exactly 64 bytes | `BadJwt` |
| 4 | `iss` is a DID, fragment stripped | `BadJwtIss` |
| 5 | `exp` present and in the future, tolerating 30s of clock skew | `JwtExpired` |
| 6 | `aud` present, and equal to the configured audience (constant-time) | `BadJwtAudience` |
| 7 | `lxm` matches the expected method, when both are present | `BadJwtLexiconMethod` |
| 8 | Signature verifies against a key the issuer publishes | `BadJwtSignature` |

For step 8 the `kid` header names which verification method signed, defaulting to `#atproto`, the repo signing key. The issuer's DID document is resolved, `getSigningKey()` returns the published `did:key`, and `SignatureVerifier` (from `atp-support`) checks the signature.

That verifier enforces two atproto rules a general-purpose ECDSA check would not: **compact signatures only** (64 raw bytes of `r || s`; DER is rejected rather than parsed) and **low-S only**. Together they make a signature's bytes canonical, so the same signature cannot be re-encoded into a different byte string.

### Key rotation

If verification fails against the cached DID document, resolution is retried uncached and the signature is checked again. A cached document can predate a rotation; a freshly fetched one cannot. Only after both fail is the token refused.

## Error responses

The middleware refuses in the shape an XRPC client expects:

```json
{
  "error": "BadJwtAudience",
  "message": "Service auth token is addressed to another service, expected \"did:web:example.com#forum\"."
}
```

| Factory | `error` | Status |
|---------|---------|--------|
| `ServiceAuthException::missing()` | `AuthMissing` | 401 |
| `ServiceAuthException::malformed()` | `BadJwt` | 400 |
| `ServiceAuthException::expired()` | `JwtExpired` | 401 |
| `ServiceAuthException::audience()` | `BadJwtAudience` | 401 |
| `ServiceAuthException::method()` | `BadJwtLexiconMethod` | 401 |
| `ServiceAuthException::issuer()` | `BadJwtIss` | 401 |
| `ServiceAuthException::signature()` | `BadJwtSignature` | 401 |

The header must be `Authorization: Bearer <jwt>`. Anything else is treated as missing.

## Verifying by hand

When a route does not fit the middleware (a queued job, a webhook, a non-Laravel entry point), call the service directly. It throws `ServiceAuthException` and returns a `ServiceAuthToken`:

```php
use SocialDept\AtpClient\Auth\ServiceAuth;
use SocialDept\AtpClient\Exceptions\ServiceAuthException;

$serviceAuth = app(ServiceAuth::class);

try {
    $token = $serviceAuth->verify(
        $serviceAuth->fromHeader($request->header('Authorization')),
        audience: 'did:web:example.com#forum',
        method: 'com.example.doThing',
    );
} catch (ServiceAuthException $e) {
    abort($e->status, $e->getMessage());
}
```

Both `audience` and `method` are optional, and passing `null` skips that check.

## ServiceAuthToken

What a successful verification hands back:

| Member | What it is |
|--------|------------|
| `did()` | The DID that signed the token (who is calling) |
| `issuer` | Same value, as the raw `iss` claim |
| `audience` | The service identifier it was addressed to |
| `method` | The NSID it authorizes, or `null` when unbound |
| `expiresAt` | Unix timestamp from `exp` |
| `authorizes(string $nsid)` | Whether it covers a method (`true` for an unbound token) |
| `secondsRemaining()` | Time left before expiry |
| `claim(string $name)` | Any other claim from the payload |

## Minting

`mint()` builds and signs a token. The signing callback is yours to supply, because the package never holds repo keys. It takes the signing input and returns 64 raw bytes of `r || s`:

```php
$jwt = app(ServiceAuth::class)->mint(
    issuer: 'did:plc:abc123',
    audience: 'did:web:collector.example#ping_collector',
    method: 'com.example.doThing',
    sign: fn (string $input) => $signer->sign($input),
    algorithm: 'ES256K',   // ES256 for a P-256 key
    lifetime: 60,          // seconds; the default
);
```

Every token gets a random `jti`, and `iat`/`exp` are stamped from server time. Claims that are `null` are omitted rather than sent empty.

Again: most applications should not do this. If you hold an authenticated session, ask the PDS for a token with `com.atproto.server.getServiceAuth` and let it sign with the user's repo key.

## Trust model

Be clear about what a verified token proves:

> DID X signed a request addressed to this service, for this method, that has not yet expired.

It is a **bearer** credential inside its window. Anyone who intercepts it can replay it until `exp`, which is why lifetimes are 60 seconds and why the `lxm` binding matters more than the lifetime does: a stolen token is only spendable at the one endpoint it names. It says nothing about authorization: whether DID X may do the thing is your application's decision, made after verification.

## See also

- [OAuth Scopes](scopes.md): user-facing authorization
- [Sessions & Keep-Alive](sessions.md): the outbound side
- [AT Protocol: cross-service auth](https://atproto.com/specs/xrpc#inter-service-authentication-temporary-specification)
