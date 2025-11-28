<?php

namespace SocialDept\AtpClient\Enums\Nsid;

enum AtprotoServer: string
{
    case CreateSession = 'com.atproto.server.createSession';
    case RefreshSession = 'com.atproto.server.refreshSession';
    case GetSession = 'com.atproto.server.getSession';
    case DescribeServer = 'com.atproto.server.describeServer';
}
