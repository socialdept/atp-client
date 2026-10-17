<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Server;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A service auth token minted by the user's PDS.
 *
 * The claims are read from the token without verifying it. That is fine for
 * the caller, which received it straight from its own PDS: the helpers exist
 * to cache the token until it expires, not to trust it. The receiving service
 * verifies it.
 *
 * @implements Arrayable<string, mixed>
 */
class GetServiceAuthResponse implements Arrayable
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $claims = null;

    public function __construct(
        public readonly string $token,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
        );
    }

    /**
     * When the token expires, as a Unix timestamp.
     */
    public function expiresAt(): ?int
    {
        $exp = $this->claims()['exp'] ?? null;

        return is_int($exp) ? $exp : null;
    }

    /**
     * The service the token is addressed to.
     */
    public function audience(): ?string
    {
        $aud = $this->claims()['aud'] ?? null;

        return is_string($aud) ? $aud : null;
    }

    /**
     * The method the token is bound to, or null when it is unbound.
     */
    public function method(): ?string
    {
        $lxm = $this->claims()['lxm'] ?? null;

        return is_string($lxm) ? $lxm : null;
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function claims(): array
    {
        if ($this->claims !== null) {
            return $this->claims;
        }

        $payload = explode('.', $this->token)[1] ?? '';
        $decoded = json_decode((string) base64_decode(strtr($payload, '-_', '+/'), true), true);

        return $this->claims = is_array($decoded) ? $decoded : [];
    }
}
