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
        $jwk = $this->publicKey->toString('JWK');

        return array_merge(
            json_decode($jwk, true),
            [
                'alg' => 'ES256',
                'use' => 'sig',
                'kid' => $this->keyId,
            ]
        );
    }

    public function getPrivateJwk(): array
    {
        $jwk = $this->privateKey->toString('JWK');

        return array_merge(
            json_decode($jwk, true),
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
