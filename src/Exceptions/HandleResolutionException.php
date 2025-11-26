<?php

namespace SocialDept\AtpClient\Exceptions;

class HandleResolutionException extends \Exception
{
    public function __construct(string $handle)
    {
        parent::__construct("Unable to resolve handle '{$handle}' to a DID");
    }
}
