<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum ChatActor: string
{
    use HasScopeHelpers;
    case GetActorMetadata = 'chat.bsky.actor.getActorMetadata';
    case ExportAccountData = 'chat.bsky.actor.exportAccountData';
    case DeleteAccount = 'chat.bsky.actor.deleteAccount';
}
