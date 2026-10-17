<?php

namespace SocialDept\AtpClient\Tests\Http;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use LogicException;
use Orchestra\Testbench\TestCase;
use phpseclib3\Crypt\EC;
use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Client\Client;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpClient\Http\ForwardedResponse;
use SocialDept\AtpClient\Session\Session;
use SocialDept\AtpClient\Session\SessionManager;
use SocialDept\AtpClient\Testing\FakeResponse;

class ForwardTest extends TestCase
{
    protected const PDS = 'https://pds.example';

    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function test_a_get_is_forwarded_with_its_query_and_only_allowlisted_headers(): void
    {
        Http::fake([self::PDS.'/*' => Http::response(['feed' => []], 200)]);

        $this->client()->forward('GET', 'app.bsky.feed.getPosts', 'uris=at%3A%2F%2Fa&uris=at%3A%2F%2Fb', null, [
            'atproto-proxy' => 'did:web:api.example#atproto_appview',
            'Atproto-Accept-Labelers' => 'did:plc:labeler',
            'Accept-Language' => 'en',
            'Cookie' => 'session=secret',
            'Authorization' => 'Bearer browser-token',
            'X-Forwarded-For' => '10.0.0.1',
        ]);

        Http::assertSent(function (HttpRequest $request) {
            return $request->method() === 'GET'
                && $request->url() === self::PDS.'/xrpc/app.bsky.feed.getPosts?uris=at%3A%2F%2Fa&uris=at%3A%2F%2Fb'
                && $request->header('atproto-proxy') === ['did:web:api.example#atproto_appview']
                && $request->header('atproto-accept-labelers') === ['did:plc:labeler']
                && $request->header('accept-language') === ['en']
                && $request->header('Authorization') === ['DPoP access-token']
                && $request->hasHeader('DPoP')
                && ! $request->hasHeader('Cookie')
                && ! $request->hasHeader('X-Forwarded-For');
        });
    }

    public function test_a_post_carries_its_body_and_content_type(): void
    {
        Http::fake([self::PDS.'/*' => Http::response(['uri' => 'at://x'], 200)]);

        $this->client()->forward('POST', 'com.atproto.repo.createRecord', null, '{"repo":"did:plc:alice"}', [
            'Content-Type' => 'application/json',
        ]);

        Http::assertSent(fn (HttpRequest $request) => $request->method() === 'POST'
            && $request->body() === '{"repo":"did:plc:alice"}'
            && $request->header('Content-Type') === ['application/json']);
    }

    public function test_an_error_from_the_pds_is_returned_not_thrown(): void
    {
        Http::fake([self::PDS.'/*' => Http::response(['error' => 'InvalidRequest', 'message' => 'nope'], 400)]);

        $response = $this->client()->forward('GET', 'app.bsky.feed.getPosts');

        $this->assertSame(400, $response->status());
        $this->assertSame('InvalidRequest', $response->json('error'));
    }

    public function test_a_stale_token_is_refreshed_once_and_the_call_replayed(): void
    {
        // DPoPClient makes two attempts per call, so both see the stale token
        // before the refresh replays it.
        Http::fake([self::PDS.'/*' => Http::sequence()
            ->push(['error' => 'ExpiredToken'], 401)
            ->push(['error' => 'ExpiredToken'], 401)
            ->push(['ok' => true], 200)]);

        $sessions = $this->createMock(SessionManager::class);
        $sessions->method('ensureValid')->willReturn($this->fakeSession());
        $sessions->expects($this->once())->method('refresh')->with('did:plc:alice');

        $response = $this->client($sessions)->forward('GET', 'app.bsky.actor.getPreferences');

        $this->assertSame(200, $response->status());
        Http::assertSentCount(3);
    }

    public function test_a_401_that_is_not_a_stale_token_keeps_its_body(): void
    {
        Http::fake([self::PDS.'/*' => Http::response(['error' => 'use_dpop_nonce'], 401)]);

        $response = $this->client()->forward('GET', 'app.bsky.actor.getPreferences');

        $this->assertSame(401, $response->status());
        $this->assertSame('use_dpop_nonce', $response->json('error'));
    }

    public function test_forwarding_needs_an_authenticated_client(): void
    {
        $this->expectException(LogicException::class);

        (new Client($this->createStub(AtpClient::class)))->forward('GET', 'app.bsky.feed.getPosts');
    }

    public function test_only_get_and_post_are_forwarded(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->forward('PUT', 'app.bsky.feed.getPosts');
    }

    public function test_the_relayed_response_keeps_status_body_and_only_content_headers(): void
    {
        Http::fake([self::PDS.'/*' => Http::response('{"posts":[]}', 200, [
            'Content-Type' => 'application/json',
            'atproto-content-labelers' => 'did:plc:labeler',
            'RateLimit-Remaining' => '99',
            'DPoP-Nonce' => 'secret-nonce',
            'WWW-Authenticate' => 'DPoP error="use_dpop_nonce"',
            'Set-Cookie' => 'pds=1',
        ])]);

        $relayed = ForwardedResponse::toSymfony($this->client()->forward('GET', 'app.bsky.feed.getPosts'));

        ob_start();
        $relayed->sendContent();
        $content = ob_get_clean();

        $this->assertSame(200, $relayed->getStatusCode());
        $this->assertSame('{"posts":[]}', $content);
        $this->assertSame('application/json', $relayed->headers->get('content-type'));
        $this->assertSame('did:plc:labeler', $relayed->headers->get('atproto-content-labelers'));
        $this->assertSame('99', $relayed->headers->get('ratelimit-remaining'));
        $this->assertFalse($relayed->headers->has('dpop-nonce'));
        $this->assertFalse($relayed->headers->has('www-authenticate'));
        $this->assertFalse($relayed->headers->has('set-cookie'));
    }

    public function test_the_fake_records_forwarded_calls_and_returns_errors_untouched(): void
    {
        $fake = Atp::fake();
        $fake->stub('app.bsky.feed.getPosts', FakeResponse::error('InvalidRequest', 'nope', 400));

        $response = Atp::as('did:plc:alice')->client->forward('GET', 'app.bsky.feed.getPosts', 'limit=5', null, [
            'atproto-proxy' => 'did:web:api.example#atproto_appview',
            'Cookie' => 'session=secret',
        ]);

        $this->assertSame(400, $response->status());
        $fake->assertCalledWith('app.bsky.feed.getPosts', fn ($request) => $request->method === 'FORWARD_GET'
            && $request->hasParam('limit', '5')
            && $request->params['_headers'] === ['atproto-proxy' => 'did:web:api.example#atproto_appview']);
    }

    protected function client(?SessionManager $sessions = null): Client
    {
        return new Client($this->createStub(AtpClient::class), $sessions ?? $this->sessions(), 'did:plc:alice');
    }

    protected function sessions(): SessionManager
    {
        $sessions = $this->createStub(SessionManager::class);
        $sessions->method('ensureValid')->willReturn($this->fakeSession());

        return $sessions;
    }

    protected function fakeSession(): Session
    {
        $key = EC::createKey('secp256r1');

        $session = $this->createStub(Session::class);
        $session->method('pdsEndpoint')->willReturn(self::PDS);
        $session->method('accessToken')->willReturn('access-token');
        $session->method('isLegacy')->willReturn(false);
        $session->method('dpopKey')->willReturn(new DPoPKey($key, $key->getPublicKey(), 'kid-1'));

        return $session;
    }
}
