<?php

namespace SocialDept\AtpClient\Client\Requests\Ozone;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Ozone\Server\GetConfigResponse;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;

class ServerRequestClient extends Request
{
    /**
     * Get blob (returns binary data)
     *
     * @requires transition:generic (rpc:tools.ozone.server.getBlob)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-server-get-config
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.server.getBlob')]
    public function getBlob(string $did, string $cid): Response
    {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.server.getBlob',
            params: compact('did', 'cid')
        );
    }

    /**
     * Get config
     *
     * @requires transition:generic (rpc:tools.ozone.server.getConfig)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-server-get-config
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.server.getConfig')]
    public function getConfig(): GetConfigResponse
    {
        $response = $this->atp->client->get(
            endpoint: 'tools.ozone.server.getConfig'
        );

        return GetConfigResponse::fromArray($response->json());
    }
}
