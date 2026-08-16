<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Team;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class MemberResponse implements Arrayable
{
    public function __construct(
        public readonly string $did,
        public readonly bool $disabled,
        public readonly ?string $role = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $lastUpdatedBy = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            did: $data['did'],
            disabled: $data['disabled'] ?? false,
            role: $data['role'] ?? null,
            createdAt: $data['createdAt'] ?? null,
            updatedAt: $data['updatedAt'] ?? null,
            lastUpdatedBy: $data['lastUpdatedBy'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'did' => $this->did,
            'disabled' => $this->disabled,
            'role' => $this->role,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'lastUpdatedBy' => $this->lastUpdatedBy,
        ], fn ($v) => $v !== null);
    }
}
