<?php

namespace SocialDept\AtpClient\Auth;

use Firebase\JWT\JWT;

class ClientAssertionManager
{
    public function __construct(
        protected ClientMetadataManager $metadata,
    ) {}

    /**
     * Check if client assertion is required (private key is configured)
     */
    public function isRequired(): bool
    {
        return ! empty(config('atp-client.oauth.private_key'));
    }

    /**
     * Create a client assertion JWT for private_key_jwt authentication
     */
    public function createAssertion(string $audience): string
    {
        $key = OAuthKey::load();
        $now = time();

        $payload = [
            'iss' => $this->metadata->getClientId(),
            'sub' => $this->metadata->getClientId(),
            'aud' => $audience,
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + 60,
        ];

        $header = [
            'alg' => 'ES256',
            'kid' => config('atp-client.oauth.kid', 'atp-client-key'),
            'typ' => 'JWT',
        ];

        return JWT::encode(
            payload: $payload,
            key: $key->toPEM(),
            alg: 'ES256',
            head: $header
        );
    }

    /**
     * Get the client assertion type for OAuth requests
     */
    public function getAssertionType(): string
    {
        return 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
    }

    /**
     * Get client authentication parameters for OAuth requests
     */
    public function getAuthParams(string $audience): array
    {
        if (! $this->isRequired()) {
            return [
                'client_id' => $this->metadata->getClientId(),
            ];
        }

        return [
            'client_id' => $this->metadata->getClientId(),
            'client_assertion_type' => $this->getAssertionType(),
            'client_assertion' => $this->createAssertion($audience),
        ];
    }
}
