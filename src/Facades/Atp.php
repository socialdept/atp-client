<?php

namespace SocialDept\AtpClient\Facades;

use Illuminate\Support\Facades\Facade;
use SocialDept\AtpClient\Auth\OAuthEngine;
use SocialDept\AtpClient\Client\AtpClient;
use SocialDept\AtpClient\Contracts\CredentialProvider;

/**
 * @method static AtpClient as(string $identifier)
 * @method static AtpClient login(string $identifier, string $password)
 * @method static OAuthEngine oauth()
 * @method static void setDefaultProvider(CredentialProvider $provider)
 *
 * @see \SocialDept\AtpClient\AtpClientServiceProvider
 */
class Atp extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'atp-client';
    }
}
