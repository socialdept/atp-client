<?php

namespace SocialDept\AtpClient\Tests\Console;

use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\OAuthKey;
use SocialDept\AtpClient\Console\DevClientCommand;

class DevClientCommandTest extends TestCase
{
    protected string $envDirectory;

    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->envDirectory = sys_get_temp_dir().'/atp-client-dev-client-'.bin2hex(random_bytes(4));
        mkdir($this->envDirectory);
        file_put_contents($this->envDirectory.'/.env', "APP_NAME=Demo\nATP_CLIENT_ID=\"http://localhost\"\n");

        $this->app->useEnvironmentPath($this->envDirectory);

        config()->set('app.url', 'https://demo.test');
        config()->set('atp-client.client.name', 'Demo');
        config()->set('atp-client.client.scopes', ['atproto', 'transition:generic']);
        config()->set('atp-client.oauth.private_key', null);
    }

    protected function tearDown(): void
    {
        @unlink($this->envDirectory.'/.env');
        @rmdir($this->envDirectory);

        parent::tearDown();
    }

    public function test_writes_a_confidential_client_to_env(): void
    {
        $this->artisan('atp-client:dev-client')->assertSuccessful();

        $env = $this->env();
        $blob = $this->decode($env['ATP_CLIENT_ID']);
        $jwk = OAuthKey::load($env['ATP_OAUTH_PRIVATE_KEY'])->toJWK()->toArray();

        $this->assertStringStartsWith('https://proto.broker/v1/', $env['ATP_CLIENT_ID']);
        $this->assertSame($env['ATP_CLIENT_ID'].'/callback', $env['ATP_CLIENT_REDIRECT_URI']);
        $this->assertSame('atproto transition:generic', $env['ATP_OAUTH_SCOPE']);
        $this->assertSame([
            'c' => 'https://demo.test/oauth/callback',
            's' => 'atproto transition:generic',
            'n' => 'Demo',
            'k' => ['x' => $jwk['x'], 'y' => $jwk['y']],
        ], $blob);
        $this->assertSame(DevClientCommand::thumbprint($jwk['x'], $jwk['y']), $env['ATP_OAUTH_KID']);
        $this->assertSame('Demo', $env['APP_NAME'], 'unrelated variables are kept');
        $this->assertSame(1, substr_count(file_get_contents($this->envDirectory.'/.env'), 'ATP_CLIENT_ID='), 'existing variables are replaced, not duplicated');
    }

    public function test_reuses_the_existing_key_so_the_client_id_is_stable(): void
    {
        $key = OAuthKey::create();
        config()->set('atp-client.oauth.private_key', $key->privateB64());

        $this->artisan('atp-client:dev-client')->assertSuccessful();
        $first = $this->env()['ATP_CLIENT_ID'];

        $this->artisan('atp-client:dev-client')->assertSuccessful();

        $this->assertSame($first, $this->env()['ATP_CLIENT_ID']);
        $this->assertSame($key->privateB64(), $this->env()['ATP_OAUTH_PRIVATE_KEY']);
    }

    public function test_fresh_generates_a_new_key(): void
    {
        $key = OAuthKey::create();
        config()->set('atp-client.oauth.private_key', $key->privateB64());

        $this->artisan('atp-client:dev-client --fresh')->assertSuccessful();

        $this->assertNotSame($key->privateB64(), $this->env()['ATP_OAUTH_PRIVATE_KEY']);
    }

    public function test_public_client_has_no_key_and_clears_a_stale_one(): void
    {
        file_put_contents($this->envDirectory.'/.env', "ATP_OAUTH_PRIVATE_KEY=\"stale\"\nATP_OAUTH_KID=\"old\"\nAPP_NAME=Demo\n");

        $this->artisan('atp-client:dev-client --public')->assertSuccessful();

        $env = $this->env();

        $this->assertArrayNotHasKey('k', $this->decode($env['ATP_CLIENT_ID']));
        $this->assertArrayNotHasKey('ATP_OAUTH_PRIVATE_KEY', $env);
        $this->assertArrayNotHasKey('ATP_OAUTH_KID', $env);
        $this->assertSame('Demo', $env['APP_NAME']);
    }

    public function test_honors_callback_broker_and_name_options(): void
    {
        $this->artisan('atp-client:dev-client', [
            '--callback' => 'https://other.test/auth/done',
            '--broker' => 'https://broker.example.com/',
            '--name' => 'Other',
            '--public' => true,
        ])->assertSuccessful();

        $env = $this->env();
        $blob = $this->decode($env['ATP_CLIENT_ID']);

        $this->assertStringStartsWith('https://broker.example.com/v1/', $env['ATP_CLIENT_ID']);
        $this->assertSame('https://other.test/auth/done', $blob['c']);
        $this->assertSame('Other', $blob['n']);
    }

    public function test_dry_run_leaves_env_untouched(): void
    {
        $before = file_get_contents($this->envDirectory.'/.env');

        $this->artisan('atp-client:dev-client --dry-run')
            ->expectsOutputToContain('ATP_CLIENT_ID="https://proto.broker/v1/')
            ->assertSuccessful();

        $this->assertSame($before, file_get_contents($this->envDirectory.'/.env'));
    }

    public function test_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('atp-client:dev-client')->assertFailed();
    }

    public function test_requires_the_atproto_scope(): void
    {
        config()->set('atp-client.client.scopes', ['transition:generic']);

        $this->artisan('atp-client:dev-client')->assertFailed();
    }

    public function test_thumbprint_matches_the_rfc_7638_construction(): void
    {
        $x = 'SGdfHWRY7zeKnDtlKMO0Efq2hHCJQ9u8jOT_Rg2wBzU';
        $y = 'TbIDZ0TnY2thZi24sStXK5-vl4HfK9UQhYkWttGO70g';

        // Same key the broker's own client script reported this kid for.
        $this->assertSame('enyqoFQC6gwWMZmSsZq1C8S_mz-0-RaRBcF3M-RI0hE', DevClientCommand::thumbprint($x, $y));
    }

    /**
     * @return array<string, string>
     */
    protected function env(): array
    {
        $values = [];

        foreach (file($this->envDirectory.'/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            [$name, $value] = explode('=', $line, 2);
            $values[$name] = trim($value, '"');
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(string $clientId): array
    {
        $blob = substr($clientId, strrpos($clientId, '/v1/') + 4);

        return json_decode(base64_decode(strtr($blob, '-_', '+/')), true);
    }
}
