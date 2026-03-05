<?php

namespace SocialDept\AtpClient\Tests\Auth;

use Mockery;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\AuthorizationServerDiscovery;
use SocialDept\AtpClient\Auth\ClientAssertionManager;
use SocialDept\AtpClient\Auth\ClientMetadataManager;
use SocialDept\AtpClient\Auth\DPoPKeyManager;
use SocialDept\AtpClient\Auth\OAuthEngine;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AuthorizationRequest;
use SocialDept\AtpClient\Data\AuthorizationServerMetadata;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Http\DPoPClient;

class OAuthEngineTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_prepare_authorization_returns_metadata_without_par(): void
    {
        $metadata = Mockery::mock(ClientMetadataManager::class);
        $metadata->shouldReceive('getRedirectUris')
            ->andReturn(['https://unthread.test/oauth/callback']);
        $metadata->shouldReceive('getScopes')
            ->andReturn(['atproto', 'transition:generic']);
        $metadata->shouldReceive('getClientId')
            ->andReturn('https://unthread.at/oauth-client-metadata.json');

        $discovery = Mockery::mock(AuthorizationServerDiscovery::class);
        $discovery->shouldReceive('discover')
            ->with('https://bsky.network')
            ->once()
            ->andReturn(new AuthorizationServerMetadata(
                issuer: 'https://bsky.social',
                authorizationEndpoint: 'https://bsky.social/oauth/authorize',
                tokenEndpoint: 'https://bsky.social/oauth/token',
                parEndpoint: 'https://bsky.social/oauth/par',
                pdsEndpoint: 'https://bsky.network',
            ));

        $engine = new OAuthEngine(
            dpopManager: Mockery::mock(DPoPKeyManager::class),
            metadata: $metadata,
            dpopClient: Mockery::mock(DPoPClient::class),
            clientAssertion: Mockery::mock(ClientAssertionManager::class),
            keyStore: Mockery::mock(KeyStore::class),
            discovery: $discovery,
        );

        // Pass pdsEndpoint directly to skip Resolver::resolvePds()
        $result = $engine->prepareAuthorization('test.bsky.social', pdsEndpoint: 'https://bsky.network');

        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('codeVerifier', $result);
        $this->assertArrayHasKey('codeChallenge', $result);
        $this->assertEquals('S256', $result['codeChallengeMethod']);
        $this->assertEquals('atproto transition:generic', $result['scopes']);
        $this->assertEquals('https://bsky.social/oauth/par', $result['parEndpoint']);
        $this->assertEquals('https://bsky.social/oauth/authorize', $result['authorizationEndpoint']);
        $this->assertEquals('https://bsky.social', $result['issuer']);
        $this->assertEquals('https://bsky.social/oauth/token', $result['tokenEndpoint']);
        $this->assertEquals('https://unthread.test/oauth/callback', $result['redirectUri']);
        $this->assertEquals('https://unthread.at/oauth-client-metadata.json', $result['clientId']);
        $this->assertEquals('https://bsky.network', $result['pdsEndpoint']);
        $this->assertEquals('test.bsky.social', $result['handle']);
    }

    public function test_callback_without_exchange_returns_code_and_metadata(): void
    {
        $metadata = Mockery::mock(ClientMetadataManager::class);
        $metadata->shouldReceive('getRedirectUris')
            ->andReturn(['https://unthread.test/oauth/callback']);

        $engine = new OAuthEngine(
            dpopManager: Mockery::mock(DPoPKeyManager::class),
            metadata: $metadata,
            dpopClient: Mockery::mock(DPoPClient::class),
            clientAssertion: Mockery::mock(ClientAssertionManager::class),
            keyStore: Mockery::mock(KeyStore::class),
            discovery: Mockery::mock(AuthorizationServerDiscovery::class),
        );

        $request = $this->makeAuthRequest();

        $result = $engine->callbackWithoutExchange(
            code: 'test-auth-code',
            state: $request->state,
            request: $request,
        );

        $this->assertEquals('test-auth-code', $result['code']);
        $this->assertEquals($request->codeVerifier, $result['codeVerifier']);
        $this->assertEquals('https://unthread.test/oauth/callback', $result['redirectUri']);
        $this->assertEquals('https://bsky.social', $result['issuer']);
        $this->assertEquals('https://bsky.social/oauth/token', $result['tokenEndpoint']);
        $this->assertEquals('https://bsky.network', $result['pdsEndpoint']);
        $this->assertEquals('test.bsky.social', $result['handle']);
    }

    public function test_callback_without_exchange_rejects_state_mismatch(): void
    {
        $metadata = Mockery::mock(ClientMetadataManager::class);

        $engine = new OAuthEngine(
            dpopManager: Mockery::mock(DPoPKeyManager::class),
            metadata: $metadata,
            dpopClient: Mockery::mock(DPoPClient::class),
            clientAssertion: Mockery::mock(ClientAssertionManager::class),
            keyStore: Mockery::mock(KeyStore::class),
            discovery: Mockery::mock(AuthorizationServerDiscovery::class),
        );

        $request = $this->makeAuthRequest();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('State mismatch');

        $engine->callbackWithoutExchange(
            code: 'test-auth-code',
            state: 'wrong-state',
            request: $request,
        );
    }

    private function makeAuthRequest(): AuthorizationRequest
    {
        $privateKey = \phpseclib3\Crypt\EC::createKey('secp256r1');

        return new AuthorizationRequest(
            url: 'https://bsky.social/oauth/authorize?request_uri=urn:test',
            state: 'test-state-123',
            codeVerifier: 'test-verifier-456',
            dpopKey: new DPoPKey(
                privateKey: $privateKey,
                publicKey: $privateKey->getPublicKey(),
                keyId: 'test-key',
            ),
            requestUri: 'urn:test',
            pdsEndpoint: 'https://bsky.network',
            handle: 'test.bsky.social',
            authServerIssuer: 'https://bsky.social',
            tokenEndpoint: 'https://bsky.social/oauth/token',
        );
    }
}
