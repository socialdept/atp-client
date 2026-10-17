<?php

namespace SocialDept\AtpClient\Tests\Testing;

use LogicException;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Enums\Nsid\AtprotoRepo;
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpClient\Testing\FakeResponse;

/**
 * Repo writes under Atp::fake(). They take the repo from the account the
 * fake acts as, since the fake has no session to read it from.
 */
class FakeRepoWritesTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_repo_writes_go_to_the_account_the_fake_acts_as(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoRepo::CreateRecord, FakeResponse::createRecord(['did' => 'did:plc:alice']));
        $fake->stub(AtprotoRepo::PutRecord, FakeResponse::putRecord());
        $fake->stub(AtprotoRepo::DeleteRecord, FakeResponse::deleteRecord());

        $repo = Atp::as('did:plc:alice')->atproto->repo;

        $created = $repo->createRecord('com.example.thing', ['text' => 'hello'], 'one', validate: false);
        $repo->putRecord('com.example.thing', 'one', ['text' => 'again'], false, 'bafyold');
        $repo->deleteRecord('com.example.thing', 'one');

        $this->assertStringStartsWith('at://did:plc:alice/', $created->uri);
        $fake->assertCalledWith(AtprotoRepo::CreateRecord, fn ($r) => $r->body['repo'] === 'did:plc:alice' && $r->body['rkey'] === 'one' && $r->body['validate'] === false);
        $fake->assertCalledWith(AtprotoRepo::PutRecord, fn ($r) => $r->body['repo'] === 'did:plc:alice' && $r->body['swapRecord'] === 'bafyold');
        $fake->assertCalledWith(AtprotoRepo::DeleteRecord, fn ($r) => $r->body['repo'] === 'did:plc:alice' && $r->body['rkey'] === 'one');
    }

    public function test_record_helpers_write_under_the_fake_too(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoRepo::CreateRecord, FakeResponse::createRecord(['did' => 'did:plc:alice']));

        Atp::as('did:plc:alice')->bsky->post->create('Hello from a test');

        $fake->assertCalledWith(AtprotoRepo::CreateRecord, fn ($r) => $r->body['repo'] === 'did:plc:alice'
            && $r->body['collection'] === 'app.bsky.feed.post'
            && $r->body['record']['text'] === 'Hello from a test');
    }

    public function test_a_write_without_an_account_says_how_to_give_one(): void
    {
        Atp::fake();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Atp::as($did)');

        Atp::public()->atproto->repo->createRecord('com.example.thing', ['text' => 'hello']);
    }
}
