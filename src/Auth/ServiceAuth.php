<?php

namespace SocialDept\AtpClient\Auth;

use SocialDept\AtpClient\Data\ServiceAuthToken;
use SocialDept\AtpClient\Exceptions\ServiceAuthException;
use SocialDept\AtpSupport\Crypto\SignatureVerifier;
use SocialDept\AtpSupport\Identity;
use SocialDept\AtpSupport\Resolver;
use Throwable;

/**
 * AT Protocol inter-service auth.
 *
 * When one service calls another — a PDS proxying to an appview, an authority
 * asking a managing app whether to authorize a user — it signs a short-lived
 * JWT with the calling account's repo key. The receiver resolves the issuer's
 * DID document and checks the signature against the key published there.
 *
 * The `lxm` claim binds a token to one method. Without it, a token minted for a
 * harmless endpoint could be replayed against a sensitive one, so it is checked
 * whenever the caller names an expected method.
 */
class ServiceAuth
{
    /**
     * Tolerated clock difference, in seconds.
     */
    public const CLOCK_SKEW = 30;

    /**
     * The default lifetime of a minted token.
     */
    public const LIFETIME = 60;

    public function __construct(
        protected Resolver $resolver,
        protected SignatureVerifier $verifier,
    ) {
    }

    /**
     * Verify a service auth JWT.
     *
     * @param  string  $jwt  The raw token, without any scheme prefix
     * @param  string|null  $audience  The service identifier this service answers to
     * @param  string|null  $method  The NSID the token must be bound to
     *
     * @throws ServiceAuthException
     */
    public function verify(string $jwt, ?string $audience = null, ?string $method = null): ServiceAuthToken
    {
        [$header, $payload, $signingInput, $signature] = $this->decode($jwt);

        $issuer = $payload['iss'] ?? null;

        if (! is_string($issuer) || ! Identity::isDid($this->stripFragment($issuer))) {
            throw ServiceAuthException::issuer('"iss" is not a DID');
        }

        $expiry = $payload['exp'] ?? null;

        if (! is_int($expiry)) {
            throw ServiceAuthException::malformed('missing "exp"');
        }

        if (time() - self::CLOCK_SKEW >= $expiry) {
            throw ServiceAuthException::expired();
        }

        $tokenAudience = $payload['aud'] ?? null;

        if (! is_string($tokenAudience) || $tokenAudience === '') {
            throw ServiceAuthException::malformed('missing "aud"');
        }

        if ($audience !== null && ! hash_equals($audience, $tokenAudience)) {
            throw ServiceAuthException::audience($audience);
        }

        $tokenMethod = is_string($payload['lxm'] ?? null) ? $payload['lxm'] : null;

        if ($method !== null && $tokenMethod !== null && ! hash_equals($method, $tokenMethod)) {
            throw ServiceAuthException::method($method);
        }

        $this->verifySignature($issuer, $header, $signingInput, $signature);

        return new ServiceAuthToken($issuer, $tokenAudience, $tokenMethod, $expiry, $payload);
    }

    /**
     * Mint a service auth JWT.
     *
     * @param  callable(string): string  $sign  Signs the signing input, returning
     *                                          64 raw bytes of r || s
     * @param  string  $algorithm  ES256K for secp256k1, ES256 for P-256
     */
    public function mint(
        string $issuer,
        string $audience,
        ?string $method,
        callable $sign,
        string $algorithm = 'ES256K',
        ?int $lifetime = null,
    ): string {
        $now = time();

        $header = ['typ' => 'JWT', 'alg' => $algorithm];

        $payload = array_filter([
            'iss' => $issuer,
            'aud' => $audience,
            'lxm' => $method,
            'iat' => $now,
            'exp' => $now + ($lifetime ?? self::LIFETIME),
            'jti' => bin2hex(random_bytes(16)),
        ], fn ($value) => $value !== null);

        $signingInput = $this->base64UrlEncode((string) json_encode($header))
            .'.'.$this->base64UrlEncode((string) json_encode($payload));

        return $signingInput.'.'.$this->base64UrlEncode($sign($signingInput));
    }

    /**
     * Extract the token from an Authorization header, if present.
     */
    public function fromHeader(?string $header): ?string
    {
        if (! is_string($header) || $header === '') {
            return null;
        }

        if (! preg_match('/^Bearer\s+(\S+)$/i', trim($header), $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Check the signature against a key the issuer publishes.
     *
     * A `kid` names which verification method signed, defaulting to the repo
     * signing key. Key rotation is handled by retrying uncached: the cached
     * document may predate the rotation.
     *
     * @param  array<string, mixed>  $header
     */
    protected function verifySignature(string $issuer, array $header, string $signingInput, string $signature): void
    {
        $did = $this->stripFragment($issuer);
        $fragment = is_string($header['kid'] ?? null) ? $header['kid'] : '#atproto';

        foreach ([true, false] as $useCache) {
            try {
                $document = $this->resolver->resolveDid($did, $useCache);
            } catch (Throwable $e) {
                throw ServiceAuthException::issuer($e->getMessage());
            }

            $didKey = $document->getSigningKey($fragment);

            if ($didKey === null) {
                throw ServiceAuthException::issuer("no \"{$fragment}\" key published by {$did}");
            }

            if ($this->verifier->verify($didKey, $signingInput, $signature)) {
                return;
            }

            // A cached document can predate a key rotation; a fresh one cannot.
            if (! $useCache) {
                break;
            }
        }

        throw ServiceAuthException::signature();
    }

    /**
     * Split and decode a compact JWS.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: string, 3: string}
     */
    protected function decode(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw ServiceAuthException::malformed('expected 3 parts');
        }

        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        if (! is_array($header) || ! is_array($payload)) {
            throw ServiceAuthException::malformed('header or payload is not JSON');
        }

        $algorithm = $header['alg'] ?? null;

        // Anything else would be a different verification path entirely; "none"
        // in particular must never reach the signature check.
        if (! in_array($algorithm, ['ES256K', 'ES256'], true)) {
            throw ServiceAuthException::malformed('unsupported "alg"');
        }

        $signature = $this->base64UrlDecode($parts[2]);

        if (strlen($signature) !== SignatureVerifier::SIGNATURE_BYTES) {
            throw ServiceAuthException::malformed('signature is not 64 bytes');
        }

        return [$header, $payload, $parts[0].'.'.$parts[1], $signature];
    }

    /**
     * A service identifier may carry a fragment; the DID itself does not.
     */
    protected function stripFragment(string $identifier): string
    {
        return explode('#', $identifier, 2)[0];
    }

    protected function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $value): string
    {
        $padded = strtr($value, '-_', '+/');
        $remainder = strlen($padded) % 4;

        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode($padded, true);
    }
}
