<?php

namespace SocialDept\AtpClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Session\Session;

class SessionUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Session $session,
        public readonly AccessToken $token,
    ) {}
}
