<?php

namespace SocialDept\AtpClient\Tests\Session;

use Illuminate\Support\Facades\Event;
use Mockery;
use Orchestra\Testbench\TestCase;
use ReflectionMethod;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\DPoPKeyManager;
use SocialDept\AtpClient\Auth\TokenRefresher;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\Credentials;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Enums\AuthType;
use SocialDept\AtpClient\Events\SessionRefreshFailed;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\TransientAuthFailureException;
use SocialDept\AtpClient\Providers\ArrayCredentialProvider;
use SocialDept\AtpClient\Session\Session;
use SocialDept\AtpClient\Session\SessionManager;

class SessionManagerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_adopts_concurrently_rotated_token_without_calling_endpoint(): void
    {
        // Stored token differs from ours: a concurrent request already rotated it.
        $credentials = new ArrayCredentialProvider();
        $credentials->storeCredentials('did:plc:test', $this->accessToken('rotated-by-winner'));

        // Adopt the winner's token; never replay the consumed one.
        $refresher = Mockery::mock(TokenRefresher::class);
        $refresher->shouldReceive('refresh')->never();

        $manager = $this->makeManager($credentials, $refresher);
        $session = $this->makeSession('stale-token-we-hold');

        $result = $this->refresh($manager, $session);

        $this->assertEquals('rotated-by-winner', $result->refreshToken());
    }

    public function test_performs_refresh_when_token_not_rotated(): void
    {
        $credentials = new ArrayCredentialProvider();
        $credentials->storeCredentials('did:plc:test', $this->accessToken('current-token'));

        $refresher = Mockery::mock(TokenRefresher::class);
        $refresher->shouldReceive('refresh')->once()->andReturn($this->accessToken('freshly-rotated'));

        $manager = $this->makeManager($credentials, $refresher);
        $session = $this->makeSession('current-token');

        $result = $this->refresh($manager, $session);

        $this->assertEquals('freshly-rotated', $result->refreshToken());
        $this->assertEquals('freshly-rotated', $credentials->getCredentials('did:plc:test')->refreshToken);
    }

    public function test_permanent_failure_emits_session_refresh_failed_with_invalid_reason(): void
    {
        Event::fake();

        $credentials = new ArrayCredentialProvider();
        $credentials->storeCredentials('did:plc:test', $this->accessToken('dead-token'));

        $refresher = Mockery::mock(TokenRefresher::class);
        $refresher->shouldReceive('refresh')->once()->andThrow(
            new AuthenticationException('Token refresh failed: {"error":"invalid_grant","error_description":"Invalid refresh token"}')
        );

        $manager = $this->makeManager($credentials, $refresher);
        $session = $this->makeSession('dead-token');

        try {
            $this->refresh($manager, $session);
            $this->fail('Expected AuthenticationException');
        } catch (AuthenticationException) {
            // expected
        }

        Event::assertDispatched(
            SessionRefreshFailed::class,
            fn (SessionRefreshFailed $event) => $event->reason === 'invalid'
        );
    }

    public function test_transient_failure_preserves_stored_credentials(): void
    {
        $credentials = new ArrayCredentialProvider();
        $credentials->storeCredentials('did:plc:test', $this->accessToken('still-valid-token'));

        $refresher = Mockery::mock(TokenRefresher::class);
        $refresher->shouldReceive('refresh')->once()->andThrow(
            TransientAuthFailureException::fromResponse('error code: 521', 521)
        );

        $manager = $this->makeManager($credentials, $refresher);
        $session = $this->makeSession('still-valid-token');

        try {
            $this->refresh($manager, $session);
            $this->fail('Expected TransientAuthFailureException');
        } catch (TransientAuthFailureException) {
            // expected
        }

        // Transient failure must leave the token intact so a later attempt retries.
        $this->assertEquals('still-valid-token', $credentials->getCredentials('did:plc:test')->refreshToken);
    }

    private function makeManager(ArrayCredentialProvider $credentials, TokenRefresher $refresher): SessionManager
    {
        return new SessionManager(
            credentials: $credentials,
            refresher: $refresher,
            dpopManager: Mockery::mock(DPoPKeyManager::class),
            keyStore: Mockery::mock(KeyStore::class),
        );
    }

    private function makeSession(string $refreshToken): Session
    {
        return new Session(
            $this->credentials($refreshToken),
            new DPoPKey('private-key-pem', 'public-key-pem', 'test-kid'),
            'https://pds.example',
        );
    }

    private function credentials(string $refreshToken): Credentials
    {
        return new Credentials(
            did: 'did:plc:test',
            accessToken: 'access-token',
            refreshToken: $refreshToken,
            expiresAt: now()->addHour(),
            handle: 'test.example',
            issuer: 'https://bsky.social',
            scope: ['atproto'],
            authType: AuthType::OAuth,
        );
    }

    private function accessToken(string $refreshToken): AccessToken
    {
        return new AccessToken(
            accessJwt: 'access-token',
            refreshJwt: $refreshToken,
            did: 'did:plc:test',
            expiresAt: now()->addHour(),
            handle: 'test.example',
            issuer: 'https://bsky.social',
            scope: ['atproto'],
            authType: AuthType::OAuth,
        );
    }

    private function refresh(SessionManager $manager, Session $session): Session
    {
        $method = new ReflectionMethod($manager, 'refreshSession');

        return $method->invoke($manager, $session);
    }
}
