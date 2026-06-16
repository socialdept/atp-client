<?php

namespace SocialDept\AtpClient\Tests\Client;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Facades\Atp;

/**
 * Regression coverage for boolean query-param encoding on the PUBLIC XRPC path.
 *
 * The authenticated path (HasHttp::call) encodes booleans as "true"/"false", but the
 * public path (Client::publicCall) originally passed params straight to Http::get, which
 * serializes false → "0". Strict PDSes reject that with
 * `Expected boolean value type (got "0")`. These tests exercise the real public GET so a
 * future regression on the call site (not just the helper) is caught.
 */
class PublicQueryEncodingTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_public_get_serializes_false_boolean_as_literal_string(): void
    {
        Http::fake(['*' => Http::response(['records' => [], 'cursor' => null], 200)]);

        Atp::public('https://pds.example')->atproto->repo->listRecords(
            repo: 'did:plc:test',
            collection: 'site.standard.publication',
            limit: 100,
            reverse: false,
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'reverse=false')
                && ! str_contains($request->url(), 'reverse=0');
        });
    }

    public function test_public_get_serializes_true_boolean_as_literal_string(): void
    {
        Http::fake(['*' => Http::response(['records' => [], 'cursor' => null], 200)]);

        Atp::public('https://pds.example')->atproto->repo->listRecords(
            repo: 'did:plc:test',
            collection: 'site.standard.publication',
            reverse: true,
        );

        Http::assertSent(fn ($request) => str_contains($request->url(), 'reverse=true'));
    }
}
