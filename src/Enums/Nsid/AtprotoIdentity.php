<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum AtprotoIdentity: string
{
    use HasScopeHelpers;
    case ResolveHandle = 'com.atproto.identity.resolveHandle';
    case UpdateHandle = 'com.atproto.identity.updateHandle';
}
