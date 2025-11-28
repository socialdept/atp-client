<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Bsky;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Data\Responses\Bsky\Labeler\GetServicesResponse;
use SocialDept\AtpClient\Enums\Nsid\BskyLabeler;

class LabelerPublicRequestClient extends PublicRequest
{
    public function getServices(array $dids, bool $detailed = false): GetServicesResponse
    {
        $response = $this->atp->client->get(BskyLabeler::GetServices, compact('dids', 'detailed'));

        return GetServicesResponse::fromArray($response->json(), $detailed);
    }
}
