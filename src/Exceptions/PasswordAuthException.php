<?php

namespace SocialDept\AtpClient\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * The PDS refused a password sign-in. `error` is the XRPC error name it
 * answered with, so a sign-in form can tell a wrong password from a missing
 * second factor.
 */
class PasswordAuthException extends AuthenticationException
{
    public function __construct(
        string $message,
        public readonly ?string $error = null,
        public readonly int $status = 0,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $error = is_array($body) && is_string($body['error'] ?? null) ? $body['error'] : null;
        $message = is_array($body) && is_string($body['message'] ?? null) ? $body['message'] : null;

        return new self($message ?? "The server refused the sign-in ({$response->status()}).", $error, $response->status());
    }

    public static function accountNotFound(string $identifier): self
    {
        return new self("No server found for {$identifier}.", 'AccountNotFound');
    }

    /**
     * The account has an email second factor: send the sign-in again with the
     * code the PDS just emailed.
     */
    public function needsAuthFactor(): bool
    {
        return $this->error === 'AuthFactorTokenRequired';
    }

    /**
     * The identifier or the password is wrong. The PDS does not say which.
     */
    public function invalidCredentials(): bool
    {
        return $this->error === 'AuthenticationRequired';
    }

    /**
     * A refresh was refused because the session is over: expired, revoked,
     * or signed out by a password reset. Only signing in again helps.
     */
    public function sessionEnded(): bool
    {
        return in_array($this->error, ['ExpiredToken', 'InvalidToken', 'AuthenticationRequired', 'AccountTakedown'], true);
    }

    public function takenDown(): bool
    {
        return $this->error === 'AccountTakedown';
    }

    public function rateLimited(): bool
    {
        return $this->status === 429 || $this->error === 'RateLimitExceeded';
    }
}
