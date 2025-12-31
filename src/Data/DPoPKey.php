<?php

namespace SocialDept\AtpClient\Data;

use Illuminate\Contracts\Support\Arrayable;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Crypt\PublicKeyLoader;

class DPoPKey implements Arrayable
{
    protected string $privateKeyPem;

    protected string $publicKeyPem;

    public function __construct(
        PrivateKey|string $privateKey,
        PublicKey|string $publicKey,
        public readonly string $keyId,
    ) {
        // Store as PEM strings for serialization
        $this->privateKeyPem = $privateKey instanceof PrivateKey
            ? $privateKey->toString('PKCS8')
            : $privateKey;

        $this->publicKeyPem = $publicKey instanceof PublicKey
            ? $publicKey->toString('PKCS8')
            : $publicKey;
    }

    public function getPrivateKey(): PrivateKey
    {
        return PublicKeyLoader::load($this->privateKeyPem);
    }

    public function getPublicKey(): PublicKey
    {
        return PublicKeyLoader::load($this->publicKeyPem);
    }

    public function getPublicJwk(): array
    {
        $jwks = json_decode($this->getPublicKey()->toString('JWK'), true);

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
        $jwks = json_decode($this->getPrivateKey()->toString('JWK'), true);

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
        return $this->privateKeyPem;
    }

    public function toArray(): array
    {
        return [
            'privateKeyPem' => $this->privateKeyPem,
            'publicKeyPem' => $this->publicKeyPem,
            'keyId' => $this->keyId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            privateKey: $data['privateKeyPem'],
            publicKey: $data['publicKeyPem'],
            keyId: $data['keyId'],
        );
    }
}
