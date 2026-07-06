<?php

namespace SocialDept\AtpClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SocialDept\AtpClient\Enums\RefreshFailureReason;
use Throwable;

/**
 * A session was found terminally invalid before a refresh could run (e.g. the
 * DPoP key backing an OAuth grant is gone, or no credentials exist). No
 * {@see Session} object exists at this point, so {@see SessionRefreshFailed}
 * cannot fire — listeners key off this to flag the account for reconnect.
 */
class SessionInvalid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $did,
        public readonly ?RefreshFailureReason $reason = null,
        public readonly ?Throwable $exception = null,
    ) {}
}
