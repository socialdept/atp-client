<?php

namespace SocialDept\AtpClient\Crypto;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Stringable;

class JsonWebKeySet implements Arrayable, Jsonable, Stringable
{
    /**
     * @var array<JsonWebKey>
     */
    protected array $keys = [];

    /**
     * Load default JWKS from OAuthKey
     */
    public static function load(): static
    {
        $key = \SocialDept\AtpClient\Auth\OAuthKey::load();
        $kid = config('client.oauth.kid', 'key-1');

        return (new static)->addKey($key->toJWK()->withKid($kid)->asPublic());
    }

    /**
     * Add a key to the set
     */
    public function addKey(JsonWebKey $key): static
    {
        $this->keys[] = $key;

        return $this;
    }

    /**
     * Convert to array (JWKS format)
     */
    public function toArray(): array
    {
        return [
            'keys' => collect($this->keys)->map(fn (JsonWebKey $key) => $key->toArray())->toArray(),
        ];
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
