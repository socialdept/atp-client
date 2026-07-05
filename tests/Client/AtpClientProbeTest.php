<?php

namespace SocialDept\AtpClient\Tests\Client;

use Mockery;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Client\AtprotoClient;
use SocialDept\AtpClient\Client\Requests\Atproto\ServerRequestClient;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\GetSessionResponse;
use SocialDept\AtpClient\Enums\RefreshFailureReason;
use SocialDept\AtpClient\Exceptions\AtpResponseException;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use Throwable;

class AtpClientProbeTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    private function clientReturning(GetSessionResponse $response): AtpClient
    {
        return $this->clientWith(fn ($server) => $server->shouldReceive('getSession')->andReturn($response));
    }

    private function clientThrowing(Throwable $e): AtpClient
    {
        return $this->clientWith(fn ($server) => $server->shouldReceive('getSession')->andThrow($e));
    }

    private function clientWith(callable $configure): AtpClient
    {
        $server = Mockery::mock(ServerRequestClient::class);
        $configure($server);

        $atproto = Mockery::mock(AtprotoClient::class);
        $atproto->server = $server;

        $client = new AtpClient();
        $client->atproto = $atproto;

        return $client;
    }

    public function test_probe_healthy_when_active(): void
    {
        $client = $this->clientReturning(new GetSessionResponse(handle: 'h.test', did: 'did:plc:x', active: true));

        $this->assertTrue($client->probe()->isHealthy());
    }

    public function test_probe_healthy_when_active_is_null(): void
    {
        // Older PDSes may omit `active`; absence means live.
        $client = $this->clientReturning(new GetSessionResponse(handle: 'h.test', did: 'did:plc:x'));

        $this->assertTrue($client->probe()->isHealthy());
    }

    public function test_probe_inactive_account_is_terminal(): void
    {
        $client = $this->clientReturning(new GetSessionResponse(handle: 'h.test', did: 'did:plc:x', active: false, status: 'deactivated'));

        $health = $client->probe();

        $this->assertTrue($health->isTerminal());
        $this->assertSame('deactivated', $health->status);
    }

    public function test_probe_terminal_on_invalid_grant_refresh_failure(): void
    {
        $client = $this->clientThrowing(
            (new AuthenticationException('Token refresh failed'))->withReason(RefreshFailureReason::InvalidGrant)
        );

        $this->assertTrue($client->probe()->isTerminal());
    }

    public function test_probe_transient_on_transient_refresh_failure(): void
    {
        $client = $this->clientThrowing(
            (new AuthenticationException('temporarily unavailable'))->withReason(RefreshFailureReason::ServerError)
        );

        $health = $client->probe();

        $this->assertFalse($health->reachable);
        $this->assertFalse($health->isTerminal());
    }

    public function test_probe_transient_on_5xx_response(): void
    {
        $client = $this->clientThrowing(new AtpResponseException('UpstreamFailure', 'bad gateway', 502, 'com.atproto.server.getSession', []));

        $health = $client->probe();

        $this->assertFalse($health->reachable);
        $this->assertFalse($health->isTerminal());
    }

    public function test_probe_stale_access_on_401(): void
    {
        $client = $this->clientThrowing(new AtpResponseException('InvalidToken', 'token has expired', 401, 'com.atproto.server.getSession', []));

        $this->assertTrue($client->probe()->needsRefresh());
    }

    public function test_probe_account_not_found_is_terminal(): void
    {
        $client = $this->clientThrowing(new AtpResponseException('InvalidRequest', 'Could not find user info for account: did:plc:x', 400, 'com.atproto.server.getSession', []));

        $this->assertTrue($client->probe()->isTerminal());
    }

    public function test_probe_network_failure_is_transient(): void
    {
        $client = $this->clientThrowing(new \RuntimeException('cURL error 6'));

        $health = $client->probe();

        $this->assertFalse($health->reachable);
        $this->assertFalse($health->isTerminal());
    }
}
