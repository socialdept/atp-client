<?php

namespace SocialDept\AtpClient\Auth;

use InvalidArgumentException;
use SocialDept\AtpClient\Crypto\P256;

class OAuthKey extends P256
{
    /**
     * Load OAuth key from configuration
     */
    public static function load(?string $private = null): static
    {
        $private ??= config('client.oauth.private_key');

        throw_if(empty($private), InvalidArgumentException::class, 'OAuth private key not configured. Run: php artisan atp-client:generate-key');

        return parent::load($private);
    }
}
