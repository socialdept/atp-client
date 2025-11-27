<?php

namespace SocialDept\AtpClient\Enums;

enum ScopeEnforcementLevel: string
{
    case Strict = 'strict';
    case Permissive = 'permissive';
}
