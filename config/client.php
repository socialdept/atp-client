<?php

use SocialDept\AtpClient\Enums\ScopeAuthorizationFailure;
use SocialDept\AtpClient\Enums\ScopeEnforcementLevel;

return [
    /*
    |--------------------------------------------------------------------------
    | Client Configuration
    |--------------------------------------------------------------------------
    |
    | OAuth client configuration. The client_id is a URL that serves as the
    | unique identifier for your OAuth client. In production, this must be
    | an HTTPS URL pointing to your publicly accessible client metadata.
    |
    | For local development, use 'http://localhost' (no port) as the client_id.
    | The redirect_uri for localhost must use 127.0.0.1 with a port.
    |
    | @see https://atproto.com/specs/oauth#clients
    |
    */
    'client' => [
        'name' => env('ATP_CLIENT_NAME', config('app.name')),
        'url' => env('ATP_CLIENT_URL', config('app.url')),

        // The client_id is the URL to your client metadata document.
        // For production: 'https://example.com/oauth/client-metadata.json'
        // For localhost:  'http://localhost' (exactly, no port)
        'client_id' => env('ATP_CLIENT_ID'),

        // Redirect URIs for OAuth callback.
        // For localhost development, use 'http://127.0.0.1:<port>/callback'
        'redirect_uris' => array_filter([
            env('ATP_CLIENT_REDIRECT_URI'),
        ]),

        'scopes' => ['atproto', 'transition:generic'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Credential Provider
    |--------------------------------------------------------------------------
    |
    | The credential provider handles storage and retrieval of OAuth tokens.
    | You can use the provided implementations or create your own.
    |
    */
    'credential_provider' => env(
        'ATP_CREDENTIAL_PROVIDER',
        \SocialDept\AtpClient\Providers\ArrayCredentialProvider::class
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Settings
    |--------------------------------------------------------------------------
    |
    | Configure session behavior including token refresh threshold and
    | DPoP key rotation interval.
    |
    */
    'session' => [
        // Refresh token if expires within this many seconds
        'refresh_threshold' => env('ATP_REFRESH_THRESHOLD', 300),

        // Rotate DPoP keys after this many seconds
        'dpop_key_rotation' => env('ATP_DPOP_KEY_ROTATION', 86400),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | OAuth 2.0 settings for AT Protocol authentication. The private key is
    | used for signing client assertions. Generate a key with:
    | php artisan atp-client:generate-key
    |
    | The metadata endpoints are automatically available at:
    | - GET /atp/oauth/client-metadata.json
    | - GET /atp/oauth/jwks.json
    | - GET /.well-known/oauth-client-metadata
    |
    */
    'oauth' => [
        'disabled' => env('ATP_OAUTH_DISABLED', false),
        'prefix' => env('ATP_OAUTH_PREFIX', '/atp/oauth/'),
        'private_key' => env('ATP_OAUTH_PRIVATE_KEY'),
        'kid' => env('ATP_OAUTH_KID', 'atp-client-key'),
        'scope' => env('ATP_OAUTH_SCOPE', 'atproto transition:generic'),

        'client_metadata' => [
            'client_name' => env('ATP_CLIENT_NAME', config('app.name')),
            'client_uri' => env('ATP_CLIENT_URL', config('app.url')),
            'logo_uri' => env('ATP_CLIENT_LOGO_URI'),
            'tos_uri' => env('ATP_CLIENT_TOS_URI'),
            'policy_uri' => env('ATP_CLIENT_POLICY_URI'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Settings
    |--------------------------------------------------------------------------
    |
    | Configure HTTP client behavior for XRPC requests.
    |
    */
    'http' => [
        'timeout' => env('ATP_HTTP_TIMEOUT', 30),
        'retry' => [
            'times' => env('ATP_HTTP_RETRY_TIMES', 3),
            'sleep' => env('ATP_HTTP_RETRY_SLEEP', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Validation
    |--------------------------------------------------------------------------
    |
    | Enable or disable response validation against AT Protocol lexicon schemas.
    | When enabled, responses are validated and ValidationException is thrown
    | if the response doesn't match the expected schema.
    |
    */
    'schema_validation' => env('ATP_SCHEMA_VALIDATION', false),

    /*
    |--------------------------------------------------------------------------
    | Public API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for unauthenticated public API access. The public API
    | allows reading public data without authentication.
    |
    */
    'public' => [
        'service_url' => env('ATP_PUBLIC_SERVICE_URL', 'https://public.api.bsky.app'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scope Enforcement
    |--------------------------------------------------------------------------
    |
    | Configure how scope requirements are enforced. Options:
    | - 'strict': Throws MissingScopeException if required scopes are missing
    | - 'permissive': Logs a warning but attempts the request anyway
    |
    */
    'scope_enforcement' => ScopeEnforcementLevel::tryFrom(
        env('ATP_SCOPE_ENFORCEMENT', 'permissive')
    ) ?? ScopeEnforcementLevel::Permissive,

    /*
    |--------------------------------------------------------------------------
    | Scope Authorization
    |--------------------------------------------------------------------------
    |
    | Configure behavior for the AtpScope facade and atp.scope middleware.
    |
    | failure_action: What happens when a scope check fails
    | - 'abort': Return a 403 HTTP response
    | - 'redirect': Redirect to the configured URL
    | - 'exception': Throw ScopeAuthorizationException
    |
    | redirect_to: URL to redirect to when failure_action is 'redirect'
    |
    */
    'scope_authorization' => [
        'failure_action' => ScopeAuthorizationFailure::tryFrom(
            env('ATP_SCOPE_FAILURE_ACTION', 'abort')
        ) ?? ScopeAuthorizationFailure::Abort,

        'redirect_to' => env('ATP_SCOPE_REDIRECT', '/login'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generator Settings
    |--------------------------------------------------------------------------
    |
    | Configure paths for the make:atp-client and make:atp-request commands.
    | Paths are relative to the application base path.
    |
    */
    'generators' => [
        'client_path' => 'app/Services/Clients',
        'request_path' => 'app/Services/Clients/Requests',
    ],
];
