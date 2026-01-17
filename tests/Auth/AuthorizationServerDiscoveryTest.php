<?php

namespace SocialDept\AtpClient\Tests\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\AuthorizationServerDiscovery;
use SocialDept\AtpClient\Data\AuthorizationServerMetadata;

class AuthorizationServerDiscoveryTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_discovers_bluesky_auth_server_for_mushroom_pds(): void
    {
        // Mock the authorization server metadata endpoint
        Http::fake([
            'bsky.social/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://bsky.social',
                'authorization_endpoint' => 'https://bsky.social/oauth/authorize',
                'token_endpoint' => 'https://bsky.social/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://bsky.social/oauth/par',
                'revocation_endpoint' => 'https://bsky.social/oauth/revoke',
            ]),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://porcini.us-east.host.bsky.network');

        $this->assertInstanceOf(AuthorizationServerMetadata::class, $metadata);
        $this->assertEquals('https://bsky.social', $metadata->issuer);
        $this->assertEquals('https://bsky.social/oauth/authorize', $metadata->authorizationEndpoint);
        $this->assertEquals('https://bsky.social/oauth/token', $metadata->tokenEndpoint);
        $this->assertEquals('https://bsky.social/oauth/par', $metadata->parEndpoint);
        $this->assertEquals('https://porcini.us-east.host.bsky.network', $metadata->pdsEndpoint);
    }

    public function test_skips_protected_resource_discovery_for_bsky_network(): void
    {
        Http::fake([
            'bsky.social/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://bsky.social',
                'authorization_endpoint' => 'https://bsky.social/oauth/authorize',
                'token_endpoint' => 'https://bsky.social/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://bsky.social/oauth/par',
            ]),
            // Should NOT be called for *.bsky.network PDSes
            'shiitake.us-west.host.bsky.network/*' => Http::response([], 500),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://shiitake.us-west.host.bsky.network');

        // Should succeed without calling the PDS
        $this->assertEquals('https://bsky.social', $metadata->issuer);
    }

    public function test_discovers_auth_server_for_self_hosted_pds(): void
    {
        Http::fake([
            'pds.example.com/.well-known/oauth-protected-resource' => Http::response([
                'resource' => 'https://pds.example.com',
                'authorization_servers' => ['https://pds.example.com'],
            ]),
            'pds.example.com/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://pds.example.com',
                'authorization_endpoint' => 'https://pds.example.com/oauth/authorize',
                'token_endpoint' => 'https://pds.example.com/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://pds.example.com/oauth/par',
            ]),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://pds.example.com');

        $this->assertEquals('https://pds.example.com', $metadata->issuer);
        $this->assertEquals('https://pds.example.com/oauth/authorize', $metadata->authorizationEndpoint);
        $this->assertEquals('https://pds.example.com/oauth/token', $metadata->tokenEndpoint);
        $this->assertEquals('https://pds.example.com/oauth/par', $metadata->parEndpoint);
        $this->assertEquals('https://pds.example.com', $metadata->pdsEndpoint);
    }

    public function test_discovers_separate_auth_server_for_self_hosted_pds(): void
    {
        Http::fake([
            'pds.example.com/.well-known/oauth-protected-resource' => Http::response([
                'resource' => 'https://pds.example.com',
                'authorization_servers' => ['https://auth.example.com'],
            ]),
            'auth.example.com/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://auth.example.com',
                'authorization_endpoint' => 'https://auth.example.com/oauth/authorize',
                'token_endpoint' => 'https://auth.example.com/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://auth.example.com/oauth/par',
            ]),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://pds.example.com');

        $this->assertEquals('https://auth.example.com', $metadata->issuer);
        $this->assertEquals('https://auth.example.com/oauth/authorize', $metadata->authorizationEndpoint);
        $this->assertEquals('https://pds.example.com', $metadata->pdsEndpoint);
    }

    public function test_falls_back_to_pds_when_protected_resource_fails(): void
    {
        Http::fake([
            'pds.example.com/.well-known/oauth-protected-resource' => Http::response([], 404),
            'pds.example.com/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://pds.example.com',
                'authorization_endpoint' => 'https://pds.example.com/oauth/authorize',
                'token_endpoint' => 'https://pds.example.com/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://pds.example.com/oauth/par',
            ]),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://pds.example.com');

        $this->assertEquals('https://pds.example.com', $metadata->issuer);
    }

    public function test_falls_back_to_constructed_endpoints_when_metadata_fails(): void
    {
        Http::fake([
            'pds.example.com/.well-known/oauth-protected-resource' => Http::response([], 404),
            'pds.example.com/.well-known/oauth-authorization-server' => Http::response([], 500),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://pds.example.com');

        $this->assertEquals('https://pds.example.com', $metadata->issuer);
        $this->assertEquals('https://pds.example.com/oauth/authorize', $metadata->authorizationEndpoint);
        $this->assertEquals('https://pds.example.com/oauth/token', $metadata->tokenEndpoint);
        $this->assertEquals('https://pds.example.com/oauth/par', $metadata->parEndpoint);
    }

    public function test_caches_discovery_results(): void
    {
        Http::fake([
            'bsky.social/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://bsky.social',
                'authorization_endpoint' => 'https://bsky.social/oauth/authorize',
                'token_endpoint' => 'https://bsky.social/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://bsky.social/oauth/par',
            ]),
        ]);

        $discovery = new AuthorizationServerDiscovery();

        // First call
        $discovery->discover('https://porcini.us-east.host.bsky.network');

        // Second call should use cache
        $discovery->discover('https://porcini.us-east.host.bsky.network');

        // Should only make one HTTP request due to caching
        Http::assertSentCount(1);
    }

    public function test_clear_cache_removes_discovery_data(): void
    {
        Http::fake([
            'bsky.social/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://bsky.social',
                'authorization_endpoint' => 'https://bsky.social/oauth/authorize',
                'token_endpoint' => 'https://bsky.social/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://bsky.social/oauth/par',
            ]),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $pds = 'https://porcini.us-east.host.bsky.network';

        // First call populates cache
        $discovery->discover($pds);

        // Clear cache
        $discovery->clearCache($pds);

        // Second call should make a new request
        $discovery->discover($pds);

        Http::assertSentCount(2);
    }

    public function test_calocybe_mushroom_pds_uses_bsky_social(): void
    {
        Http::fake([
            'bsky.social/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://bsky.social',
                'authorization_endpoint' => 'https://bsky.social/oauth/authorize',
                'token_endpoint' => 'https://bsky.social/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://bsky.social/oauth/par',
                'revocation_endpoint' => 'https://bsky.social/oauth/revoke',
                'introspection_endpoint' => 'https://bsky.social/oauth/introspect',
            ]),
            // Mushroom PDS should NOT be called
            'calocybe.us-west.host.bsky.network/*' => Http::response([], 500),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://calocybe.us-west.host.bsky.network');

        // Should use bsky.social as auth server
        $this->assertEquals('https://bsky.social', $metadata->issuer);
        $this->assertEquals('https://bsky.social/oauth/authorize', $metadata->authorizationEndpoint);
        $this->assertEquals('https://bsky.social/oauth/token', $metadata->tokenEndpoint);
        $this->assertEquals('https://bsky.social/oauth/par', $metadata->parEndpoint);

        // PDS endpoint should remain the mushroom PDS
        $this->assertEquals('https://calocybe.us-west.host.bsky.network', $metadata->pdsEndpoint);

        // Verify we only called bsky.social, not the mushroom PDS
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'bsky.social');
        });
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'calocybe');
        });
    }

    public function test_selfhosted_pds_discovers_from_protected_resource(): void
    {
        Http::fake([
            // Self-hosted PDS returns itself as the auth server
            'selfhosted.social/.well-known/oauth-protected-resource' => Http::response([
                'resource' => 'https://selfhosted.social',
                'authorization_servers' => ['https://selfhosted.social'],
                'scopes_supported' => ['atproto', 'transition:generic'],
                'bearer_methods_supported' => ['header'],
            ]),
            'selfhosted.social/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://selfhosted.social',
                'authorization_endpoint' => 'https://selfhosted.social/oauth/authorize',
                'token_endpoint' => 'https://selfhosted.social/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://selfhosted.social/oauth/par',
                'revocation_endpoint' => 'https://selfhosted.social/oauth/revoke',
            ]),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://selfhosted.social');

        // Self-hosted PDS is its own auth server
        $this->assertEquals('https://selfhosted.social', $metadata->issuer);
        $this->assertEquals('https://selfhosted.social/oauth/authorize', $metadata->authorizationEndpoint);
        $this->assertEquals('https://selfhosted.social/oauth/token', $metadata->tokenEndpoint);
        $this->assertEquals('https://selfhosted.social/oauth/par', $metadata->parEndpoint);
        $this->assertEquals('https://selfhosted.social', $metadata->pdsEndpoint);

        // Verify both discovery endpoints were called
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'oauth-protected-resource');
        });
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'oauth-authorization-server');
        });
    }

    public function test_selfhosted_pds_with_separate_auth_server(): void
    {
        Http::fake([
            // PDS points to a separate auth server
            'pds.selfhosted.social/.well-known/oauth-protected-resource' => Http::response([
                'resource' => 'https://pds.selfhosted.social',
                'authorization_servers' => ['https://auth.selfhosted.social'],
            ]),
            'auth.selfhosted.social/.well-known/oauth-authorization-server' => Http::response([
                'issuer' => 'https://auth.selfhosted.social',
                'authorization_endpoint' => 'https://auth.selfhosted.social/oauth/authorize',
                'token_endpoint' => 'https://auth.selfhosted.social/oauth/token',
                'pushed_authorization_request_endpoint' => 'https://auth.selfhosted.social/oauth/par',
            ]),
        ]);

        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://pds.selfhosted.social');

        // Auth server is separate from PDS
        $this->assertEquals('https://auth.selfhosted.social', $metadata->issuer);
        $this->assertEquals('https://auth.selfhosted.social/oauth/authorize', $metadata->authorizationEndpoint);
        $this->assertEquals('https://auth.selfhosted.social/oauth/token', $metadata->tokenEndpoint);
        $this->assertEquals('https://auth.selfhosted.social/oauth/par', $metadata->parEndpoint);

        // PDS endpoint is still the original PDS
        $this->assertEquals('https://pds.selfhosted.social', $metadata->pdsEndpoint);
    }

    /**
     * @group integration
     */
    public function test_integration_calocybe_mushroom_pds_resolves_to_bsky_social(): void
    {
        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://calocybe.us-west.host.bsky.network');

        // Bluesky mushroom PDSes should resolve to bsky.social
        $this->assertEquals('https://bsky.social', $metadata->issuer);
        $this->assertEquals('https://bsky.social/oauth/authorize', $metadata->authorizationEndpoint);
        $this->assertEquals('https://bsky.social/oauth/token', $metadata->tokenEndpoint);
        $this->assertEquals('https://bsky.social/oauth/par', $metadata->parEndpoint);
        $this->assertEquals('https://bsky.social/oauth/revoke', $metadata->revocationEndpoint);

        // PDS endpoint should remain the mushroom PDS
        $this->assertEquals('https://calocybe.us-west.host.bsky.network', $metadata->pdsEndpoint);
    }

    /**
     * @group integration
     */
    public function test_integration_bsky_social_authorization_server_metadata(): void
    {
        $discovery = new AuthorizationServerDiscovery();
        $metadata = $discovery->discover('https://porcini.us-east.host.bsky.network');

        // Verify bsky.social returns expected OAuth metadata
        $this->assertEquals('https://bsky.social', $metadata->issuer);
        $this->assertStringStartsWith('https://bsky.social/oauth/authorize', $metadata->authorizationEndpoint);
        $this->assertStringStartsWith('https://bsky.social/oauth/token', $metadata->tokenEndpoint);
        $this->assertStringStartsWith('https://bsky.social/oauth/par', $metadata->parEndpoint);
    }

    /**
     * @group integration
     */
    public function test_integration_selfhosted_social_discovery(): void
    {
        $discovery = new AuthorizationServerDiscovery();

        try {
            $metadata = $discovery->discover('https://selfhosted.social');

            // Self-hosted PDS should discover its own auth server
            $this->assertNotEmpty($metadata->issuer);
            $this->assertNotEmpty($metadata->authorizationEndpoint);
            $this->assertNotEmpty($metadata->tokenEndpoint);
            $this->assertNotEmpty($metadata->parEndpoint);
            $this->assertEquals('https://selfhosted.social', $metadata->pdsEndpoint);
        } catch (\Exception $e) {
            // If selfhosted.social is not reachable, skip
            $this->markTestSkipped('selfhosted.social is not reachable: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     *
     * Tests that any *.bsky.network PDS resolves to bsky.social.
     * Uses fake/non-existent hostnames to verify the fast path works
     * without making network requests to the PDS itself.
     */
    public function test_integration_various_mushroom_pds_endpoints(): void
    {
        $mushroomPdses = [
            'https://porcini.us-east.host.bsky.network',
            'https://morel.us-east.host.bsky.network',
            'https://conocybe.us-west.host.bsky.network',
            'https://lionsmane.us-east.host.bsky.network',
            'https://nonexistent.fake.host.bsky.network', // Even fake ones should work
        ];

        $discovery = new AuthorizationServerDiscovery();

        foreach ($mushroomPdses as $pds) {
            $discovery->clearCache($pds);
            $metadata = $discovery->discover($pds);

            $this->assertEquals(
                'https://bsky.social',
                $metadata->issuer,
                "Expected {$pds} to resolve to bsky.social"
            );
            $this->assertEquals($pds, $metadata->pdsEndpoint);
        }
    }
}
