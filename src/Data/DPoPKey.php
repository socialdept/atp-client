<?php

namespace SocialDept\AtpClient\Data;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;

class DPoPKey
{
    public function __construct(
        public readonly PrivateKey $privateKey,
        public readonly PublicKey $publicKey,
        public readonly string $keyId,
    ) {}

    public function getPublicJwk(): array
    {
        $jwks = json_decode($this->publicKey->toString('JWK'), true);

        // phpseclib returns JWKS format {"keys":[...]}, extract the first key
        $jwk = $jwks['keys'][0] ?? $jwks;

        return array_merge(
            $jwk,
            [
                'alg' => 'ES256',
                'use' => 'sig',
                'kid' => $this->keyId,
            ]
        );
    }

    public function getPrivateJwk(): array
    {
        $jwks = json_decode($this->privateKey->toString('JWK'), true);

        // phpseclib returns JWKS format {"keys":[...]}, extract the first key
        $jwk = $jwks['keys'][0] ?? $jwks;

        return array_merge(
            $jwk,
            [
                'alg' => 'ES256',
                'use' => 'sig',
                'kid' => $this->keyId,
            ]
        );
    }

    public function toPEM(): string
    {
        return $this->privateKey->toString('PKCS8');
    }
}
