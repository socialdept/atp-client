<?php

namespace SocialDept\AtpClient\Enums;

enum ScopeAuthorizationFailure: string
{
    case Abort = 'abort';
    case Redirect = 'redirect';
    case Exception = 'exception';
}
