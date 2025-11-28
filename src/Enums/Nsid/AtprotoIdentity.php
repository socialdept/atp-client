<?php

namespace SocialDept\AtpClient\Enums\Nsid;

enum AtprotoIdentity: string
{
    case ResolveHandle = 'com.atproto.identity.resolveHandle';
    case UpdateHandle = 'com.atproto.identity.updateHandle';
}
