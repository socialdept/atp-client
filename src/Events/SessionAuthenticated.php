<?php

namespace SocialDept\AtpClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Enums\AuthType;

class SessionAuthenticated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AccessToken $token,
    ) {
    }

    public function isOAuth(): bool
    {
        return $this->token->authType === AuthType::OAuth;
    }

    public function isLegacy(): bool
    {
        return $this->token->authType === AuthType::Legacy;
    }
}
