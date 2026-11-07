# Installation

Part of the [atp-client docs](../introduction.md#documentation). This page covers installing the package and configuring it.

## Installing

```bash
composer require socialdept/atp-client
```

Optionally publish the configuration:

```bash
php artisan vendor:publish --tag=atp-client-config
```

OAuth clients sign with a private key. Generate one with:

```bash
php artisan atp-client:generate-key
```

## Configuration

After publishing the config file, you can customize these options:

```php
// config/atp-client.php

use SocialDept\AtpClient\Enums\ScopeAuthorizationFailure;
use SocialDept\AtpClient\Enums\ScopeEnforcementLevel;

return [
    // OAuth client metadata
    'client' => [
        'name' => env('ATP_CLIENT_NAME', config('app.name')),
        'url' => env('ATP_CLIENT_URL', config('app.url')),
        'client_id' => env('ATP_CLIENT_ID'),
        'redirect_uris' => array_filter([
            env('ATP_CLIENT_REDIRECT_URI'),
        ]),
        'scopes' => explode(' ', env('ATP_OAUTH_SCOPE', 'atproto transition:generic')),
    ],

    // Credential storage provider
    'credential_provider' => env(
        'ATP_CREDENTIAL_PROVIDER',
        \SocialDept\AtpClient\Providers\ArrayCredentialProvider::class
    ),

    // Session behavior
    'session' => [
        'refresh_threshold' => env('ATP_REFRESH_THRESHOLD', 300),      // Refresh if expires within 5 minutes
        'dpop_key_rotation' => env('ATP_DPOP_KEY_ROTATION', 86400),    // Rotate DPoP keys after 24 hours
        'allow_key_regeneration' => env('ATP_ALLOW_KEY_REGENERATION', false),
        'refresh_serialize' => env('ATP_REFRESH_SERIALIZE', true),     // Per-DID refresh lock
        'refresh_lock_wait' => env('ATP_REFRESH_LOCK_WAIT', 10),
        'refresh_lock_ttl' => env('ATP_REFRESH_LOCK_TTL', 15),
    ],

    // Inbound service auth
    'service_auth' => [
        'audience' => env('ATP_SERVICE_AUTH_AUDIENCE'),
        'allow_any_audience' => env('ATP_SERVICE_AUTH_ALLOW_ANY_AUDIENCE', false),
    ],

    // OAuth settings
    'oauth' => [
        'disabled' => env('ATP_OAUTH_DISABLED', false),
        'client_metadata_path' => env('ATP_OAUTH_CLIENT_METADATA_PATH', '/oauth-client-metadata.json'),
        'jwks_path' => env('ATP_OAUTH_JWKS_PATH', '/oauth-jwks.json'),
        'jwks_uri' => env('ATP_CLIENT_JWKS_URI'),
        'private_key' => env('ATP_OAUTH_PRIVATE_KEY'),
        'kid' => env('ATP_OAUTH_KID', 'atp-client-key'),
        'client_metadata' => [
            'client_name' => env('ATP_CLIENT_NAME', config('app.name')),
            'client_uri' => env('ATP_CLIENT_URL', config('app.url')),
            'logo_uri' => env('ATP_CLIENT_LOGO_URI'),
            'tos_uri' => env('ATP_CLIENT_TOS_URI'),
            'policy_uri' => env('ATP_CLIENT_POLICY_URI'),
        ],
    ],

    // HTTP client settings
    'http' => [
        'timeout' => env('ATP_HTTP_TIMEOUT', 30),
        'retry' => [
            'times' => env('ATP_HTTP_RETRY_TIMES', 3),
            'sleep' => env('ATP_HTTP_RETRY_SLEEP', 100),
        ],
    ],

    // Validate responses against lexicon schemas
    'schema_validation' => env('ATP_SCHEMA_VALIDATION', false),

    // 'strict' throws MissingScopeException, 'permissive' logs a warning
    'scope_enforcement' => ScopeEnforcementLevel::tryFrom(
        env('ATP_SCOPE_ENFORCEMENT', 'permissive')
    ) ?? ScopeEnforcementLevel::Permissive,

    // AtpScope facade and atp.scope middleware: 'abort', 'redirect' or 'exception'
    'scope_authorization' => [
        'failure_action' => ScopeAuthorizationFailure::tryFrom(
            env('ATP_SCOPE_FAILURE_ACTION', 'abort')
        ) ?? ScopeAuthorizationFailure::Abort,
        'redirect_to' => env('ATP_SCOPE_REDIRECT', '/login'),
    ],

    // Paths for make:atp-client and make:atp-request
    'generators' => [
        'client_path' => 'app/Services/Clients',
        'client_public_path' => 'app/Services/Clients/Public',
        'request_path' => 'app/Services/Clients/Requests',
        'request_public_path' => 'app/Services/Clients/Public/Requests',
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
ATP_OAUTH_SCOPE="atproto transition:generic"
ATP_CREDENTIAL_PROVIDER="SocialDept\\AtpClient\\Providers\\CacheCredentialProvider"
ATP_REFRESH_THRESHOLD=300
ATP_HTTP_TIMEOUT=30

# Optional: Override client_id and jwks_uri for external OAuth brokers
ATP_CLIENT_ID="https://broker.example.com/clients/your-client-id"
ATP_CLIENT_JWKS_URI="https://broker.example.com/clients/your-client-id/jwks"

# Optional: Customize endpoint paths (defaults to AT Protocol standard)
ATP_OAUTH_CLIENT_METADATA_PATH="/oauth-client-metadata.json"
ATP_OAUTH_JWKS_PATH="/oauth-jwks.json"
```

`ATP_OAUTH_SCOPE` is a space-separated list of the scopes your app asks for. It sets both the scopes requested at sign-in and the `scope` your client metadata document advertises, so the two always match. Configs published before 0.3.14 keep a fixed `client.scopes` list and read the env into `oauth.scope` instead. They still work: a set `ATP_OAUTH_SCOPE` wins, otherwise the published list is used.

The `ATP_OAUTH_KID` is the Key ID used in your JWKS endpoint. Some developers may require this to match a specific value. The default is `atp-client-key`.

When using an external OAuth broker, set `ATP_CLIENT_ID` and `ATP_CLIENT_JWKS_URI` to override the auto-generated URLs.

### Local Development

Authorization servers fetch client metadata from a public HTTPS URL and refuse redirects to local domains such as `.test`, so a local app cannot host its own client. A stateless client metadata broker such as [proto.broker](https://proto.broker) hosts it for you:

```bash
php artisan atp-client:dev-client
```

This builds a confidential client whose public key, callback and scopes are encoded in the client id itself, then writes `ATP_CLIENT_ID`, `ATP_CLIENT_REDIRECT_URI`, `ATP_OAUTH_SCOPE`, `ATP_OAUTH_PRIVATE_KEY` and `ATP_OAUTH_KID` to `.env`. Nothing is registered anywhere, and the private key never leaves your machine.

- The callback defaults to the `oauth.callback` route, or `APP_URL/oauth/callback`. Override it with `--callback`.
- Re-running reuses the key in `.env`, so the client id stays the same. Pass `--fresh` for a new key.
- `--public` creates a public client with no signing key, and removes `ATP_OAUTH_PRIVATE_KEY` and `ATP_OAUTH_KID` from `.env`.
- The scopes come from `ATP_OAUTH_SCOPE` and are encoded in the client id, so re-run the command after changing them.
- `--broker` points at a self-hosted broker. `--dry-run` prints the values instead of writing them.
- The command refuses to run in production unless you pass `--force`.
