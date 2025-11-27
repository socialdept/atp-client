<?php

namespace SocialDept\AtpClient\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SocialDept\AtpClient\Auth\DPoPKeyManager;
use SocialDept\AtpClient\Auth\DPoPNonceManager;
use SocialDept\AtpClient\Data\DPoPKey;

class DPoPClient
{
    public function __construct(
        protected DPoPKeyManager $dpopManager,
        protected DPoPNonceManager $nonceManager,
    ) {}

    /**
     * Build a DPoP-authenticated request with automatic nonce retry
     */
    public function request(
        string $pdsEndpoint,
        string $url,
        string $method,
        DPoPKey $dpopKey,
        ?string $accessToken = null,
    ): PendingRequest {
        return Http::retry(times: 2, sleepMilliseconds: 0, throw: false)
            ->withRequestMiddleware(
                fn (RequestInterface $request) => $this->addDPoPProof(
                    $request,
                    $pdsEndpoint,
                    $url,
                    $method,
                    $dpopKey,
                    $accessToken,
                )
            )
            ->withResponseMiddleware(
                fn (ResponseInterface $response) => $this->captureNonce($response, $pdsEndpoint)
            );
    }

    /**
     * Add DPoP proof header to request
     */
    protected function addDPoPProof(
        RequestInterface $request,
        string $pdsEndpoint,
        string $url,
        string $method,
        DPoPKey $dpopKey,
        ?string $accessToken,
    ): RequestInterface {
        $nonce = $this->nonceManager->getNonce($pdsEndpoint);

        $dpopProof = $this->dpopManager->createProof(
            key: $dpopKey,
            method: $method,
            url: $url,
            nonce: $nonce,
            accessToken: $accessToken,
        );

        $request = $request->withHeader('DPoP', $dpopProof);

        if ($accessToken) {
            $request = $request->withHeader('Authorization', 'DPoP '.$accessToken);
        }

        return $request;
    }

    /**
     * Capture DPoP nonce from response for future requests
     */
    protected function captureNonce(ResponseInterface $response, string $pdsEndpoint): ResponseInterface
    {
        $nonce = $response->getHeaderLine('DPoP-Nonce');

        if ($nonce !== '') {
            $this->nonceManager->storeNonce($pdsEndpoint, $nonce);
        }

        return $response;
    }
}
