<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum OzoneModeration: string
{
    use HasScopeHelpers;
    case GetEvent = 'tools.ozone.moderation.getEvent';
    case GetEvents = 'tools.ozone.moderation.getEvents';
    case GetRecord = 'tools.ozone.moderation.getRecord';
    case GetRepo = 'tools.ozone.moderation.getRepo';
    case QueryEvents = 'tools.ozone.moderation.queryEvents';
    case QueryStatuses = 'tools.ozone.moderation.queryStatuses';
    case SearchRepos = 'tools.ozone.moderation.searchRepos';
    case EmitEvent = 'tools.ozone.moderation.emitEvent';
}
