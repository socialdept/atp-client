<?php

namespace SocialDept\AtpClient\Tests\Auth;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\ClientAssertionManager;
use SocialDept\AtpClient\Auth\ClientMetadataManager;
use SocialDept\AtpClient\Crypto\P256;

class ClientAssertionManagerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_refresh_assertion_middleware_swaps_client_assertion_with_unique_jti(): void
    {
        // Generate a real ES256 key for signing
        config()->set('atp-client.oauth.private_key', $this->generateTestKey());

        $metadata = Mockery::mock(ClientMetadataManager::class);
        $metadata->shouldReceive('getClientId')
            ->andReturn('https://example.test/oauth-client-metadata.json');

        $manager = new ClientAssertionManager($metadata);

        $audience = 'https://bsky.social';

        $originalParams = $manager->getAuthParams($audience);
        $originalAssertion = $originalParams['client_assertion'];

        $body = http_build_query($originalParams + ['grant_type' => 'authorization_code']);
        $request = new Request('POST', 'https://example.test/oauth/par', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], Utils::streamFor($body));

        $middleware = $manager->refreshAssertionMiddleware($audience);

        $updated = $middleware($request);
        parse_str((string) $updated->getBody(), $updatedParams);

        $this->assertArrayHasKey('client_assertion', $updatedParams);
        $this->assertNotSame($originalAssertion, $updatedParams['client_assertion']);
        $this->assertSame('authorization_code', $updatedParams['grant_type']);

        $this->assertNotSame(
            $this->jtiOf($originalAssertion),
            $this->jtiOf($updatedParams['client_assertion']),
        );
    }

    public function test_refresh_assertion_middleware_noops_when_assertion_not_required(): void
    {
        config()->set('atp-client.oauth.private_key', null);

        $metadata = Mockery::mock(ClientMetadataManager::class);

        $manager = new ClientAssertionManager($metadata);

        $request = new Request('POST', 'https://example.test', [], Utils::streamFor('foo=bar'));
        $middleware = $manager->refreshAssertionMiddleware('https://bsky.social');

        $result = $middleware($request);

        $this->assertSame($request, $result);
    }

    public function test_refresh_assertion_middleware_skips_when_body_has_no_assertion(): void
    {
        config()->set('atp-client.oauth.private_key', $this->generateTestKey());

        $metadata = Mockery::mock(ClientMetadataManager::class);

        $manager = new ClientAssertionManager($metadata);

        $request = new Request('POST', 'https://example.test', [], Utils::streamFor('foo=bar'));
        $middleware = $manager->refreshAssertionMiddleware('https://bsky.social');

        $result = $middleware($request);

        $this->assertSame('foo=bar', (string) $result->getBody());
    }

    private function jtiOf(string $jwt): string
    {
        [, $payload] = explode('.', $jwt);
        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        return $decoded['jti'];
    }

    private function generateTestKey(): string
    {
        return P256::create()->privateB64();
    }
}
