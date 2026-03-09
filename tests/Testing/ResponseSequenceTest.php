<?php

namespace SocialDept\AtpClient\Tests\Testing;

use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Testing\FakeResponse;
use SocialDept\AtpClient\Testing\ResponseSequence;

class ResponseSequenceTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_returns_responses_in_order(): void
    {
        $sequence = new ResponseSequence();
        $sequence->push(FakeResponse::ok(['order' => 1]));
        $sequence->push(FakeResponse::ok(['order' => 2]));
        $sequence->push(FakeResponse::ok(['order' => 3]));

        $this->assertEquals(1, $sequence->next('test')->json('order'));
        $this->assertEquals(2, $sequence->next('test')->json('order'));
        $this->assertEquals(3, $sequence->next('test')->json('order'));
    }

    public function test_throws_when_exhausted(): void
    {
        $sequence = new ResponseSequence();
        $sequence->push(FakeResponse::ok());

        $sequence->next('test'); // consume the only response

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Response sequence for [test.endpoint] is empty');

        $sequence->next('test.endpoint');
    }

    public function test_fallback_when_exhausted(): void
    {
        $sequence = new ResponseSequence();
        $sequence->push(FakeResponse::ok(['order' => 1]));
        $sequence->whenEmpty(FakeResponse::ok(['order' => 'fallback']));

        $sequence->next('test'); // consume

        $this->assertEquals('fallback', $sequence->next('test')->json('order'));
        $this->assertEquals('fallback', $sequence->next('test')->json('order'));
    }

    public function test_dont_fail_when_empty(): void
    {
        $sequence = new ResponseSequence();
        $sequence->push(FakeResponse::ok(['order' => 1]));
        $sequence->dontFailWhenEmpty();

        $sequence->next('test'); // consume

        // Should return empty 200 instead of throwing
        $response = $sequence->next('test');
        $this->assertEquals(200, $response->status());
    }

    public function test_push_error(): void
    {
        $sequence = new ResponseSequence();
        $sequence->pushError('InvalidToken', 'Token expired', 401);

        $response = $sequence->next('test');
        $this->assertEquals(401, $response->status());
        $this->assertEquals('InvalidToken', $response->json('error'));
    }

    public function test_push_ok(): void
    {
        $sequence = new ResponseSequence();
        $sequence->pushOk(['key' => 'value']);

        $response = $sequence->next('test');
        $this->assertEquals(200, $response->status());
        $this->assertEquals('value', $response->json('key'));
    }

    public function test_has_more(): void
    {
        $sequence = new ResponseSequence();
        $this->assertFalse($sequence->hasMore());

        $sequence->push(FakeResponse::ok());
        $this->assertTrue($sequence->hasMore());

        $sequence->next('test');
        $this->assertFalse($sequence->hasMore());
    }

    public function test_closure_responses(): void
    {
        $sequence = new ResponseSequence();
        $sequence->push(function (string $endpoint) {
            return FakeResponse::ok(['endpoint' => $endpoint]);
        });

        $response = $sequence->next('app.bsky.actor.getProfile');
        $this->assertEquals('app.bsky.actor.getProfile', $response->json('endpoint'));
    }

    public function test_array_responses_converted_to_fake_response(): void
    {
        $sequence = new ResponseSequence();
        $sequence->push(['key' => 'from_array']);

        $response = $sequence->next('test');
        $this->assertEquals(200, $response->status());
        $this->assertEquals('from_array', $response->json('key'));
    }
}
