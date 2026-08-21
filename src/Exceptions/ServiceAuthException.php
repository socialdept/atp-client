<?php

namespace SocialDept\AtpClient\Exceptions;

use RuntimeException;

/**
 * An inter-service auth token was missing, malformed, expired, addressed
 * elsewhere, or not signed by the DID it claims to come from.
 */
class ServiceAuthException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $error = 'AuthMissing',
        public readonly int $status = 401,
    ) {
        parent::__construct($message);
    }

    public static function missing(): self
    {
        return new self('Request requires a service auth token.', 'AuthMissing');
    }

    public static function malformed(string $detail): self
    {
        return new self("Malformed service auth token: {$detail}.", 'BadJwt', 400);
    }

    public static function expired(): self
    {
        return new self('Service auth token has expired.', 'JwtExpired');
    }

    public static function audience(string $expected): self
    {
        return new self("Service auth token is addressed to another service, expected \"{$expected}\".", 'BadJwtAudience');
    }

    public static function method(string $expected): self
    {
        return new self("Service auth token was not issued for \"{$expected}\".", 'BadJwtLexiconMethod');
    }

    public static function issuer(string $detail): self
    {
        return new self("Could not establish the service auth issuer: {$detail}.", 'BadJwtIss');
    }

    public static function signature(): self
    {
        return new self('Service auth token signature is invalid.', 'BadJwtSignature');
    }
}
