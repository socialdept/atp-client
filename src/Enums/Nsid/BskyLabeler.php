<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum BskyLabeler: string
{
    use HasScopeHelpers;
    case GetServices = 'app.bsky.labeler.getServices';
}
