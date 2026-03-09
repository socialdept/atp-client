<?php

namespace SocialDept\AtpClient\Facades;

use BackedEnum;
use Illuminate\Support\Facades\Facade;
use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Auth\OAuthEngine;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Http\Response;
use SocialDept\AtpClient\Testing\FakeAtpManager;
use SocialDept\AtpClient\Testing\ResponseSequence;

/**
 * @method static AtpClient as(string $actor)
 * @method static AtpClient login(string $actor, string $password)
 * @method static OAuthEngine oauth()
 * @method static AtpClient public(?string $service = null)
 * @method static void setDefaultProvider(CredentialProvider $provider)
 *
 * @see \SocialDept\AtpClient\AtpClientServiceProvider
 */
class Atp extends Facade
{
    /**
     * Replace the facade root with a fake for testing.
     *
     * @param  array<string|BackedEnum, Response|array|\Closure|ResponseSequence>  $stubs  Keyed by XRPC endpoint NSID or BackedEnum
     */
    public static function fake(array $stubs = []): FakeAtpManager
    {
        $fake = new FakeAtpManager($stubs);

        static::swap($fake);

        return $fake;
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'atp-client';
    }
}
