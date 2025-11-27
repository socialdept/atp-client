<?php

namespace SocialDept\AtpClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SocialDept\AtpClient\Data\AccessToken;

class LegacyUserAuthenticated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AccessToken $token,
    ) {}
}
