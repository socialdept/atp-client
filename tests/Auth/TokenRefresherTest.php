<?php

namespace SocialDept\AtpClient\Tests\Auth;

use Illuminate\Support\Facades\Http;
use Mockery;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\AuthorizationServerDiscovery;
use SocialDept\AtpClient\Auth\ClientAssertionManager;
use SocialDept\AtpClient\Auth\TokenRefresher;
use SocialDept\AtpClient\Data\AuthorizationServerMetadata;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Enums\AuthType;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\OAuthSessionInvalidException;
use SocialDept\AtpClient\Exceptions\TransientAuthFailureException;
use SocialDept\AtpClient\Http\DPoPClient;

class TokenRefresherTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_throws_missing_refresh_token_when_empty(): void
    {
        $refresher = $this->makeRefresher();

        $this->expectException(OAuthSessionInvalidException::class);
        $this->expectExceptionMessage('refresh token is missing');

        $refresher->refresh(
            refreshToken: '',
            pdsEndpoint: 'https://pampas.us-east.host.bsky.network',
            dpopKey: $this->makeDPoPKey(),
        );
    }

    public function test_throws_authentication_exception_on_permanent_4xx_failure(): void
    {
        $dpopClient = $this->mockDPoPClientWithResponse(400, json_encode([
            'error' => 'invalid_grant',
            'error_description' => 'Token has been revoked',
        ]));

        $refresher = $this->makeRefresher($dpopClient);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token refresh failed:');

        $refresher->refresh(
            refreshToken: 'test-refresh-token',
            pdsEndpoint: 'https://pampas.us-east.host.bsky.network',
            dpopKey: $this->makeDPoPKey(),
        );
    }

    public function test_throws_transient_exception_on_5xx_after_retries(): void
    {
        $dpopClient = $this->mockDPoPClientWithResponse(521, 'error code: 521');

        $refresher = $this->makeRefresher($dpopClient);

        $this->expectException(TransientAuthFailureException::class);
        $this->expectExceptionMessage('transient, HTTP 521');

        $refresher->refresh(
            refreshToken: 'test-refresh-token',
            pdsEndpoint: 'https://pampas.us-east.host.bsky.network',
            dpopKey: $this->makeDPoPKey(),
        );
    }

    public function test_throws_transient_exception_on_html_response_after_retries(): void
    {
        $html = '<!DOCTYPE html><html lang="en"><head><title>Error</title></head><body><pre>Cannot POST /oauth/token</pre></body></html>';
        $dpopClient = $this->mockDPoPClientWithResponse(404, $html);

        $refresher = $this->makeRefresher($dpopClient);

        $this->expectException(TransientAuthFailureException::class);
        $this->expectExceptionMessage('transient');

        $refresher->refresh(
            refreshToken: 'test-refresh-token',
            pdsEndpoint: 'https://pampas.us-east.host.bsky.network',
            dpopKey: $this->makeDPoPKey(),
        );
    }

    public function test_uses_discovered_token_endpoint(): void
    {
        Http::fake([
            'https://bsky.social/oauth/token' => Http::response(json_encode([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'sub' => 'did:plc:test123',
                'expires_in' => 3600,
                'scope' => 'atproto transition:generic',
            ]), 200),
        ]);

        $dpopClient = Mockery::mock(DPoPClient::class);
        $dpopClient->shouldReceive('request')
            ->withArgs(function ($issuer, $tokenUrl) {
                // Verify it uses the discovered auth server, not the PDS
                return $issuer === 'https://bsky.social'
                    && $tokenUrl === 'https://bsky.social/oauth/token';
            })
            ->andReturn(Http::baseUrl(''));

        $refresher = $this->makeRefresher($dpopClient);

        $token = $refresher->refresh(
            refreshToken: 'test-refresh-token',
            pdsEndpoint: 'https://pampas.us-east.host.bsky.network',
            dpopKey: $this->makeDPoPKey(),
        );

        $this->assertEquals('new-access-token', $token->accessJwt);
        $this->assertEquals('https://bsky.social', $token->issuer);
    }

    public function test_legacy_throws_transient_exception_on_5xx_after_retries(): void
    {
        Http::fake([
            '*/xrpc/com.atproto.server.refreshSession' => Http::response('Server Error', 502),
        ]);

        $refresher = $this->makeRefresher();

        $this->expectException(TransientAuthFailureException::class);
        $this->expectExceptionMessage('transient, HTTP 502');

        $refresher->refresh(
            refreshToken: 'test-refresh-token',
            pdsEndpoint: 'https://bsky.network',
            dpopKey: $this->makeDPoPKey(),
            authType: AuthType::Legacy,
        );
    }

    public function test_legacy_throws_authentication_exception_on_permanent_failure(): void
    {
        Http::fake([
            '*/xrpc/com.atproto.server.refreshSession' => Http::response(json_encode([
                'error' => 'ExpiredToken',
                'message' => 'Token has expired',
            ]), 400),
        ]);

        $refresher = $this->makeRefresher();

        $this->expectException(AuthenticationException::class);

        $refresher->refresh(
            refreshToken: 'test-refresh-token',
            pdsEndpoint: 'https://bsky.network',
            dpopKey: $this->makeDPoPKey(),
            authType: AuthType::Legacy,
        );
    }

    public function test_transient_exception_extends_authentication_exception(): void
    {
        $exception = TransientAuthFailureException::fromResponse('error', 521);

        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertInstanceOf(TransientAuthFailureException::class, $exception);
    }

    private function makeRefresher(?DPoPClient $dpopClient = null): TokenRefresher
    {
        $clientAssertion = Mockery::mock(ClientAssertionManager::class);
        $clientAssertion->shouldReceive('getAuthParams')->andReturn([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => 'test-assertion',
        ]);

        $discovery = Mockery::mock(AuthorizationServerDiscovery::class);
        $discovery->shouldReceive('discover')->andReturn(new AuthorizationServerMetadata(
            issuer: 'https://bsky.social',
            authorizationEndpoint: 'https://bsky.social/oauth/authorize',
            tokenEndpoint: 'https://bsky.social/oauth/token',
            parEndpoint: 'https://bsky.social/oauth/par',
            pdsEndpoint: 'https://pampas.us-east.host.bsky.network',
        ));

        return new TokenRefresher(
            dpopClient: $dpopClient ?? Mockery::mock(DPoPClient::class),
            clientAssertion: $clientAssertion,
            discovery: $discovery,
        );
    }

    private function mockDPoPClientWithResponse(int $status, string $body): DPoPClient
    {
        Http::fake([
            'https://bsky.social/oauth/token' => Http::response($body, $status),
        ]);

        $dpopClient = Mockery::mock(DPoPClient::class);
        $dpopClient->shouldReceive('request')->andReturn(Http::baseUrl(''));

        return $dpopClient;
    }

    private function makeDPoPKey(): DPoPKey
    {
        $privateKey = \phpseclib3\Crypt\EC::createKey('secp256r1');

        return new DPoPKey(
            privateKey: $privateKey,
            publicKey: $privateKey->getPublicKey(),
            keyId: 'test-key',
        );
    }
}
