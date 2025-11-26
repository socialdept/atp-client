<?php

namespace SocialDept\AtpClient\Data;

class AuthorizationRequest
{
    public function __construct(
        public readonly string $url,
        public readonly string $state,
        public readonly string $codeVerifier,
        public readonly DPoPKey $dpopKey,
        public readonly string $requestUri,
        public readonly string $pdsEndpoint,
        public readonly ?string $handle = null,
    ) {}
}
