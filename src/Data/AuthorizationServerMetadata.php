<?php

namespace SocialDept\AtpClient\Data;

use Illuminate\Contracts\Support\Arrayable;

class AuthorizationServerMetadata implements Arrayable
{
    public function __construct(
        public readonly string $issuer,
        public readonly string $authorizationEndpoint,
        public readonly string $tokenEndpoint,
        public readonly string $parEndpoint,
        public readonly string $pdsEndpoint,
        public readonly ?string $revocationEndpoint = null,
        public readonly ?string $introspectionEndpoint = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'issuer' => $this->issuer,
            'authorization_endpoint' => $this->authorizationEndpoint,
            'token_endpoint' => $this->tokenEndpoint,
            'pushed_authorization_request_endpoint' => $this->parEndpoint,
            'pds_endpoint' => $this->pdsEndpoint,
            'revocation_endpoint' => $this->revocationEndpoint,
            'introspection_endpoint' => $this->introspectionEndpoint,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            issuer: $data['issuer'],
            authorizationEndpoint: $data['authorization_endpoint'],
            tokenEndpoint: $data['token_endpoint'],
            parEndpoint: $data['pushed_authorization_request_endpoint'],
            pdsEndpoint: $data['pds_endpoint'],
            revocationEndpoint: $data['revocation_endpoint'] ?? null,
            introspectionEndpoint: $data['introspection_endpoint'] ?? null,
        );
    }
}
