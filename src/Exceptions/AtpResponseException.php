<?php

namespace SocialDept\AtpClient\Exceptions;

use Illuminate\Http\Client\Response;

class AtpResponseException extends \Exception
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $errorMessage,
        public readonly int $httpStatus,
        public readonly string $endpoint,
        public readonly array $responseBody,
    ) {
        parent::__construct("{$errorCode}: {$errorMessage}", $httpStatus);
    }

    public static function fromResponse(Response $response, string $endpoint): self
    {
        $body = $response->json() ?? [];

        return new self(
            errorCode: $body['error'] ?? 'UnknownError',
            errorMessage: $body['message'] ?? $response->body(),
            httpStatus: $response->status(),
            endpoint: $endpoint,
            responseBody: $body,
        );
    }
}
