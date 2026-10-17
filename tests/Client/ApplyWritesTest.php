<?php

namespace SocialDept\AtpClient\Tests\Client;

use Carbon\Carbon;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use phpseclib3\Crypt\EC;
use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Enums\ScopeEnforcementLevel;
use SocialDept\AtpClient\Exceptions\MissingScopeException;
use SocialDept\AtpClient\Session\Session;
use SocialDept\AtpClient\Session\SessionManager;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Like;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\Create;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\CreateResult;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\Delete;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\DeleteResult;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\Update;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\UpdateResult;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\StrongRef;

class ApplyWritesTest extends TestCase
{
    protected const PDS = 'https://pds.example';

    protected const DID = 'did:plc:alice';

    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('atp-client.scope_enforcement', ScopeEnforcementLevel::Strict);
    }

    public function test_it_sends_every_write_with_its_union_type_in_one_call(): void
    {
        Http::fake([self::PDS.'/*' => Http::response($this->commitResponse(), 200)]);

        $this->client()->atproto->repo->applyWrites([
            new Create(collection: 'app.bsky.feed.post', value: ['$type' => 'app.bsky.feed.post', 'text' => 'hi'], rkey: '3abc'),
            new Update(collection: 'app.bsky.actor.profile', rkey: 'self', value: ['$type' => 'app.bsky.actor.profile', 'displayName' => 'Alice']),
            new Delete(collection: 'app.bsky.feed.like', rkey: '3xyz'),
        ]);

        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $request) {
            return $request->url() === self::PDS.'/xrpc/com.atproto.repo.applyWrites'
                && $request['repo'] === self::DID
                && ! array_key_exists('validate', $request->data())
                && $request['writes'] === [
                    ['$type' => 'com.atproto.repo.applyWrites#create', 'collection' => 'app.bsky.feed.post', 'value' => ['$type' => 'app.bsky.feed.post', 'text' => 'hi'], 'rkey' => '3abc'],
                    ['$type' => 'com.atproto.repo.applyWrites#update', 'collection' => 'app.bsky.actor.profile', 'rkey' => 'self', 'value' => ['$type' => 'app.bsky.actor.profile', 'displayName' => 'Alice']],
                    ['$type' => 'com.atproto.repo.applyWrites#delete', 'collection' => 'app.bsky.feed.like', 'rkey' => '3xyz'],
                ];
        });
    }

    public function test_a_record_given_as_data_is_serialized_with_its_type(): void
    {
        Http::fake([self::PDS.'/*' => Http::response($this->commitResponse(), 200)]);

        $like = new Like(
            subject: new StrongRef(uri: 'at://did:plc:bob/app.bsky.feed.post/3abc', cid: 'bafyreiabc'),
            createdAt: Carbon::parse('2026-10-17T12:00:00Z'),
        );

        $this->client()->atproto->repo->applyWrites([new Create(collection: 'app.bsky.feed.like', value: $like)]);

        Http::assertSent(fn (HttpRequest $request) => $request['writes'][0]['value']['$type'] === 'app.bsky.feed.like'
            && $request['writes'][0]['value']['subject']['uri'] === 'at://did:plc:bob/app.bsky.feed.post/3abc');
    }

    public function test_validate_and_swap_commit_are_sent_when_given(): void
    {
        Http::fake([self::PDS.'/*' => Http::response($this->commitResponse(), 200)]);

        $this->client()->atproto->repo->applyWrites(
            [new Delete(collection: 'app.bsky.feed.like', rkey: '3xyz')],
            validate: false,
            swapCommit: 'bafyreicommit',
        );

        Http::assertSent(fn (HttpRequest $request) => $request['validate'] === false && $request['swapCommit'] === 'bafyreicommit');
    }

    public function test_the_response_carries_the_commit_and_one_typed_result_per_write(): void
    {
        Http::fake([self::PDS.'/*' => Http::response($this->commitResponse(), 200)]);

        $response = $this->client()->atproto->repo->applyWrites([
            new Create(collection: 'app.bsky.feed.post', value: ['$type' => 'app.bsky.feed.post', 'text' => 'hi']),
            new Update(collection: 'app.bsky.actor.profile', rkey: 'self', value: ['$type' => 'app.bsky.actor.profile']),
            new Delete(collection: 'app.bsky.feed.like', rkey: '3xyz'),
        ]);

        $this->assertSame('3kcommitrev', $response->commit?->rev);
        $this->assertInstanceOf(CreateResult::class, $response->results[0]);
        $this->assertSame('at://did:plc:alice/app.bsky.feed.post/3abc', $response->results[0]->uri);
        $this->assertInstanceOf(UpdateResult::class, $response->results[1]);
        $this->assertInstanceOf(DeleteResult::class, $response->results[2]);
    }

    public function test_every_write_is_checked_against_the_session_scopes_before_sending(): void
    {
        Http::fake();

        $client = $this->client(['atproto', 'repo:app.bsky.feed.post?action=create']);

        try {
            $client->atproto->repo->applyWrites([
                new Create(collection: 'app.bsky.feed.post', value: ['$type' => 'app.bsky.feed.post', 'text' => 'hi']),
                new Delete(collection: 'app.bsky.feed.like', rkey: '3xyz'),
            ]);

            $this->fail('A write outside the granted scopes should be refused.');
        } catch (MissingScopeException) {
            Http::assertNothingSent();
        }
    }

    public function test_granular_repo_scopes_covering_every_write_are_enough(): void
    {
        Http::fake([self::PDS.'/*' => Http::response($this->commitResponse(), 200)]);

        $this->client(['atproto', 'repo:app.bsky.feed.post?action=create', 'repo:app.bsky.feed.like?action=delete'])
            ->atproto->repo->applyWrites([
                new Create(collection: 'app.bsky.feed.post', value: ['$type' => 'app.bsky.feed.post', 'text' => 'hi']),
                new Delete(collection: 'app.bsky.feed.like', rkey: '3xyz'),
            ]);

        Http::assertSentCount(1);
    }

    public function test_an_empty_list_of_writes_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->atproto->repo->applyWrites([]);
    }

    public function test_anything_other_than_a_write_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->atproto->repo->applyWrites([['collection' => 'app.bsky.feed.post']]);
    }

    /**
     * @param  array<int, string>  $scopes
     */
    protected function client(array $scopes = ['atproto', 'transition:generic']): AtpClient
    {
        $key = EC::createKey('secp256r1');

        $session = $this->createStub(Session::class);
        $session->method('did')->willReturn(self::DID);
        $session->method('pdsEndpoint')->willReturn(self::PDS);
        $session->method('accessToken')->willReturn('access-token');
        $session->method('isLegacy')->willReturn(false);
        $session->method('dpopKey')->willReturn(new DPoPKey($key, $key->getPublicKey(), 'kid-1'));
        $session->method('scopes')->willReturn($scopes);
        $session->method('hasScope')->willReturnCallback(fn (string $scope) => in_array($scope, $scopes, true));

        $sessions = $this->createStub(SessionManager::class);
        $sessions->method('ensureValid')->willReturn($session);
        $sessions->method('session')->willReturn($session);

        return new AtpClient($sessions, self::DID);
    }

    /**
     * @return array<string, mixed>
     */
    protected function commitResponse(): array
    {
        return [
            'commit' => ['cid' => 'bafyreicommit', 'rev' => '3kcommitrev'],
            'results' => [
                ['$type' => 'com.atproto.repo.applyWrites#createResult', 'uri' => 'at://did:plc:alice/app.bsky.feed.post/3abc', 'cid' => 'bafyreipost', 'validationStatus' => 'valid'],
                ['$type' => 'com.atproto.repo.applyWrites#updateResult', 'uri' => 'at://did:plc:alice/app.bsky.actor.profile/self', 'cid' => 'bafyreiprofile'],
                ['$type' => 'com.atproto.repo.applyWrites#deleteResult'],
            ],
        ];
    }
}
