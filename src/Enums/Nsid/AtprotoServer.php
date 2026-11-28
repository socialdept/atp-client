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
    case GetServiceAuth = 'com.atproto.server.getServiceAuth';
    case DeleteSession = 'com.atproto.server.deleteSession';
    case CreateAppPassword = 'com.atproto.server.createAppPassword';
    case ListAppPasswords = 'com.atproto.server.listAppPasswords';
    case RevokeAppPassword = 'com.atproto.server.revokeAppPassword';
    case RequestEmailConfirmation = 'com.atproto.server.requestEmailConfirmation';
    case ConfirmEmail = 'com.atproto.server.confirmEmail';
    case RequestEmailUpdate = 'com.atproto.server.requestEmailUpdate';
    case UpdateEmail = 'com.atproto.server.updateEmail';
    case RequestAccountDelete = 'com.atproto.server.requestAccountDelete';
    case DeleteAccount = 'com.atproto.server.deleteAccount';
    case RequestPasswordReset = 'com.atproto.server.requestPasswordReset';
    case ResetPassword = 'com.atproto.server.resetPassword';
}
