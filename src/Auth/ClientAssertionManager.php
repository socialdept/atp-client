<?php

namespace SocialDept\AtpClient\Auth;

use Closure;
use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;

class ClientAssertionManager
{
    public function __construct(
        protected ClientMetadataManager $metadata,
    ) {}

    /**
     * Check if client assertion is required (private key is configured)
     */
    public function isRequired(): bool
    {
        return ! empty(config('atp-client.oauth.private_key'));
    }

    /**
     * Create a client assertion JWT for private_key_jwt authentication
     */
    public function createAssertion(string $audience): string
    {
        $key = OAuthKey::load();
        $now = time();

        $payload = [
            'iss' => $this->metadata->getClientId(),
            'sub' => $this->metadata->getClientId(),
            'aud' => $audience,
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + 60,
        ];

        $header = [
            'alg' => 'ES256',
            'kid' => config('atp-client.oauth.kid', 'atp-client-key'),
            'typ' => 'JWT',
        ];

        return JWT::encode(
            payload: $payload,
            key: $key->toPEM(),
            alg: 'ES256',
            head: $header
        );
    }

    /**
     * Get the client assertion type for OAuth requests
     */
    public function getAssertionType(): string
    {
        return 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
    }

    /**
     * Get client authentication parameters for OAuth requests
     */
    public function getAuthParams(string $audience): array
    {
        if (! $this->isRequired()) {
            return [
                'client_id' => $this->metadata->getClientId(),
            ];
        }

        return [
            'client_id' => $this->metadata->getClientId(),
            'client_assertion_type' => $this->getAssertionType(),
            'client_assertion' => $this->createAssertion($audience),
        ];
    }

    /**
     * Build a Http request middleware that swaps the `client_assertion` form
     * parameter with a freshly minted JWT on every attempt.
     *
     * The DPoP layer wraps requests in `Http::retry()`, so the same outgoing
     * request can be sent multiple times (e.g. after a DPoP-Nonce challenge).
     * Without this middleware the same assertion JWT — including its `jti` —
     * is replayed, which authorization servers reject as `private_key_jwt jti
     * reused`.
     */
    public function refreshAssertionMiddleware(string $audience): Closure
    {
        return function (RequestInterface $request) use ($audience): RequestInterface {
            if (! $this->isRequired()) {
                return $request;
            }

            $body = (string) $request->getBody();
            parse_str($body, $params);

            if (! isset($params['client_assertion'])) {
                return $request;
            }

            $params['client_assertion'] = $this->createAssertion($audience);

            return $request->withBody(Utils::streamFor(http_build_query($params)));
        };
    }
}
