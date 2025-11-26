<?php

namespace SocialDept\AtpClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OAuthTokenRefreshing
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $did,
        public readonly string $refreshToken,
    ) {}
}
