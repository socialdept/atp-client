<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum OzoneTeam: string
{
    use HasScopeHelpers;
    case GetMember = 'tools.ozone.team.getMember';
    case ListMembers = 'tools.ozone.team.listMembers';
    case AddMember = 'tools.ozone.team.addMember';
    case UpdateMember = 'tools.ozone.team.updateMember';
    case DeleteMember = 'tools.ozone.team.deleteMember';
}
