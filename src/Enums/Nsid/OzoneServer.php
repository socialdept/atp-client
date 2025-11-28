<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum OzoneServer: string
{
    use HasScopeHelpers;
    case GetBlob = 'tools.ozone.server.getBlob';
    case GetConfig = 'tools.ozone.server.getConfig';
}
