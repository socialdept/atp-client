<?php

namespace SocialDept\AtpClient\Tests\Testing;

use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Testing\FakeResponse;

class FakeResponseTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    // ─── Actor Factories ─────────────────────────────────────────────

    public function test_profile_has_required_fields(): void
    {
        $profile = FakeResponse::profile();

        $this->assertArrayHasKey('did', $profile);
        $this->assertArrayHasKey('handle', $profile);
        $this->assertArrayHasKey('displayName', $profile);
        $this->assertArrayHasKey('followersCount', $profile);
        $this->assertArrayHasKey('followsCount', $profile);
        $this->assertArrayHasKey('postsCount', $profile);
        $this->assertStringStartsWith('did:plc:', $profile['did']);
    }

    public function test_profile_accepts_overrides(): void
    {
        $profile = FakeResponse::profile([
            'handle' => 'custom.bsky.social',
            'displayName' => 'Custom Name',
        ]);

        $this->assertEquals('custom.bsky.social', $profile['handle']);
        $this->assertEquals('Custom Name', $profile['displayName']);
    }

    public function test_profiles_generates_multiple(): void
    {
        $result = FakeResponse::profiles(3);

        $this->assertArrayHasKey('profiles', $result);
        $this->assertCount(3, $result['profiles']);
    }

    public function test_search_actors_typeahead(): void
    {
        $result = FakeResponse::searchActorsTypeahead(3);

        $this->assertArrayHasKey('actors', $result);
        $this->assertCount(3, $result['actors']);
    }

    public function test_search_actors(): void
    {
        $result = FakeResponse::searchActors(5, 'cursor123');

        $this->assertArrayHasKey('actors', $result);
        $this->assertCount(5, $result['actors']);
        $this->assertEquals('cursor123', $result['cursor']);
    }

    public function test_get_suggestions(): void
    {
        $result = FakeResponse::getSuggestions(4, 'cursor456');

        $this->assertArrayHasKey('actors', $result);
        $this->assertCount(4, $result['actors']);
        $this->assertEquals('cursor456', $result['cursor']);
    }

    public function test_get_suggestions_without_cursor(): void
    {
        $result = FakeResponse::getSuggestions(3);

        $this->assertArrayHasKey('actors', $result);
        $this->assertCount(3, $result['actors']);
        $this->assertArrayNotHasKey('cursor', $result);
    }

    // ─── Feed Factories ──────────────────────────────────────────────

    public function test_post_has_required_fields(): void
    {
        $post = FakeResponse::post();

        $this->assertArrayHasKey('uri', $post);
        $this->assertArrayHasKey('cid', $post);
        $this->assertArrayHasKey('author', $post);
        $this->assertArrayHasKey('record', $post);
        $this->assertStringStartsWith('at://', $post['uri']);
        $this->assertStringStartsWith('bafyrei', $post['cid']);
    }

    public function test_timeline_generates_feed_items(): void
    {
        $result = FakeResponse::timeline(5, 'cursor123');

        $this->assertArrayHasKey('feed', $result);
        $this->assertCount(5, $result['feed']);
        $this->assertEquals('cursor123', $result['cursor']);
    }

    public function test_timeline_without_cursor(): void
    {
        $result = FakeResponse::timeline(3);

        $this->assertArrayHasKey('feed', $result);
        $this->assertArrayNotHasKey('cursor', $result);
    }

    public function test_get_post_thread(): void
    {
        $result = FakeResponse::getPostThread();

        $this->assertArrayHasKey('thread', $result);
        $this->assertArrayHasKey('post', $result['thread']);
        $this->assertArrayHasKey('replies', $result['thread']);
        $this->assertEquals('app.bsky.feed.defs#threadViewPost', $result['thread']['$type']);
    }

    public function test_get_post_thread_with_overrides(): void
    {
        $result = FakeResponse::getPostThread([
            'post' => ['record' => ['text' => 'Custom post']],
        ]);

        $this->assertEquals('Custom post', $result['thread']['post']['record']['text']);
    }

    public function test_get_posts(): void
    {
        $result = FakeResponse::getPosts(4);

        $this->assertArrayHasKey('posts', $result);
        $this->assertCount(4, $result['posts']);
        $this->assertArrayHasKey('uri', $result['posts'][0]);
    }

    // ─── Repo Factories ──────────────────────────────────────────────

    public function test_create_record_has_required_fields(): void
    {
        $result = FakeResponse::createRecord();

        $this->assertArrayHasKey('uri', $result);
        $this->assertArrayHasKey('cid', $result);
        $this->assertArrayHasKey('commit', $result);
        $this->assertArrayHasKey('validationStatus', $result);
    }

    public function test_delete_record_has_commit(): void
    {
        $result = FakeResponse::deleteRecord();

        $this->assertArrayHasKey('commit', $result);
        $this->assertArrayHasKey('cid', $result['commit']);
        $this->assertArrayHasKey('rev', $result['commit']);
    }

    public function test_get_record(): void
    {
        $result = FakeResponse::getRecord();

        $this->assertArrayHasKey('uri', $result);
        $this->assertArrayHasKey('cid', $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertStringStartsWith('at://', $result['uri']);
    }

    public function test_get_record_with_overrides(): void
    {
        $result = FakeResponse::getRecord([
            'value' => ['$type' => 'app.bsky.feed.like', 'subject' => ['uri' => 'at://test', 'cid' => 'baf123']],
        ]);

        $this->assertEquals('app.bsky.feed.like', $result['value']['$type']);
    }

    public function test_list_records(): void
    {
        $result = FakeResponse::listRecords(3, 'cursor789');

        $this->assertArrayHasKey('records', $result);
        $this->assertCount(3, $result['records']);
        $this->assertEquals('cursor789', $result['cursor']);

        $record = $result['records'][0];
        $this->assertArrayHasKey('uri', $record);
        $this->assertArrayHasKey('cid', $record);
        $this->assertArrayHasKey('value', $record);
    }

    public function test_list_records_without_cursor(): void
    {
        $result = FakeResponse::listRecords(2);

        $this->assertArrayHasKey('records', $result);
        $this->assertCount(2, $result['records']);
        $this->assertArrayNotHasKey('cursor', $result);
    }

    // ─── Blob Factories ──────────────────────────────────────────────

    public function test_upload_blob_has_blob_reference(): void
    {
        $result = FakeResponse::uploadBlob('image/png', 12345);

        $this->assertArrayHasKey('blob', $result);
        $this->assertEquals('blob', $result['blob']['$type']);
        $this->assertEquals('image/png', $result['blob']['mimeType']);
        $this->assertEquals(12345, $result['blob']['size']);
    }

    // ─── Session Factories ───────────────────────────────────────────

    public function test_create_session_has_tokens(): void
    {
        $result = FakeResponse::createSession();

        $this->assertArrayHasKey('did', $result);
        $this->assertArrayHasKey('handle', $result);
        $this->assertArrayHasKey('accessJwt', $result);
        $this->assertArrayHasKey('refreshJwt', $result);
        $this->assertStringStartsWith('fake-access-', $result['accessJwt']);
    }

    public function test_refresh_session_has_new_tokens(): void
    {
        $result = FakeResponse::refreshSession();

        $this->assertArrayHasKey('did', $result);
        $this->assertArrayHasKey('accessJwt', $result);
        $this->assertArrayHasKey('refreshJwt', $result);
        $this->assertTrue($result['active']);
    }

    public function test_get_session(): void
    {
        $result = FakeResponse::getSession();

        $this->assertArrayHasKey('did', $result);
        $this->assertArrayHasKey('handle', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('emailConfirmed', $result);
        $this->assertArrayHasKey('active', $result);
        $this->assertTrue($result['active']);
    }

    public function test_get_session_with_overrides(): void
    {
        $result = FakeResponse::getSession([
            'did' => 'did:plc:custom',
            'handle' => 'custom.bsky.social',
        ]);

        $this->assertEquals('did:plc:custom', $result['did']);
        $this->assertEquals('custom.bsky.social', $result['handle']);
    }

    // ─── Graph Factories ─────────────────────────────────────────────

    public function test_followers(): void
    {
        $result = FakeResponse::followers(3);

        $this->assertArrayHasKey('subject', $result);
        $this->assertArrayHasKey('followers', $result);
        $this->assertCount(3, $result['followers']);
    }

    public function test_follows(): void
    {
        $result = FakeResponse::follows(4);

        $this->assertArrayHasKey('subject', $result);
        $this->assertArrayHasKey('follows', $result);
        $this->assertCount(4, $result['follows']);
    }

    // ─── Notification Factories ──────────────────────────────────────

    public function test_notifications(): void
    {
        $result = FakeResponse::notifications(5);

        $this->assertArrayHasKey('notifications', $result);
        $this->assertCount(5, $result['notifications']);
    }

    // ─── Generic Helpers ─────────────────────────────────────────────

    public function test_strong_ref(): void
    {
        $ref = FakeResponse::strongRef();

        $this->assertArrayHasKey('uri', $ref);
        $this->assertArrayHasKey('cid', $ref);
        $this->assertStringStartsWith('at://', $ref['uri']);
    }

    public function test_empty(): void
    {
        $result = FakeResponse::empty();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ─── Composable Shape Builders ───────────────────────────────────

    public function test_feed_list(): void
    {
        $result = FakeResponse::feedList(3, 'cursor123');

        $this->assertArrayHasKey('feed', $result);
        $this->assertCount(3, $result['feed']);
        $this->assertEquals('cursor123', $result['cursor']);
        $this->assertArrayHasKey('post', $result['feed'][0]);
    }

    public function test_feed_list_with_custom_key(): void
    {
        $result = FakeResponse::feedList(2, key: 'items');

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);
        $this->assertArrayNotHasKey('feed', $result);
    }

    public function test_feed_list_without_cursor(): void
    {
        $result = FakeResponse::feedList(2);

        $this->assertArrayHasKey('feed', $result);
        $this->assertArrayNotHasKey('cursor', $result);
    }

    public function test_profile_list(): void
    {
        $result = FakeResponse::profileList(4, 'cursor456');

        $this->assertArrayHasKey('actors', $result);
        $this->assertCount(4, $result['actors']);
        $this->assertEquals('cursor456', $result['cursor']);
        $this->assertArrayHasKey('did', $result['actors'][0]);
    }

    public function test_profile_list_with_custom_key(): void
    {
        $result = FakeResponse::profileList(3, key: 'followers');

        $this->assertArrayHasKey('followers', $result);
        $this->assertCount(3, $result['followers']);
        $this->assertArrayNotHasKey('actors', $result);
    }

    public function test_post_list(): void
    {
        $result = FakeResponse::postList(5, 'cursor789');

        $this->assertArrayHasKey('posts', $result);
        $this->assertCount(5, $result['posts']);
        $this->assertEquals('cursor789', $result['cursor']);
        $this->assertArrayHasKey('uri', $result['posts'][0]);
    }

    public function test_post_list_with_custom_key(): void
    {
        $result = FakeResponse::postList(2, key: 'quotes');

        $this->assertArrayHasKey('quotes', $result);
        $this->assertCount(2, $result['quotes']);
        $this->assertArrayNotHasKey('posts', $result);
    }

    public function test_cursor_list(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];
        $result = FakeResponse::cursorList($items, 'abc123', 'records');

        $this->assertArrayHasKey('records', $result);
        $this->assertCount(3, $result['records']);
        $this->assertEquals('abc123', $result['cursor']);
    }

    public function test_cursor_list_without_cursor(): void
    {
        $items = [['id' => 1]];
        $result = FakeResponse::cursorList($items);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayNotHasKey('cursor', $result);
    }

    // ─── Error Responses ─────────────────────────────────────────────

    public function test_ok_creates_success_response(): void
    {
        $response = FakeResponse::ok(['key' => 'value']);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('value', $response->json('key'));
    }

    public function test_error_creates_error_response(): void
    {
        $response = FakeResponse::error('TestError', 'Test message', 422);

        $this->assertEquals(422, $response->status());
        $this->assertEquals('TestError', $response->json('error'));
        $this->assertEquals('Test message', $response->json('message'));
    }

    public function test_make_creates_response_with_headers(): void
    {
        $response = FakeResponse::make(['data' => true], 201, ['X-Custom' => 'value']);

        $this->assertEquals(201, $response->status());
        $this->assertTrue($response->json('data'));
    }
}
