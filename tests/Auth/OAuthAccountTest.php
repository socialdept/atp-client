<?php

namespace SocialDept\AtpClient\Tests\Auth;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Mockery;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\AuthorizationServerDiscovery;
use SocialDept\AtpClient\Auth\OAuthEngine;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AuthorizationRequest;
use SocialDept\AtpClient\Data\AuthorizationServerMetadata;
use SocialDept\AtpClient\Events\SessionAuthenticated;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Testing\FakeOAuthEngine;
use SocialDept\AtpSupport\AtpSupportServiceProvider;
use SocialDept\AtpSupport\Data\DidDocument;
use SocialDept\AtpSupport\Facades\Resolver;

/**
 * Starting a login at a server, and checking that the account the server
 * returns is one it may sign in.
 */
class OAuthAccountTest extends TestCase
{
    protected const AUTH = 'https://auth.example';
    protected const PDS = 'https://pds.example';

    protected KeyStore $keys;

    protected function getPackageProviders($app): array
    {
        return [AtpSupportServiceProvider::class, AtpClientServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('atp-client.client.client_id', 'https://app.example/oauth-client-metadata.json');
        $app['config']->set('atp-client.client.redirect_uris', ['https://app.example/oauth/callback']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $discovery = Mockery::mock(AuthorizationServerDiscovery::class);
        $discovery->shouldReceive('discover')->with(self::PDS)->andReturn($this->metadata(self::AUTH, self::PDS));
        $discovery->shouldReceive('discover')->with('https://evil-pds.example')->andReturn($this->metadata('https://evil.example', 'https://evil-pds.example'));
        $discovery->shouldReceive('forAuthorizationServer')->with(self::AUTH)->andReturn($this->metadata(self::AUTH, self::AUTH));
        $this->app->instance(AuthorizationServerDiscovery::class, $discovery);

        // Starting a flow stores its DPoP key. Tests about the callback
        // narrow this to what they expect.
        $this->keys = Mockery::mock(KeyStore::class);
        $this->keys->shouldReceive('store')->byDefault();
        $this->app->instance(KeyStore::class, $this->keys);

        Resolver::shouldReceive('resolveDid')->with('did:plc:alice', false)->andReturn(DidDocument::fromArray([
            'id' => 'did:plc:alice',
            'alsoKnownAs' => ['at://alice.example'],
            'service' => [['id' => '#atproto_pds', 'type' => 'AtprotoPersonalDataServer', 'serviceEndpoint' => self::PDS]],
        ]));
        Resolver::shouldReceive('resolvePds')->with('alice.example')->andReturn(self::PDS);
        Resolver::shouldReceive('handleToDid')->with('alice.example')->andReturn('did:plc:alice');
        Resolver::shouldReceive('handleToDid')->with('bob.example')->andReturn('did:plc:bob');

        Http::fake([
            self::AUTH.'/oauth/par' => Http::response(['request_uri' => 'urn:ietf:params:oauth:request_uri:abc', 'expires_in' => 60], 201),
            self::AUTH.'/oauth/token' => Http::response(['access_token' => 'at', 'refresh_token' => 'rt', 'sub' => 'did:plc:alice', 'expires_in' => 300, 'scope' => 'atproto', 'token_type' => 'DPoP']),
        ]);

        Event::fake([SessionAuthenticated::class]);
    }

    public function test_a_login_can_start_at_a_server_without_naming_an_account(): void
    {
        $request = $this->engine()->authorizeAt(self::AUTH.'/', prompt: 'create');

        $this->assertSame(self::AUTH.'/oauth/authorize?request_uri=urn%3Aietf%3Aparams%3Aoauth%3Arequest_uri%3Aabc&client_id=https%3A%2F%2Fapp.example%2Foauth-client-metadata.json', $request->url);
        $this->assertNull($request->handle);
        $this->assertSame(self::AUTH, $request->authServerIssuer);

        Http::assertSent(fn (Request $r) => $r->url() === self::AUTH.'/oauth/par'
            && $r['prompt'] === 'create'
            && ! isset($r['login_hint']));
    }

    public function test_a_login_for_a_handle_sends_it_as_the_login_hint(): void
    {
        $this->engine()->authorize('alice.example');

        Http::assertSent(fn (Request $r) => $r->url() === self::AUTH.'/oauth/par'
            && $r['login_hint'] === 'alice.example'
            && ! isset($r['prompt']));
    }

    public function test_an_account_its_server_may_sign_in_is_stored_with_its_handle(): void
    {
        $this->keys->shouldReceive('store')->once();

        $token = $this->engine()->callback('code', 'state-1', $this->request(self::AUTH), self::AUTH);

        $this->assertSame('did:plc:alice', $token->did);
        $this->assertSame('alice.example', $token->handle, 'The handle comes from the DID document when the flow named none.');
        Event::assertDispatched(SessionAuthenticated::class);
    }

    public function test_an_account_whose_pds_uses_another_server_is_refused_before_anything_is_stored(): void
    {
        $this->keys->shouldNotReceive('store');

        // evil.example claims alice, whose PDS uses auth.example.
        Http::fake(['https://evil.example/oauth/token' => Http::response(['access_token' => 'at', 'refresh_token' => 'rt', 'sub' => 'did:plc:alice', 'expires_in' => 300])]);

        try {
            $this->engine()->callback('code', 'state-1', $this->request('https://evil.example'));
            $this->fail('A foreign account was accepted.');
        } catch (AuthenticationException $e) {
            $this->assertStringContainsString('may not sign in did:plc:alice', $e->getMessage());
        }

        Event::assertNotDispatched(SessionAuthenticated::class);
    }

    public function test_a_login_for_a_handle_must_land_on_that_handle(): void
    {
        $this->keys->shouldNotReceive('store');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Signed in as did:plc:alice, not bob.example');

        $this->engine()->callback('code', 'state-1', $this->request(self::AUTH, 'bob.example'));
    }

    public function test_a_redirect_from_another_issuer_is_refused_before_the_exchange(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Issuer mismatch');

        try {
            $this->engine()->callback('code', 'state-1', $this->request(self::AUTH), 'https://evil.example');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_the_fake_engine_records_server_starts(): void
    {
        $fake = new FakeOAuthEngine();
        $request = $fake->authorizeAt(self::AUTH, prompt: 'create');

        $this->assertSame(self::AUTH, $request->authServerIssuer);
        $this->assertSame(['authorizeAt', ['server' => self::AUTH, 'scopes' => null, 'prompt' => 'create']], $fake->recordedCalls()[0]);
    }

    protected function engine(): OAuthEngine
    {
        return $this->app->make(OAuthEngine::class);
    }

    protected function request(string $issuer, ?string $handle = null): AuthorizationRequest
    {
        return new AuthorizationRequest(
            url: $issuer.'/oauth/authorize',
            state: 'state-1',
            codeVerifier: 'verifier',
            dpopKey: FakeOAuthEngine::fakeDPoPKey(),
            requestUri: 'urn:test',
            pdsEndpoint: $issuer,
            handle: $handle,
            authServerIssuer: $issuer,
            tokenEndpoint: $issuer.'/oauth/token',
        );
    }

    protected function metadata(string $issuer, string $pds): AuthorizationServerMetadata
    {
        return new AuthorizationServerMetadata(
            issuer: $issuer,
            authorizationEndpoint: $issuer.'/oauth/authorize',
            tokenEndpoint: $issuer.'/oauth/token',
            parEndpoint: $issuer.'/oauth/par',
            pdsEndpoint: $pds,
        );
    }
}
