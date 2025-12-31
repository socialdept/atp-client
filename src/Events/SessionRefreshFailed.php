<?php

namespace SocialDept\AtpClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SocialDept\AtpClient\Session\Session;
use Throwable;

class SessionRefreshFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Session $session,
        public readonly Throwable $exception,
        public readonly string $reason,
    ) {}
}
