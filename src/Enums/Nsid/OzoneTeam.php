<?php

namespace SocialDept\AtpClient\Enums\Nsid;

enum OzoneTeam: string
{
    case GetMember = 'tools.ozone.team.getMember';
    case ListMembers = 'tools.ozone.team.listMembers';
    case AddMember = 'tools.ozone.team.addMember';
    case UpdateMember = 'tools.ozone.team.updateMember';
    case DeleteMember = 'tools.ozone.team.deleteMember';
}
