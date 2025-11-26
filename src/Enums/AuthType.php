<?php

namespace SocialDept\AtpClient\Enums;

enum AuthType: string
{
    case OAuth = 'oauth';
    case Legacy = 'legacy';
}
