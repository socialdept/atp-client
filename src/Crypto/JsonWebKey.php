<?php

namespace SocialDept\AtpClient\Crypto;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\EC\PublicKey;
use Stringable;

class JsonWebKey implements Arrayable, Jsonable, Stringable
{
    protected PrivateKey|PublicKey $key;

    protected string $kid = 'key-1';

    public function __construct(PrivateKey|PublicKey $key)
    {
        $this->key = $key;
    }

    /**
     * Get the underlying key
     */
    public function key(): PrivateKey|PublicKey
    {
        return $this->key;
    }

    /**
     * Get public key version
     */
    public function asPublic(): static
    {
        if ($this->key instanceof PrivateKey) {
            $clone = clone $this;
            $clone->key = $this->key->getPublicKey();

            return $clone;
        }

        return $this;
    }

    /**
     * Get key ID
     */
    public function kid(): string
    {
        return $this->kid;
    }

    /**
     * Set key ID
     */
    public function withKid(string $kid): static
    {
        $this->kid = $kid;

        return $this;
    }

    /**
     * Export as PEM
     */
    public function toPEM(): string
    {
        return $this->key->toString('PKCS8');
    }

    /**
     * Convert to array (JWK format)
     */
    public function toArray(): array
    {
        $publicKey = $this->key instanceof PrivateKey
            ? $this->key->getPublicKey()
            : $this->key;

        $jwk = $publicKey->toString('JWK');

        return array_merge(
            json_decode($jwk, true),
            [
                'alg' => P256::ALG,
                'use' => 'sig',
                'kid' => $this->kid(),
            ]
        );
    }

    /**
     * Convert to JSON
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $options);
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
