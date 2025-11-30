<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Attributes\PublicEndpoint;
use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\DescribeServerResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\GetSessionResponse;
use SocialDept\AtpClient\Enums\Nsid\AtprotoServer;
use SocialDept\AtpClient\Enums\Scope;

class ServerRequestClient extends Request
{
    /**
     * Get current session
     *
     * @requires atproto (rpc:com.atproto.server.getSession)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-get-session
     */
    #[ScopedEndpoint(Scope::Atproto, granular: 'rpc:com.atproto.server.getSession')]
    public function getSession(): GetSessionResponse
    {
        $response = $this->atp->client->get(
            endpoint: AtprotoServer::GetSession
        );

        return GetSessionResponse::fromArray($response->json());
    }

    /**
     * Describe server
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-describe-server
     */
    #[PublicEndpoint]
    public function describeServer(): DescribeServerResponse
    {
        $response = $this->atp->client->get(
            endpoint: AtprotoServer::DescribeServer
        );

        return DescribeServerResponse::fromArray($response->json());
    }
}
