<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Server;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class DescribeServerResponse implements Arrayable
{
    /**
     * @param  array<string>  $availableUserDomains
     */
    public function __construct(
        public readonly string $did,
        public readonly array $availableUserDomains,
        public readonly ?bool $inviteCodeRequired = null,
        public readonly ?bool $phoneVerificationRequired = null,
        public readonly ?array $links = null,
        public readonly ?array $contact = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            did: $data['did'],
            availableUserDomains: $data['availableUserDomains'] ?? [],
            inviteCodeRequired: $data['inviteCodeRequired'] ?? null,
            phoneVerificationRequired: $data['phoneVerificationRequired'] ?? null,
            links: $data['links'] ?? null,
            contact: $data['contact'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'did' => $this->did,
            'availableUserDomains' => $this->availableUserDomains,
            'inviteCodeRequired' => $this->inviteCodeRequired,
            'phoneVerificationRequired' => $this->phoneVerificationRequired,
            'links' => $this->links,
            'contact' => $this->contact,
        ];
    }
}
