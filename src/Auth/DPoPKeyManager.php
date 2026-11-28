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
        $dpopKey = $this->ephemeralKey();

        // Store the key
        $this->keyStore->store($sessionId, $dpopKey);

        return $dpopKey;
    }

    /**
     * A P-256 key that is not stored, for sessions that never prove
     * possession, such as password sessions.
     */
    public function ephemeralKey(): DPoPKey
    {
        $privateKey = EC::createKey('secp256r1');
        $publicKey = $privateKey->getPublicKey();

        return new DPoPKey($privateKey, $publicKey, $this->generateKeyId($publicKey));
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
