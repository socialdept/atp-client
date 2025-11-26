<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;

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
    public function getSession(): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.server.getSession'
        );
    }

    /**
     * Describe server
     *
     * @requires atproto (rpc:com.atproto.server.describeServer)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-describe-server
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.server.describeServer')]
    public function describeServer(): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.server.describeServer'
        );
    }
}
