<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Server;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class GetSessionResponse implements Arrayable
{
    public function __construct(
        public readonly string $handle,
        public readonly string $did,
        public readonly ?string $email = null,
        public readonly ?bool $emailConfirmed = null,
        public readonly ?bool $emailAuthFactor = null,
        public readonly mixed $didDoc = null,
        public readonly ?bool $active = null,
        public readonly ?string $status = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            handle: $data['handle'],
            did: $data['did'],
            email: $data['email'] ?? null,
            emailConfirmed: $data['emailConfirmed'] ?? null,
            emailAuthFactor: $data['emailAuthFactor'] ?? null,
            didDoc: $data['didDoc'] ?? null,
            active: $data['active'] ?? null,
            status: $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'handle' => $this->handle,
            'did' => $this->did,
            'email' => $this->email,
            'emailConfirmed' => $this->emailConfirmed,
            'emailAuthFactor' => $this->emailAuthFactor,
            'didDoc' => $this->didDoc,
            'active' => $this->active,
            'status' => $this->status,
        ];
    }
}
