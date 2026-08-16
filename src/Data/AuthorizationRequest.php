<?php

namespace SocialDept\AtpClient\Data;

use Illuminate\Contracts\Support\Arrayable;

class AuthorizationRequest implements Arrayable
{
    public function __construct(
        public readonly string $url,
        public readonly string $state,
        public readonly string $codeVerifier,
        public readonly DPoPKey $dpopKey,
        public readonly string $requestUri,
        public readonly string $pdsEndpoint,
        public readonly ?string $handle = null,
        public readonly ?string $authServerIssuer = null,
        public readonly ?string $tokenEndpoint = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'state' => $this->state,
            'codeVerifier' => $this->codeVerifier,
            'dpopKey' => $this->dpopKey->toArray(),
            'requestUri' => $this->requestUri,
            'pdsEndpoint' => $this->pdsEndpoint,
            'handle' => $this->handle,
            'authServerIssuer' => $this->authServerIssuer,
            'tokenEndpoint' => $this->tokenEndpoint,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            state: $data['state'],
            codeVerifier: $data['codeVerifier'],
            dpopKey: DPoPKey::fromArray($data['dpopKey']),
            requestUri: $data['requestUri'],
            pdsEndpoint: $data['pdsEndpoint'],
            handle: $data['handle'] ?? null,
            authServerIssuer: $data['authServerIssuer'] ?? null,
            tokenEndpoint: $data['tokenEndpoint'] ?? null,
        );
    }
}
