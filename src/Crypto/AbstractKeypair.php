<?php

namespace SocialDept\AtpClient\Crypto;

use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\EC\PublicKey;
use phpseclib3\Crypt\PublicKeyLoader;

abstract class AbstractKeypair
{
    public const CURVE = '';

    public const ALG = '';

    public const MULTIBASE_PREFIX = '';

    protected PrivateKey|PublicKey $key;

    /**
     * Create a new keypair
     */
    public static function create(): static
    {
        $self = new static();
        $self->key = EC::createKey(static::CURVE);

        return $self;
    }

    /**
     * Load keypair from base64-encoded private key
     */
    public static function load(?string $private = null): static
    {
        $self = new static();
        $self->key = PublicKeyLoader::load(
            base64_decode(strtr($private, '-_', '+/'), strict: true)
        );

        return $self;
    }

    /**
     * Get the key instance
     */
    public function key(): PrivateKey|PublicKey
    {
        return $this->key;
    }

    /**
     * Export private key as base64-encoded string
     */
    public function privateB64(): string
    {
        return strtr(base64_encode($this->key->toString('PKCS8')), '+/', '-_');
    }

    /**
     * Export private key as PEM string
     */
    public function toPEM(): string
    {
        return $this->key->toString('PKCS8');
    }

    /**
     * Convert to JSON Web Key format
     */
    public function toJWK(): JsonWebKey
    {
        return new JsonWebKey($this->key);
    }
}
