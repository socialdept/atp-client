<?php

namespace SocialDept\AtpClient\Tests\Auth;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Enums\AuthType;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\PasswordAuthException;
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpClient\Providers\ArrayCredentialProvider;
use SocialDept\AtpSupport\AtpSupportServiceProvider;
use SocialDept\AtpSupport\Facades\Resolver;

class PasswordAuthTest extends TestCase
{
    private const DID = 'did:plc:alice';

    private const PDS = 'https://pds.example';

    private ArrayCredentialProvider $credentials;

    private KeyStore $keys;

    protected function getPackageProviders($app): array
    {
        return [AtpSupportServiceProvider::class, AtpClientServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

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

        Resolver::shouldReceive('resolvePds')->andReturn(self::PDS);
    }

    public function test_it_signs_in_without_storing_the_session(): void
    {
        Http::fake([self::PDS.'/xrpc/com.atproto.server.createSession' => Http::response($this->sessionBody())]);

        $token = Atp::password()->createSession('alice.example', 'secret');

        $this->assertSame(self::DID, $token->did);
        $this->assertSame(AuthType::Legacy, $token->authType);
        $this->assertSame(self::PDS, $token->issuer);
        $this->assertSame('alice.example', $token->handle);
        $this->assertNull($this->credentials->getCredentials(self::DID));
        Http::assertSent(fn (Request $request) => $request['identifier'] === 'alice.example'
            && $request['password'] === 'secret'
            && ! isset($request['authFactorToken']));
    }

    public function test_it_sends_the_second_factor_and_signs_in_at_the_given_server(): void
    {
        Http::fake(['https://other.example/xrpc/com.atproto.server.createSession' => Http::response($this->sessionBody())]);

        Atp::password()->createSession('alice@mail.example', 'secret', 'ABCDE-12345', 'https://other.example/');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://other.example/xrpc/com.atproto.server.createSession'
            && $request['authFactorToken'] === 'ABCDE-12345');
    }

    public function test_a_refusal_names_the_error(): void
    {
        $cases = [
            'needsAuthFactor' => [401, 'AuthFactorTokenRequired'],
            'invalidCredentials' => [401, 'AuthenticationRequired'],
            'takenDown' => [401, 'AccountTakedown'],
            'rateLimited' => [429, 'RateLimitExceeded'],
        ];

        $answer = null;
        Http::fake([self::PDS.'/*' => function () use (&$answer) {
            return Http::response(['error' => $answer[1], 'message' => "{$answer[1]} message"], $answer[0]);
        }]);

        foreach ($cases as $check => [$status, $error]) {
            $answer = [$status, $error];

            try {
                Atp::password()->createSession('alice.example', 'secret');
                $this->fail("{$error} did not throw");
            } catch (PasswordAuthException $e) {
                $this->assertTrue($e->{$check}(), $check);
                $this->assertSame($error, $e->error);
                $this->assertSame($status, $e->status);
                $this->assertSame("{$error} message", $e->getMessage());
            }
        }
    }

    public function test_an_account_without_a_server_is_refused_before_any_request(): void
    {
        Http::fake();
        Resolver::swap(new class () {
            public function resolvePds(string $actor): ?string
            {
                return null;
            }
        });

        try {
            Atp::password()->createSession('nobody.example', 'secret');
            $this->fail('No exception');
        } catch (PasswordAuthException $e) {
            $this->assertSame('AccountNotFound', $e->error);
        }

        Http::assertNothingSent();
    }

    public function test_it_ends_a_session_with_its_refresh_token(): void
    {
        Http::fake([self::PDS.'/xrpc/com.atproto.server.deleteSession' => Http::response()]);

        $this->assertTrue(Atp::password()->deleteSession($this->token()));

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer refresh-jwt'));
    }

    public function test_ending_a_session_reports_a_refusal(): void
    {
        Http::fake([self::PDS.'/*' => Http::response(['error' => 'ExpiredToken'], 400)]);

        $this->assertFalse(Atp::password()->deleteSession($this->token()));
    }

    public function test_it_refreshes_a_session_without_storing_it(): void
    {
        Http::fake([self::PDS.'/xrpc/com.atproto.server.refreshSession' => Http::response($this->sessionBody())]);

        $token = Atp::password()->refreshSession('refresh-jwt', self::PDS);

        $this->assertSame(self::DID, $token->did);
        $this->assertSame(AuthType::Legacy, $token->authType);
        $this->assertNull($this->credentials->getCredentials(self::DID));
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer refresh-jwt'));
    }

    public function test_a_dead_refresh_token_names_the_error(): void
    {
        Http::fake([self::PDS.'/*' => Http::response(['error' => 'ExpiredToken', 'message' => 'Token has expired'], 400)]);

        try {
            Atp::password()->refreshSession('refresh-jwt', self::PDS);
            $this->fail('No exception');
        } catch (PasswordAuthException $e) {
            $this->assertSame('ExpiredToken', $e->error);
            $this->assertTrue($e->sessionEnded());
        }
    }

    public function test_login_keeps_the_session_and_stores_no_key(): void
    {
        Http::fake([self::PDS.'/xrpc/com.atproto.server.createSession' => Http::response($this->sessionBody())]);

        $client = Atp::login(self::DID, 'secret', 'ABCDE-12345');

        $this->assertSame(AuthType::Legacy, $this->credentials->getCredentials(self::DID)?->authType);
        $this->assertSame(self::DID, $client->client->repoDid());
        $this->assertSame([], $this->keys->keys);
        Http::assertSent(fn (Request $request) => $request['authFactorToken'] === 'ABCDE-12345');
    }

    public function test_a_refused_login_is_still_an_authentication_exception(): void
    {
        Http::fake([self::PDS.'/*' => Http::response(['error' => 'AuthenticationRequired', 'message' => 'Invalid identifier or password'], 401)]);

        $this->expectException(AuthenticationException::class);

        Atp::login(self::DID, 'wrong');
    }

    public function test_a_client_for_a_token_stores_nothing(): void
    {
        Http::fake([self::PDS.'/xrpc/com.atproto.server.createAppPassword' => Http::response([
            'name' => 'Laptop',
            'password' => 'abcd-efgh-ijkl-mnop',
            'createdAt' => '2026-11-28T00:00:00.000Z',
        ])]);

        $created = Atp::withToken($this->token())->atproto->server->createAppPassword('Laptop');

        $this->assertSame('abcd-efgh-ijkl-mnop', $created->password);
        $this->assertNull($this->credentials->getCredentials(self::DID));
        $this->assertSame([], $this->keys->keys);
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer access-jwt')
            && $request['name'] === 'Laptop'
            && $request['privileged'] === false);
    }

    public function test_a_procedure_without_input_is_sent_without_a_body(): void
    {
        Http::fake([self::PDS.'/*' => Http::response(['tokenRequired' => true])]);

        $server = Atp::withToken($this->token())->atproto->server;
        $server->requestEmailConfirmation();
        $server->requestEmailUpdate();
        $server->requestAccountDelete();

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->body() === '');
        Http::assertNotSent(fn (Request $request) => $request->body() !== '');
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionBody(): array
    {
        return [
            'did' => self::DID,
            'handle' => 'alice.example',
            'accessJwt' => $this->jwt(time() + 7200),
            'refreshJwt' => 'refresh-jwt',
        ];
    }

    private function token(): AccessToken
    {
        return new AccessToken(
            accessJwt: 'access-jwt',
            refreshJwt: 'refresh-jwt',
            did: self::DID,
            expiresAt: now()->addHours(2),
            handle: 'alice.example',
            issuer: self::PDS,
            authType: AuthType::Legacy,
        );
    }

    private function jwt(int $exp): string
    {
        $encode = fn (array $part) => rtrim(strtr(base64_encode(json_encode($part)), '+/', '-_'), '=');

        return $encode(['alg' => 'HS256']).'.'.$encode(['sub' => self::DID, 'exp' => $exp]).'.signature';
    }
}
