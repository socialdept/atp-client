<?php

namespace SocialDept\AtpClient\Tests\Auth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Crypto\P256;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Enums\AuthType;
use SocialDept\AtpClient\Enums\RevocationFailure;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\SessionExpiredException;
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpClient\Providers\ArrayCredentialProvider;
use SocialDept\AtpClient\Session\Session;
use SocialDept\AtpClient\Session\SessionManager;
use SocialDept\AtpClient\Testing\FakeOAuthEngine;

class RevokeSessionTest extends TestCase
{
    private const DID = 'did:plc:revoke';

    private const ISSUER = 'https://auth.example';

    private const REVOKE = 'https://auth.example/oauth/revoke';

    private ArrayCredentialProvider $credentials;

    private KeyStore $keys;

    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('atp-client.client.client_id', 'https://app.example/oauth-client-metadata.json');
        config()->set('atp-client.oauth.private_key', P256::create()->privateB64());

        $this->credentials = new ArrayCredentialProvider();
        $this->app->instance(CredentialProvider::class, $this->credentials);

        $this->keys = new class () implements KeyStore {
            public array $keys = [];

            public function store(string $sessionId, DPoPKey $key): void
            {
                $this->keys[$sessionId] = $key;
            }

            public function get(string $sessionId): ?DPoPKey
            {
                return $this->keys[$sessionId] ?? null;
            }

            public function delete(string $sessionId): void
            {
                unset($this->keys[$sessionId]);
            }

            public function exists(string $sessionId): bool
            {
                return isset($this->keys[$sessionId]);
            }
        };
        $this->app->instance(KeyStore::class, $this->keys);
    }

    public function test_revokes_the_refresh_token_like_a_refresh_and_forgets_the_session(): void
    {
        $this->storeSession();
        $this->fakeServer([self::REVOKE => Http::response('', 200)]);

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertTrue($result->revoked);
        $this->assertNull($result->failure);
        $this->assertNull($this->credentials->getCredentials(self::DID));
        $this->assertFalse($this->keys->exists($this->sessionId()));

        Http::assertSent(function (Request $request) {
            return $request->url() === self::REVOKE
                && $request->hasHeader('DPoP')
                && $request['token'] === 'refresh-token'
                && $request['token_type_hint'] === 'refresh_token'
                && $request['client_id'] === 'https://app.example/oauth-client-metadata.json'
                && $request['client_assertion_type'] === 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'
                && filled($request['client_assertion']);
        });
        Http::assertNotSent(fn (Request $request) => $request->url() === self::REVOKE && $request['token'] === 'access-token');
    }

    public function test_falls_back_to_the_access_token_when_the_refresh_token_is_refused(): void
    {
        $this->storeSession();
        $this->fakeServer([
            self::REVOKE => function (Request $request) {
                return $request['token'] === 'access-token'
                    ? Http::response('', 200)
                    : Http::response(['error' => 'invalid_request'], 400);
            },
        ]);

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertTrue($result->revoked);
        Http::assertSent(fn (Request $request) => $request->url() === self::REVOKE && $request['token_type_hint'] === 'access_token');
    }

    public function test_a_refusal_still_forgets_the_session_and_reports_it(): void
    {
        $this->storeSession();
        $this->fakeServer([self::REVOKE => Http::response(['error' => 'invalid_client', 'error_description' => 'Unknown client'], 401)]);

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertFalse($result->revoked);
        $this->assertSame(RevocationFailure::Refused, $result->failure);
        $this->assertSame(401, $result->status);
        $this->assertSame('Unknown client', $result->error);
        $this->assertTrue($result->hadSession());
        $this->assertNull($this->credentials->getCredentials(self::DID));
        $this->assertFalse($this->keys->exists($this->sessionId()));
    }

    public function test_a_server_error_is_reported_as_unreachable(): void
    {
        $this->storeSession();
        $this->fakeServer([self::REVOKE => Http::response('Bad gateway', 502)]);

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertSame(RevocationFailure::Unreachable, $result->failure);
        $this->assertSame(502, $result->status);
        $this->assertNull($this->credentials->getCredentials(self::DID));
    }

    public function test_a_network_failure_still_forgets_the_session(): void
    {
        $this->storeSession();
        $this->fakeServer([self::REVOKE => fn () => throw new ConnectionException('Connection refused')]);

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertSame(RevocationFailure::Unreachable, $result->failure);
        $this->assertSame('Connection refused', $result->error);
        $this->assertNull($this->credentials->getCredentials(self::DID));
        $this->assertFalse($this->keys->exists($this->sessionId()));
    }

    public function test_a_server_without_a_revocation_endpoint_is_reported_and_not_called(): void
    {
        $this->storeSession();
        $this->fakeServer([], revocationEndpoint: null);

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertSame(RevocationFailure::NoEndpoint, $result->failure);
        $this->assertNull($this->credentials->getCredentials(self::DID));
        Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
    }

    public function test_a_missing_dpop_key_is_reported_and_not_called(): void
    {
        $this->storeSession(withKey: false);
        $this->fakeServer([self::REVOKE => Http::response('', 200)]);

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertSame(RevocationFailure::MissingDpopKey, $result->failure);
        $this->assertNull($this->credentials->getCredentials(self::DID));
        Http::assertNothingSent();
    }

    public function test_reports_no_session_when_nothing_is_stored(): void
    {
        Http::fake();

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertFalse($result->revoked);
        $this->assertSame(RevocationFailure::NoSession, $result->failure);
        $this->assertFalse($result->hadSession());
        Http::assertNothingSent();
    }

    public function test_an_app_password_session_is_deleted_at_its_pds(): void
    {
        $this->credentials->storeCredentials(self::DID, new AccessToken(
            accessJwt: 'access-token',
            refreshJwt: 'refresh-token',
            did: self::DID,
            expiresAt: now()->addHour(),
            issuer: 'https://pds.example',
            authType: AuthType::Legacy,
        ));
        Http::fake(['https://pds.example/xrpc/com.atproto.server.deleteSession' => Http::response('', 200)]);

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertTrue($result->revoked);
        $this->assertNull($this->credentials->getCredentials(self::DID));
        Http::assertSent(fn (Request $request) => $request->url() === 'https://pds.example/xrpc/com.atproto.server.deleteSession'
            && $request->header('Authorization') === ['Bearer refresh-token']);
    }

    public function test_drops_the_cached_session(): void
    {
        $this->storeSession();
        $this->fakeServer([self::REVOKE => Http::response('', 200)]);

        $manager = $this->app->make(SessionManager::class);
        $this->cacheSession($manager);

        Atp::oauth()->revoke(self::DID);

        $this->expectException(SessionExpiredException::class);

        $manager->session(self::DID);
    }

    public function test_throw_raises_only_when_the_server_did_not_confirm(): void
    {
        $this->storeSession();
        $this->fakeServer([self::REVOKE => Http::response(['error' => 'invalid_client'], 401)]);

        $result = Atp::oauth()->revoke(self::DID);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('refused (HTTP 401): invalid_client');

        $result->throw();
    }

    public function test_throw_returns_a_confirmed_result(): void
    {
        $this->storeSession();
        $this->fakeServer([self::REVOKE => Http::response('', 200)]);

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertSame($result, $result->throw());
    }

    public function test_the_fake_records_revoke(): void
    {
        $fake = Atp::fake();

        $result = Atp::oauth()->revoke(self::DID);

        $this->assertTrue($result->revoked);
        $this->assertSame([['revoke', ['did' => self::DID]]], $fake->oauth()->recordedCalls());
        $this->assertInstanceOf(FakeOAuthEngine::class, $fake->oauth());
    }

    private function storeSession(bool $withKey = true): void
    {
        $this->credentials->storeCredentials(self::DID, new AccessToken(
            accessJwt: 'access-token',
            refreshJwt: 'refresh-token',
            did: self::DID,
            expiresAt: now()->addHour(),
            issuer: self::ISSUER,
        ));

        if ($withKey) {
            $this->keys->store($this->sessionId(), FakeOAuthEngine::fakeDPoPKey());
        }
    }

    private function cacheSession(SessionManager $manager): void
    {
        $sessions = new ReflectionProperty($manager, 'sessions');
        $creds = $this->credentials->getCredentials(self::DID);
        $sessions->setValue($manager, [
            self::DID => new Session($creds, $this->keys->get($this->sessionId()), 'https://pds.example'),
        ]);
    }

    private function fakeServer(array $responses, ?string $revocationEndpoint = self::REVOKE): void
    {
        Http::fake($responses + [
            self::ISSUER.'/.well-known/oauth-authorization-server' => Http::response(array_filter([
                'issuer' => self::ISSUER,
                'authorization_endpoint' => self::ISSUER.'/oauth/authorize',
                'token_endpoint' => self::ISSUER.'/oauth/token',
                'pushed_authorization_request_endpoint' => self::ISSUER.'/oauth/par',
                'revocation_endpoint' => $revocationEndpoint,
            ])),
        ]);
    }

    private function sessionId(): string
    {
        return 'session_'.hash('sha256', self::DID);
    }
}
