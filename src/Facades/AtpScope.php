<?php

namespace SocialDept\AtpClient\Facades;

use Illuminate\Support\Facades\Facade;
use SocialDept\AtpClient\Auth\ScopeGate;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Session\Session;

/**
 * @method static ScopeGate forSession(Session $session)
 * @method static ScopeGate forUser(string $handleOrDid)
 * @method static bool can(string|Scope $scope)
 * @method static bool canAny(array $scopes)
 * @method static bool canAll(array $scopes)
 * @method static bool cannot(string|Scope $scope)
 * @method static void authorize(string|Scope ...$scopes)
 * @method static array granted()
 *
 * @see \SocialDept\AtpClient\Auth\ScopeGate
 */
class AtpScope extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'atp-scope';
    }
}
