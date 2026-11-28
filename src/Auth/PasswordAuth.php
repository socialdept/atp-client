<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Support\Facades\Http;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\Credentials;
use SocialDept\AtpClient\Exceptions\PasswordAuthException;
use SocialDept\AtpSupport\Facades\Resolver;
use Throwable;

/**
 * Password sessions on a PDS, made and ended without storing anything.
 *
 * `Atp::login()` keeps the session in the credential provider. These calls
 * hand the tokens back instead, for code that decides where a session lives
 * itself, or that needs one for a single request, such as a session from
 * the main password used once to create an app password.
 */
class PasswordAuth
{
    /**
     * Sign in with a handle, DID or email and a password, the account's own
     * or an app password.
     *
     * @param  string|null  $authFactorToken  The code the PDS emailed, for an account with an email second factor
     * @param  string|null  $pds  Where to sign in. Resolved from the identifier when null, so an email needs it
     *
     * @throws PasswordAuthException
     */
    public function createSession(
        string $identifier,
        string $password,
        ?string $authFactorToken = null,
        ?string $pds = null,
    ): AccessToken {
        $pds = rtrim($pds ?? $this->pdsFor($identifier), '/');

        $response = Http::acceptJson()->post($pds.'/xrpc/com.atproto.server.createSession', array_filter([
            'identifier' => $identifier,
            'password' => $password,
            'authFactorToken' => $authFactorToken,
        ], fn ($value) => $value !== null && $value !== ''));

        if ($response->failed()) {
            throw PasswordAuthException::fromResponse($response);
        }

        return AccessToken::fromResponse($response->json(), issuer: $pds);
    }

    /**
     * Swap a password session's refresh token for new tokens. The PDS rotates
     * the refresh token, and the old one keeps working for a short grace
     * period, so two refreshes at once get the same answer.
     *
     * @throws PasswordAuthException
     */
    public function refreshSession(string $refreshJwt, string $pds): AccessToken
    {
        $pds = rtrim($pds, '/');

        $response = Http::acceptJson()
            ->withToken($refreshJwt)
            ->withBody('', 'application/json')
            ->post($pds.'/xrpc/com.atproto.server.refreshSession');

        if ($response->failed()) {
            throw PasswordAuthException::fromResponse($response);
        }

        return AccessToken::fromResponse($response->json(), issuer: $pds);
    }

    /**
     * End a password session at its PDS. Its access token keeps working until
     * it expires, since the PDS checks only its signature.
     *
     * @return bool Whether the PDS confirmed it
     */
    public function deleteSession(AccessToken|Credentials $session, ?string $pds = null): bool
    {
        $refresh = $session instanceof AccessToken ? $session->refreshJwt : $session->refreshToken;
        $pds = $pds ?? $session->issuer ?? Resolver::resolvePds($session->did);

        if ($refresh === '' || $pds === null) {
            return false;
        }

        try {
            return Http::acceptJson()
                ->withToken($refresh)
                ->withBody('', 'application/json')
                ->post(rtrim($pds, '/').'/xrpc/com.atproto.server.deleteSession')
                ->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @throws PasswordAuthException
     */
    protected function pdsFor(string $identifier): string
    {
        try {
            $pds = Resolver::resolvePds($identifier);
        } catch (Throwable) {
            $pds = null;
        }

        return $pds ?? throw PasswordAuthException::accountNotFound($identifier);
    }
}
