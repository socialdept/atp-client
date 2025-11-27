<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Bsky;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Http\Response;

class LabelerPublicRequestClient extends PublicRequest
{
    public function getServices(array $dids, bool $detailed = false): Response
    {
        return $this->atp->client->get('app.bsky.labeler.getServices', compact('dids', 'detailed'));
    }
}
