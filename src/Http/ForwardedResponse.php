<?php

namespace SocialDept\AtpClient\Http;

use Illuminate\Http\Client\Response as LaravelResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relays a forwarded XRPC response back to the caller.
 *
 * Only headers that describe the content or its rate limits are passed on.
 * Anything about the app's own authentication with the PDS, such as
 * WWW-Authenticate or DPoP-Nonce, stays between the app and the PDS.
 */
class ForwardedResponse
{
    /**
     * Response headers relayed to the caller.
     */
    public const RELAYED_HEADERS = [
        'atproto-content-labelers',
        'atproto-repo-rev',
        'cache-control',
        'content-language',
        'content-type',
        'etag',
        'last-modified',
        'ratelimit-limit',
        'ratelimit-policy',
        'ratelimit-remaining',
        'ratelimit-reset',
        'retry-after',
    ];

    /**
     * Stream the PDS response to the caller with its status and relayed headers.
     */
    public static function toSymfony(LaravelResponse $response, int $chunkSize = 8192): StreamedResponse
    {
        $body = $response->toPsrResponse()->getBody();

        return new StreamedResponse(function () use ($body, $chunkSize) {
            while (! $body->eof()) {
                $chunk = $body->read($chunkSize);

                if ($chunk === '') {
                    break;
                }

                echo $chunk;
                flush();
            }
        }, $response->status(), static::relayedHeaders($response));
    }

    /**
     * @return array<string, string>
     */
    public static function relayedHeaders(LaravelResponse $response): array
    {
        $headers = [];

        foreach ($response->headers() as $name => $values) {
            $name = strtolower((string) $name);

            if (in_array($name, self::RELAYED_HEADERS, true)) {
                $headers[$name] = implode(', ', (array) $values);
            }
        }

        return $headers;
    }
}
