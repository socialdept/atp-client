<?php

namespace SocialDept\AtpClient\Data\Responses;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Response class for endpoints that return empty objects.
 *
 * @implements Arrayable<string, mixed>
 */
class EmptyResponse implements Arrayable
{
    public function __construct() {}

    public static function fromArray(array $data): self
    {
        return new self;
    }

    public function toArray(): array
    {
        return [];
    }
}
