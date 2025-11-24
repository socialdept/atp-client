<?php

namespace SocialDept\AtpClient\Facades;

use Illuminate\Support\Facades\Facade;

class AtpClient extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'atp-client';
    }
}
