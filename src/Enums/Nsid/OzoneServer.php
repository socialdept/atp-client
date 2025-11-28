<?php

namespace SocialDept\AtpClient\Enums\Nsid;

enum OzoneServer: string
{
    case GetBlob = 'tools.ozone.server.getBlob';
    case GetConfig = 'tools.ozone.server.getConfig';
}
