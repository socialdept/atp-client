<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum AtprotoServer: string
{
    use HasScopeHelpers;
    case CreateSession = 'com.atproto.server.createSession';
    case RefreshSession = 'com.atproto.server.refreshSession';
    case GetSession = 'com.atproto.server.getSession';
    case DescribeServer = 'com.atproto.server.describeServer';
}
