<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\DescribeServerResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\GetSessionResponse;
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
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.server.getSession')]
    public function getSession(): GetSessionResponse
    {
        $response = $this->atp->client->get(
            endpoint: 'com.atproto.server.getSession'
        );

        return GetSessionResponse::fromArray($response->json());
    }

    /**
     * Describe server
     *
     * @requires atproto (rpc:com.atproto.server.describeServer)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-describe-server
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.server.describeServer')]
    public function describeServer(): DescribeServerResponse
    {
        $response = $this->atp->client->get(
            endpoint: 'com.atproto.server.describeServer'
        );

        return DescribeServerResponse::fromArray($response->json());
    }
}
