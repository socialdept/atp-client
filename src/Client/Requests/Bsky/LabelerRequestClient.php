<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Bsky\Labeler\GetServicesResponse;
use SocialDept\AtpClient\Enums\Nsid\BskyLabeler;

class LabelerRequestClient extends Request
{
    /**
     * Get labeler services
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-labeler-get-services
     */
    public function getServices(array $dids, bool $detailed = false): GetServicesResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyLabeler::GetServices,
            params: compact('dids', 'detailed')
        );

        return GetServicesResponse::fromArray($response->json(), $detailed);
    }
}
