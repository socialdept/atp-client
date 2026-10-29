<?php

namespace SocialDept\AtpClient\Tests\Auth;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\AuthorizationServerDiscovery;
use SocialDept\AtpClient\Auth\ClientMetadataManager;
use SocialDept\AtpClient\Auth\OAuthEngine;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AuthorizationServerMetadata;

/**
 * ATP_OAUTH_SCOPE decides the scopes a sign-in asks for and the scopes the
 * client metadata advertises, and older published configs keep working.
 */
class ScopeConfigTest extends TestCase
{
    protected const AUTH = 'https://auth.example';

    protected const ENV_SCOPE = 'atproto repo:app.bsky.feed.post?action=create rpc:app.bsky.actor.getProfile?aud=*';

    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    protected function setUp(): void
    {
        // The config file reads the env while the app boots.
        $this->setEnv(self::ENV_SCOPE);

        parent::setUp();

        config()->set('atp-client.client.client_id', 'https://app.example/oauth-client-metadata.json');
        config()->set('atp-client.client.redirect_uris', ['https://app.example/oauth/callback']);

        $discovery = Mockery::mock(AuthorizationServerDiscovery::class);
        $discovery->shouldReceive('discover')->andReturn($this->server());
        $discovery->shouldReceive('forAuthorizationServer')->andReturn($this->server());
        $this->app->instance(AuthorizationServerDiscovery::class, $discovery);

        $keys = Mockery::mock(KeyStore::class);
        $keys->shouldReceive('store');
        $this->app->instance(KeyStore::class, $keys);

        Http::fake([self::AUTH.'/oauth/par' => Http::response(['request_uri' => 'urn:test', 'expires_in' => 60], 201)]);
    }

    protected function tearDown(): void
    {
        $this->setEnv(null);

        parent::tearDown();
    }

    public function test_the_env_scope_reaches_the_authorization_request(): void
    {
        $prepared = $this->app->make(OAuthEngine::class)
            ->prepareAuthorization('alice.example', pdsEndpoint: self::AUTH);

        $this->assertSame(self::ENV_SCOPE, $prepared['scopes']);

        $this->app->make(OAuthEngine::class)->authorizeAt(self::AUTH);

        Http::assertSent(fn (Request $r) => $r->url() === self::AUTH.'/oauth/par' && $r['scope'] === self::ENV_SCOPE);
    }

    public function test_the_env_scope_reaches_the_client_metadata(): void
    {
        $this->getJson('/oauth-client-metadata.json')->assertOk()->assertJsonPath('scope', self::ENV_SCOPE);

        $this->assertSame(self::ENV_SCOPE, $this->app->make(ClientMetadataManager::class)->toArray()['scope']);
    }

    public function test_the_env_scope_reaches_the_localhost_client_id(): void
    {
        config()->set('atp-client.client.client_id', null);
        config()->set('atp-client.client.url', 'https://app.test');

        parse_str((string) parse_url($this->app->make(ClientMetadataManager::class)->getClientId(), PHP_URL_QUERY), $query);

        $this->assertSame(self::ENV_SCOPE, $query['scope']);
    }

    public function test_a_published_scope_list_is_used_when_the_env_is_unset(): void
    {
        // An older published config: a fixed list, and oauth.scope at its default.
        config()->set('atp-client.client.scopes', ['atproto', 'repo:app.bsky.feed.like']);
        config()->set('atp-client.oauth.scope', 'atproto transition:generic');

        $this->assertSame(['atproto', 'repo:app.bsky.feed.like'], $this->app->make(ClientMetadataManager::class)->getScopes());
        $this->getJson('/oauth-client-metadata.json')->assertJsonPath('scope', 'atproto repo:app.bsky.feed.like');
    }

    public function test_an_older_published_config_follows_the_env(): void
    {
        // An older published config: a fixed list, and oauth.scope read from the env.
        config()->set('atp-client.client.scopes', ['atproto', 'transition:generic']);
        config()->set('atp-client.oauth.scope', self::ENV_SCOPE);

        $prepared = $this->app->make(OAuthEngine::class)
            ->prepareAuthorization('alice.example', pdsEndpoint: self::AUTH);

        $this->assertSame(self::ENV_SCOPE, $prepared['scopes']);
        $this->getJson('/oauth-client-metadata.json')->assertJsonPath('scope', self::ENV_SCOPE);
    }

    public function test_scopes_may_be_a_string_and_fall_back_to_the_default(): void
    {
        $metadata = $this->app->make(ClientMetadataManager::class);

        config()->set('atp-client.client.scopes', '  atproto   transition:chat.bsky ');
        $this->assertSame(['atproto', 'transition:chat.bsky'], $metadata->getScopes());

        config()->set('atp-client.client.scopes', ['']);
        $this->assertSame(ClientMetadataManager::DEFAULT_SCOPES, $metadata->getScopes());
    }

    protected function setEnv(?string $value): void
    {
        if ($value === null) {
            unset($_ENV['ATP_OAUTH_SCOPE'], $_SERVER['ATP_OAUTH_SCOPE']);
            putenv('ATP_OAUTH_SCOPE');

            return;
        }

        $_ENV['ATP_OAUTH_SCOPE'] = $_SERVER['ATP_OAUTH_SCOPE'] = $value;
        putenv('ATP_OAUTH_SCOPE='.$value);
    }

    protected function server(): AuthorizationServerMetadata
    {
        return new AuthorizationServerMetadata(
            issuer: self::AUTH,
            authorizationEndpoint: self::AUTH.'/oauth/authorize',
            tokenEndpoint: self::AUTH.'/oauth/token',
            parEndpoint: self::AUTH.'/oauth/par',
            pdsEndpoint: self::AUTH,
        );
    }
}
