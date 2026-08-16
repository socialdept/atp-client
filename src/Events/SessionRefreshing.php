<?php

namespace SocialDept\AtpClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SocialDept\AtpClient\Session\Session;

class SessionRefreshing
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Session $session,
    ) {
    }
}
