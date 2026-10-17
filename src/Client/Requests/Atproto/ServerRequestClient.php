<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use BackedEnum;
use SocialDept\AtpClient\Attributes\PublicEndpoint;
use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\DescribeServerResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\GetServiceAuthResponse;
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

    /**
     * Ask the user's PDS for a service auth token addressed to another service.
     *
     * The PDS signs it with the account's repo key, so the receiving service
     * can verify it against the user's DID document. Use it to call a service
     * directly instead of proxying through the PDS, for example a video upload
     * service or an AppView on another host.
     *
     * Under OAuth the PDS authorizes the call with the same check it applies to
     * proxied requests, so the session needs `rpc:{lxm}?aud={aud}` for the pair
     * requested, with `aud` in the combined `did#serviceId` form that OAuth
     * scopes use. `transition:generic` covers any non-chat method.
     *
     * Always pass `$lxm`. A token without one can be replayed against any
     * method, is limited to a 60 second lifetime, and needs an `rpc:*` scope.
     *
     * @param  string  $aud  The service the token is for: a DID, or `did#serviceId`
     * @param  string|BackedEnum|null  $lxm  The method the token is bound to
     * @param  int|null  $exp  Expiry as a Unix timestamp, at most an hour ahead.
     *                         The PDS defaults to 60 seconds.
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-get-service-auth
     */
    #[ScopedEndpoint(Scope::Atproto, description: 'rpc:{lxm}?aud={aud} for the requested pair, or transition:generic')]
    public function getServiceAuth(string $aud, string|BackedEnum|null $lxm = null, ?int $exp = null): GetServiceAuthResponse
    {
        $lxm = $lxm instanceof BackedEnum ? $lxm->value : $lxm;

        $response = $this->atp->client->get(
            endpoint: AtprotoServer::GetServiceAuth,
            params: array_filter(compact('aud', 'lxm', 'exp'), fn ($value) => $value !== null)
        );

        return GetServiceAuthResponse::fromArray($response->json());
    }
}
