<?php

namespace SocialDept\AtpClient\Enums\Nsid;

enum ChatActor: string
{
    case GetActorMetadata = 'chat.bsky.actor.getActorMetadata';
    case ExportAccountData = 'chat.bsky.actor.exportAccountData';
    case DeleteAccount = 'chat.bsky.actor.deleteAccount';
}
