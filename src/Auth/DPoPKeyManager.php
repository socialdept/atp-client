<?php

namespace SocialDept\AtpClient\Auth;

use Firebase\JWT\JWT;
use phpseclib3\Crypt\EC;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\DPoPKey;

class DPoPKeyManager
{
    public function __construct(
        protected KeyStore $keyStore
    ) {
    }

    /**
     * Generate new ES256 key pair
     */
    public function generateKey(string $sessionId): DPoPKey
    {
        // Generate P-256 elliptic curve key pair
        $privateKey = EC::createKey('secp256r1');
        $publicKey = $privateKey->getPublicKey();
        $keyId = $this->generateKeyId($publicKey);

        $dpopKey = new DPoPKey($privateKey, $publicKey, $keyId);

        // Store the key
        $this->keyStore->store($sessionId, $dpopKey);

        return $dpopKey;
    }

    /**
     * Create DPoP proof JWT
     */
    public function createProof(
        DPoPKey $key,
        string $method,
        string $url,
        string $nonce = '',
        ?string $accessToken = null
    ): string {
        $now = time();

        $payload = [
            'jti' => bin2hex(random_bytes(16)),
            'htm' => $method,
            'htu' => $url,
            'iat' => $now,
            'exp' => $now + 60, // 1 minute validity
        ];

        // Only include nonce if provided (first request may not have one)
        if ($nonce !== '') {
            $payload['nonce'] = $nonce;
        }

        if ($accessToken) {
            $payload['ath'] = $this->hashAccessToken($accessToken);
        }

        $header = [
            'typ' => 'dpop+jwt',
            'alg' => 'ES256',
            'jwk' => $key->getPublicJwk(),
        ];

        return JWT::encode(
            payload: $payload,
            key: $key->toPEM(),
            alg: 'ES256',
            head: $header
        );
    }

    /**
     * Hash access token for DPoP proof
     */
    protected function hashAccessToken(string $token): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $token, true)), '+/', '-_'), '=');
    }

    /**
     * Generate key ID from public key
     */
    protected function generateKeyId($publicKey): string
    {
        $jwk = $publicKey->toString('JWK');

        return hash('sha256', $jwk);
    }
}
