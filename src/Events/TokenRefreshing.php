<?php

namespace SocialDept\AtpClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenRefreshing
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $identifier,
        public readonly string $refreshToken,
    ) {}
}
