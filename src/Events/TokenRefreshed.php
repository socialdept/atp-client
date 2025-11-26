<?php

namespace SocialDept\AtpClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SocialDept\AtpClient\Data\AccessToken;

class TokenRefreshed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $did,
        public readonly AccessToken $token,
    ) {}
}
