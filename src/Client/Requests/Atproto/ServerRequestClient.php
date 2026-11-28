<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use BackedEnum;
use SocialDept\AtpClient\Attributes\PublicEndpoint;
use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\CreateAppPasswordResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\DescribeServerResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\GetServiceAuthResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\GetSessionResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\ListAppPasswordsResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\RequestEmailUpdateResponse;
use SocialDept\AtpClient\Data\Responses\EmptyResponse;
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

    /**
     * Create an app password for the account.
     *
     * The PDS returns the password only in this response. Needs a session
     * from the account's main password: app password and OAuth sessions are
     * refused.
     *
     * @param  bool  $privileged  Whether it may also reach direct messages
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-create-app-password
     */
    #[ScopedEndpoint(Scope::Atproto, description: 'A session from the main password')]
    public function createAppPassword(string $name, bool $privileged = false): CreateAppPasswordResponse
    {
        $response = $this->atp->client->post(
            endpoint: AtprotoServer::CreateAppPassword,
            body: compact('name', 'privileged'),
        );

        return CreateAppPasswordResponse::fromArray($response->json());
    }

    /**
     * List the account's app passwords, without the passwords themselves.
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-list-app-passwords
     */
    #[ScopedEndpoint(Scope::Atproto, description: 'A password session')]
    public function listAppPasswords(): ListAppPasswordsResponse
    {
        $response = $this->atp->client->get(
            endpoint: AtprotoServer::ListAppPasswords
        );

        return ListAppPasswordsResponse::fromArray($response->json());
    }

    /**
     * Revoke an app password and every session signed in with it.
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-revoke-app-password
     */
    #[ScopedEndpoint(Scope::Atproto, description: 'A password session')]
    public function revokeAppPassword(string $name): EmptyResponse
    {
        $this->atp->client->post(
            endpoint: AtprotoServer::RevokeAppPassword,
            body: compact('name'),
        );

        return new EmptyResponse();
    }

    /**
     * Ask the PDS to email a code that confirms the account's address.
     *
     * @requires account:email?action=manage under OAuth
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-request-email-confirmation
     */
    #[ScopedEndpoint(Scope::Atproto, granular: 'account:email?action=manage')]
    public function requestEmailConfirmation(): EmptyResponse
    {
        $this->atp->client->post(
            endpoint: AtprotoServer::RequestEmailConfirmation,
            body: null,
        );

        return new EmptyResponse();
    }

    /**
     * Confirm the account's address with the code from requestEmailConfirmation().
     *
     * @requires account:email?action=manage under OAuth
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-confirm-email
     */
    #[ScopedEndpoint(Scope::Atproto, granular: 'account:email?action=manage')]
    public function confirmEmail(string $email, string $token): EmptyResponse
    {
        $this->atp->client->post(
            endpoint: AtprotoServer::ConfirmEmail,
            body: compact('email', 'token'),
        );

        return new EmptyResponse();
    }

    /**
     * Start changing the account's address. Once the current address is
     * confirmed, the PDS emails a code there, and tokenRequired is true.
     * Needs a session from the main password.
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-request-email-update
     */
    #[ScopedEndpoint(Scope::Atproto, description: 'A session from the main password')]
    public function requestEmailUpdate(): RequestEmailUpdateResponse
    {
        $response = $this->atp->client->post(
            endpoint: AtprotoServer::RequestEmailUpdate,
            body: null,
        );

        return RequestEmailUpdateResponse::fromArray($response->json());
    }

    /**
     * Change the account's address, or turn the email second factor on or
     * off. Pass the code from requestEmailUpdate() when it said one is needed.
     * Needs a session from the main password.
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-update-email
     */
    #[ScopedEndpoint(Scope::Atproto, description: 'A session from the main password')]
    public function updateEmail(string $email, ?string $token = null, ?bool $emailAuthFactor = null): EmptyResponse
    {
        $this->atp->client->post(
            endpoint: AtprotoServer::UpdateEmail,
            body: array_filter(compact('email', 'token', 'emailAuthFactor'), fn ($value) => $value !== null),
        );

        return new EmptyResponse();
    }

    /**
     * Ask the PDS to email the code deleteAccount() needs. Needs a session
     * from the main password.
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-request-account-delete
     */
    #[ScopedEndpoint(Scope::Atproto, description: 'A session from the main password')]
    public function requestAccountDelete(): EmptyResponse
    {
        $this->atp->client->post(
            endpoint: AtprotoServer::RequestAccountDelete,
            body: null,
        );

        return new EmptyResponse();
    }

    /**
     * Delete the account for good, with its password and the code from
     * requestAccountDelete(). Takes no session, so it also works on a public
     * client for the account's PDS.
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-delete-account
     */
    #[PublicEndpoint]
    public function deleteAccount(string $did, string $password, string $token): EmptyResponse
    {
        $this->atp->client->post(
            endpoint: AtprotoServer::DeleteAccount,
            body: compact('did', 'password', 'token'),
        );

        return new EmptyResponse();
    }

    /**
     * Ask the PDS to email a password reset code to this address. Call it on
     * a public client for the account's PDS: no session is needed.
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-request-password-reset
     */
    #[PublicEndpoint]
    public function requestPasswordReset(string $email): EmptyResponse
    {
        $this->atp->client->post(
            endpoint: AtprotoServer::RequestPasswordReset,
            body: compact('email'),
        );

        return new EmptyResponse();
    }

    /**
     * Set a new password with the code from requestPasswordReset(). Every
     * session of the account is signed out.
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-reset-password
     */
    #[PublicEndpoint]
    public function resetPassword(string $token, string $password): EmptyResponse
    {
        $this->atp->client->post(
            endpoint: AtprotoServer::ResetPassword,
            body: compact('token', 'password'),
        );

        return new EmptyResponse();
    }
}
