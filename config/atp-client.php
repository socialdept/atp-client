<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Client Metadata
    |--------------------------------------------------------------------------
    |
    | OAuth client configuration. The metadata URL must be publicly accessible
    | and serve the client-metadata.json file.
    |
    */
    'client' => [
        'name' => env('ATP_CLIENT_NAME', config('app.name')),
        'url' => env('ATP_CLIENT_URL', config('app.url')),
        'metadata_url' => env('ATP_CLIENT_METADATA_URL'),
        'redirect_uris' => [
            env('ATP_CLIENT_REDIRECT_URI', config('app.url').'/auth/atp/callback'),
        ],
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
];