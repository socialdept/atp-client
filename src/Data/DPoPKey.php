<?php

namespace SocialDept\AtpClient\Data;

use Jose\Component\Core\JWK;

class DPoPKey
{
    public function __construct(
        public readonly JWK $privateKey,
        public readonly JWK $publicKey,
        public readonly string $keyId,
    ) {}

    public function getPublicJwk(): array
    {
        return $this->publicKey->jsonSerialize();
    }

    public function getPrivateJwk(): array
    {
        return $this->privateKey->jsonSerialize();
    }
}
