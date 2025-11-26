<?php

namespace SocialDept\AtpClient\Http\Config;

use Closure;
use SocialDept\AtpClient\Crypto\JsonWebKeySet;

class OAuthMetadata
{
    protected static ?Closure $clientMetadataUsing = null;

    protected static ?Closure $jwksUsing = null;

    /**
     * Set custom callback for client metadata
     */
    public static function clientMetadataUsing(?Closure $callback): void
    {
        static::$clientMetadataUsing = $callback;
    }

    /**
     * Get client metadata as array
     */
    public static function clientMetadata(): array
    {
        // Get custom metadata from callback if set
        if (static::$clientMetadataUsing) {
            $stored = call_user_func(static::$clientMetadataUsing);
        } else {
            $stored = config('client.oauth.client_metadata', []);
        }

        // Base metadata that should always be present
        $base = [
            'client_id' => route('atp.oauth.client-metadata'),
            'jwks_uri' => route('atp.oauth.jwks'),
            'redirect_uris' => config('client.client.redirect_uris', []),
            'scope' => config('client.oauth.scope', 'atproto transition:generic'),
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'private_key_jwt',
            'token_endpoint_auth_signing_alg' => 'ES256',
            'application_type' => 'web',
            'dpop_bound_access_tokens' => true,
        ];

        // Merge and filter out null values
        return array_filter(array_merge($stored, $base), fn ($value) => ! is_null($value));
    }

    /**
     * Set custom callback for JWKS
     */
    public static function jwksUsing(?Closure $callback): void
    {
        static::$jwksUsing = $callback;
    }

    /**
     * Get JWKS as array
     */
    public static function jwks(): array
    {
        if (static::$jwksUsing) {
            return call_user_func(static::$jwksUsing);
        }

        return JsonWebKeySet::load()->toArray();
    }
}
