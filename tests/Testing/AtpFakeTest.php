<?php

namespace SocialDept\AtpClient\Tests\Testing;

use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Enums\Nsid\AtprotoRepo;
use SocialDept\AtpClient\Enums\Nsid\AtprotoServer;
use SocialDept\AtpClient\Enums\Nsid\BskyActor;
use SocialDept\AtpClient\Enums\Nsid\BskyFeed;
use SocialDept\AtpClient\Exceptions\AtpResponseException;
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpClient\Testing\FakeAtpManager;
use SocialDept\AtpClient\Testing\FakeResponse;
use SocialDept\AtpClient\Testing\ResponseSequence;

class AtpFakeTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    // ─── Basic Faking ────────────────────────────────────────────────

    public function test_fake_returns_fake_manager(): void
    {
        $fake = Atp::fake();

        $this->assertInstanceOf(FakeAtpManager::class, $fake);
    }

    public function test_fake_with_stubs_returns_stubbed_data(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile([
            'handle' => 'alice.bsky.social',
            'displayName' => 'Alice',
        ]));

        $profile = Atp::public()->bsky->actor->getProfile('alice.bsky.social');

        $this->assertEquals('alice.bsky.social', $profile->handle);
        $this->assertEquals('Alice', $profile->displayName);
    }

    public function test_fake_without_stubs_returns_empty_success(): void
    {
        Atp::fake();

        // Any unstubbed endpoint returns empty 200
        $response = Atp::public()->client->get(BskyActor::GetProfile->value, ['actor' => 'test']);

        $this->assertEquals(200, $response->status());
    }

    // ─── Enum Support ────────────────────────────────────────────────

    public function test_stub_method_accepts_enum(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile(['handle' => 'stubbed.bsky.social']));

        $profile = Atp::public()->bsky->actor->getProfile('test');
        $this->assertEquals('stubbed.bsky.social', $profile->handle);
    }

    public function test_stub_with_enum_and_string_are_interchangeable(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile(['handle' => 'from-enum.bsky.social']));
        $fake->stub(BskyFeed::GetTimeline, FakeResponse::timeline(2));

        $profile = Atp::public()->bsky->actor->getProfile('test');
        $this->assertEquals('from-enum.bsky.social', $profile->handle);

        $timeline = Atp::public()->client->get(BskyFeed::GetTimeline->value);
        $this->assertCount(2, $timeline->json('feed'));
    }

    public function test_sequence_method_accepts_enum(): void
    {
        $fake = Atp::fake();

        $fake->sequence(BskyActor::GetProfile)
            ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'first.bsky.social'])))
            ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'second.bsky.social'])));

        $first = Atp::public()->bsky->actor->getProfile('any');
        $second = Atp::public()->bsky->actor->getProfile('any');

        $this->assertEquals('first.bsky.social', $first->handle);
        $this->assertEquals('second.bsky.social', $second->handle);
    }

    // ─── Assertions with Enums ───────────────────────────────────────

    public function test_assert_called_with_enum(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile());

        Atp::public()->bsky->actor->getProfile('test.bsky.social');

        $fake->assertCalled(BskyActor::GetProfile);
    }

    public function test_assert_not_called_with_enum(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile());

        $fake->assertNotCalled(BskyActor::GetProfile);
    }

    public function test_assert_called_times_with_enum(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile());

        Atp::public()->bsky->actor->getProfile('test.bsky.social');
        Atp::public()->bsky->actor->getProfile('other.bsky.social');

        $fake->assertCalledTimes(BskyActor::GetProfile, 2);
    }

    public function test_assert_called_with_callback_and_enum(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile());

        Atp::public()->bsky->actor->getProfile('alice.bsky.social');

        $fake->assertCalledWith(BskyActor::GetProfile, function ($request) {
            return $request->hasParam('actor', 'alice.bsky.social');
        });
    }

    public function test_assert_called_publicly_with_enum(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile());

        Atp::public()->bsky->actor->getProfile('test.bsky.social');

        $fake->assertCalledPublicly(BskyActor::GetProfile);
    }

    public function test_assert_called_authenticated_with_enum(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile());

        Atp::as('did:plc:test123')->bsky->actor->getProfile('other.bsky.social');

        $fake->assertCalledAuthenticated(BskyActor::GetProfile);
        $fake->assertCalledAuthenticated(BskyActor::GetProfile, 'did:plc:test123');
    }

    public function test_get_recorded_for_with_enum(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile());

        Atp::public()->bsky->actor->getProfile('test.bsky.social');

        $recorded = $fake->getRecordedFor(BskyActor::GetProfile);
        $this->assertCount(1, $recorded);
        $this->assertEquals('app.bsky.actor.getProfile', $recorded[0]->endpoint);
    }

    public function test_assert_nothing_called(): void
    {
        $fake = Atp::fake();

        $fake->assertNothingCalled();
    }

    // ─── Error Simulation ────────────────────────────────────────────

    public function test_error_response_throws_atp_exception(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::error('InvalidRequest', 'Bad input', 400));

        $this->expectException(AtpResponseException::class);

        try {
            Atp::public()->bsky->actor->getProfile('invalid');
        } catch (AtpResponseException $e) {
            $this->assertEquals('InvalidRequest', $e->errorCode);
            $this->assertEquals('Bad input', $e->errorMessage);
            $this->assertEquals(400, $e->httpStatus);
            $this->assertEquals('app.bsky.actor.getProfile', $e->endpoint);

            throw $e;
        }
    }

    public function test_expired_token_error(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::RefreshSession, FakeResponse::expiredToken());

        try {
            Atp::public()->client->get(AtprotoServer::RefreshSession->value);
            $this->fail('Expected AtpResponseException');
        } catch (AtpResponseException $e) {
            $this->assertEquals('ExpiredToken', $e->errorCode);
            $this->assertEquals(401, $e->httpStatus);
        }
    }

    public function test_invalid_token_error(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::RefreshSession, FakeResponse::invalidToken());

        try {
            Atp::public()->client->get(AtprotoServer::RefreshSession->value);
            $this->fail('Expected AtpResponseException');
        } catch (AtpResponseException $e) {
            $this->assertEquals('InvalidToken', $e->errorCode);
            $this->assertEquals(401, $e->httpStatus);
        }
    }

    public function test_account_takedown_error(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::CreateSession, FakeResponse::accountTakedown());

        try {
            Atp::public()->client->post(AtprotoServer::CreateSession->value, []);
            $this->fail('Expected AtpResponseException');
        } catch (AtpResponseException $e) {
            $this->assertEquals('AccountTakedown', $e->errorCode);
            $this->assertEquals(401, $e->httpStatus);
        }
    }

    public function test_auth_factor_required_error(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::CreateSession, FakeResponse::authFactorTokenRequired());

        try {
            Atp::public()->client->post(AtprotoServer::CreateSession->value, []);
            $this->fail('Expected AtpResponseException');
        } catch (AtpResponseException $e) {
            $this->assertEquals('AuthFactorTokenRequired', $e->errorCode);
        }
    }

    public function test_invalid_swap_error(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoRepo::CreateRecord, FakeResponse::invalidSwap());

        try {
            Atp::public()->client->post(AtprotoRepo::CreateRecord->value, []);
            $this->fail('Expected AtpResponseException');
        } catch (AtpResponseException $e) {
            $this->assertEquals('InvalidSwap', $e->errorCode);
            $this->assertEquals(400, $e->httpStatus);
        }
    }

    public function test_rate_limited_error(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyFeed::GetTimeline, FakeResponse::rateLimited(60));

        try {
            Atp::public()->client->get(BskyFeed::GetTimeline->value);
            $this->fail('Expected AtpResponseException');
        } catch (AtpResponseException $e) {
            $this->assertEquals('RateLimitExceeded', $e->errorCode);
            $this->assertEquals(429, $e->httpStatus);
        }
    }

    // ─── Response Sequences ──────────────────────────────────────────

    public function test_response_sequence(): void
    {
        $fake = Atp::fake();

        $fake->sequence(BskyActor::GetProfile)
            ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'first.bsky.social'])))
            ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'second.bsky.social'])));

        $first = Atp::public()->bsky->actor->getProfile('any');
        $second = Atp::public()->bsky->actor->getProfile('any');

        $this->assertEquals('first.bsky.social', $first->handle);
        $this->assertEquals('second.bsky.social', $second->handle);
    }

    public function test_sequence_with_error_then_success(): void
    {
        $fake = Atp::fake();

        $fake->sequence(BskyActor::GetProfile)
            ->push(FakeResponse::error('InvalidRequest', 'Bad input', 400))
            ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'success.bsky.social'])));

        // First call throws
        try {
            Atp::public()->bsky->actor->getProfile('test');
            $this->fail('Expected exception');
        } catch (AtpResponseException $e) {
            $this->assertEquals('InvalidRequest', $e->errorCode);
        }

        // Second call succeeds
        $profile = Atp::public()->bsky->actor->getProfile('test');
        $this->assertEquals('success.bsky.social', $profile->handle);
    }

    public function test_sequence_with_fallback(): void
    {
        $fake = Atp::fake();

        $fake->sequence(BskyActor::GetProfile)
            ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'first.bsky.social'])))
            ->whenEmpty(FakeResponse::ok(FakeResponse::profile(['handle' => 'fallback.bsky.social'])));

        Atp::public()->bsky->actor->getProfile('any');
        $second = Atp::public()->bsky->actor->getProfile('any');
        $third = Atp::public()->bsky->actor->getProfile('any');

        // Both exhausted calls hit fallback
        $this->assertEquals('fallback.bsky.social', $second->handle);
        $this->assertEquals('fallback.bsky.social', $third->handle);
    }

    // ─── Wildcard Matching ───────────────────────────────────────────

    public function test_wildcard_stub_matching(): void
    {
        Atp::fake([
            'app.bsky.actor.*' => FakeResponse::profile(['handle' => 'wildcard.bsky.social']),
        ]);

        $profile = Atp::public()->bsky->actor->getProfile('test');

        $this->assertEquals('wildcard.bsky.social', $profile->handle);
    }

    public function test_exact_match_takes_priority_over_wildcard(): void
    {
        $fake = Atp::fake([
            'app.bsky.actor.*' => FakeResponse::profile(['handle' => 'wildcard.bsky.social']),
        ]);
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile(['handle' => 'exact.bsky.social']));

        $profile = Atp::public()->bsky->actor->getProfile('test');

        $this->assertEquals('exact.bsky.social', $profile->handle);
    }

    // ─── Callable Stubs ──────────────────────────────────────────────

    public function test_callable_stubs(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, function (string $endpoint, ?array $params) {
            return FakeResponse::ok(FakeResponse::profile([
                'handle' => $params['actor'] ?? 'unknown',
            ]));
        });

        $profile = Atp::public()->bsky->actor->getProfile('dynamic.bsky.social');

        $this->assertEquals('dynamic.bsky.social', $profile->handle);
    }

    // ─── Prevent Stray Requests ──────────────────────────────────────

    public function test_prevent_stray_requests(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile());
        $fake->preventStrayRequests();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected ATP request');

        Atp::public()->client->get(BskyFeed::GetTimeline->value);
    }

    // ─── Authenticated Mode ──────────────────────────────────────────

    public function test_authenticated_mode_works(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyFeed::GetTimeline, FakeResponse::timeline(3));

        $response = Atp::as('did:plc:test123')->client->get(BskyFeed::GetTimeline->value);

        $this->assertArrayHasKey('feed', $response->toArray());
        $this->assertCount(3, $response->toArray()['feed']);

        $fake->assertCalled(BskyFeed::GetTimeline);
        $fake->assertCalledAuthenticated(BskyFeed::GetTimeline, 'did:plc:test123');
    }

    // ─── OAuth Fake ──────────────────────────────────────────────────

    public function test_fake_oauth_engine(): void
    {
        $fake = Atp::fake();

        $oauth = $fake->oauth();
        $request = $oauth->authorize('test.bsky.social');

        $this->assertEquals('fake-state', $request->state);
        $this->assertCount(1, $oauth->recordedCalls());
    }

    // ─── Wildcard Assertions ─────────────────────────────────────────

    public function test_assert_called_with_wildcard(): void
    {
        $fake = Atp::fake();
        $fake->stub(BskyActor::GetProfile, FakeResponse::profile());

        Atp::public()->bsky->actor->getProfile('test.bsky.social');

        $fake->assertCalled('app.bsky.actor.*');
    }
}
