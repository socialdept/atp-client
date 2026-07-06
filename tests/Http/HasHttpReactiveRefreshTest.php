<?php

namespace SocialDept\AtpClient\Tests\Http;

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SocialDept\AtpClient\Http\HasHttp;

class HasHttpReactiveRefreshTest extends TestCase
{
    private function target(): object
    {
        return new class
        {
            use HasHttp;
        };
    }

    private function response(int $status, string $body): Response
    {
        return new Response(new PsrResponse($status, [], $body));
    }

    private function isStale(Response $response): bool
    {
        $target = $this->target();

        return (new ReflectionMethod($target, 'isStaleAccessResponse'))->invoke($target, $response);
    }

    public function test_401_token_error_is_stale(): void
    {
        $this->assertTrue($this->isStale($this->response(401, json_encode(['error' => 'ExpiredToken']))));
        $this->assertTrue($this->isStale($this->response(401, json_encode(['error' => 'InvalidToken']))));
    }

    public function test_bare_401_is_stale(): void
    {
        $this->assertTrue($this->isStale($this->response(401, '')));
    }

    public function test_401_use_dpop_nonce_is_not_stale(): void
    {
        // DPoP nonce challenges are handled by the DPoP client's own retry, not a token refresh.
        $this->assertFalse($this->isStale($this->response(401, json_encode(['error' => 'use_dpop_nonce']))));
    }

    public function test_non_401_is_not_stale(): void
    {
        $this->assertFalse($this->isStale($this->response(500, 'server error')));
        $this->assertFalse($this->isStale($this->response(400, json_encode(['error' => 'invalid_request']))));
    }
}
