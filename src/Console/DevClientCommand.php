<?php

namespace SocialDept\AtpClient\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use SocialDept\AtpClient\Auth\ClientMetadataManager;
use SocialDept\AtpClient\Auth\OAuthKey;

/**
 * Build a local development OAuth client on a stateless client metadata
 * broker such as proto.broker.
 *
 * The client id is computed here, not registered anywhere: the broker
 * decodes the callback, scopes and public key straight out of the URL. The
 * private key stays in .env and never leaves this machine.
 */
class DevClientCommand extends Command
{
    protected $signature = 'atp-client:dev-client
                            {--callback= : Your app\'s OAuth callback URL (defaults to the oauth.callback route)}
                            {--broker=https://proto.broker : The client metadata broker}
                            {--name= : Client name shown on the consent screen (defaults to the client name)}
                            {--public : Create a public client with no signing key}
                            {--fresh : Generate a new signing key instead of reusing the one in .env}
                            {--dry-run : Print the values without writing .env}
                            {--force : Run outside the local environment}';

    protected $description = 'Create a local development OAuth client on a client metadata broker';

    protected const LOCAL_SUFFIXES = ['.test', '.localhost', '.local'];

    protected const LOCAL_HOSTS = ['localhost', '127.0.0.1', '[::1]'];

    public function handle(): int
    {
        if ($this->laravel->isProduction() && ! $this->option('force')) {
            $this->components->error('Refusing to create a development client in production. Pass --force to override.');

            return self::FAILURE;
        }

        $callback = $this->callbackUrl();
        $scope = implode(' ', $this->laravel->make(ClientMetadataManager::class)->getScopes());

        if (! in_array('atproto', explode(' ', $scope), true)) {
            $this->components->error('The configured scopes must include "atproto".');

            return self::FAILURE;
        }

        if (! $this->isLocalHost(parse_url($callback, PHP_URL_HOST) ?: '')) {
            $this->components->warn("{$callback} is not a local development host. The public proto.broker will refuse to redirect to it.");
        }

        $blob = ['c' => $callback, 's' => $scope];

        if ($name = $this->option('name') ?? config('atp-client.client.name')) {
            $blob['n'] = (string) $name;
        }

        // A public client must not keep a signing key from an earlier run, or
        // the client would still try to authenticate as confidential.
        $env = ['ATP_OAUTH_PRIVATE_KEY' => null, 'ATP_OAUTH_KID' => null];

        if (! $this->option('public')) {
            $key = $this->signingKey();
            $jwk = $key->toJWK()->toArray();

            $blob['k'] = ['x' => $jwk['x'], 'y' => $jwk['y']];

            $env['ATP_OAUTH_PRIVATE_KEY'] = $key->privateB64();
            $env['ATP_OAUTH_KID'] = static::thumbprint($jwk['x'], $jwk['y']);
        }

        $clientId = rtrim((string) $this->option('broker'), '/').'/v1/'.static::encodeBlob($blob);

        $env = [
            'ATP_CLIENT_ID' => $clientId,
            'ATP_CLIENT_REDIRECT_URI' => $clientId.'/callback',
            'ATP_OAUTH_SCOPE' => $scope,
            ...$env,
        ];

        $this->components->twoColumnDetail('Callback', $callback);
        $this->components->twoColumnDetail('Scope', $scope);
        $this->components->twoColumnDetail('Client type', $this->option('public') ? 'public' : 'confidential (private_key_jwt)');
        $this->components->twoColumnDetail('Client ID', $clientId);
        $this->newLine();

        if ($this->option('dry-run')) {
            foreach ($env as $variable => $value) {
                $this->line($value === null ? "# remove {$variable}" : $variable.'="'.$value.'"');
            }

            return self::SUCCESS;
        }

        $this->writeEnv($env);
        $this->components->info('Updated '.basename($this->laravel->environmentFilePath()).'. Run php artisan config:clear if config is cached.');

        return self::SUCCESS;
    }

    /**
     * Encode a client blob as unpadded base64url JSON, the broker's v1 format.
     *
     * @param  array<string, mixed>  $blob
     */
    public static function encodeBlob(array $blob): string
    {
        return rtrim(strtr(base64_encode(json_encode($blob, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    /**
     * RFC 7638 JWK thumbprint, the kid the broker publishes when none is set.
     */
    public static function thumbprint(string $x, string $y): string
    {
        $canonical = '{"crv":"P-256","kty":"EC","x":"'.$x.'","y":"'.$y.'"}';

        return rtrim(strtr(base64_encode(hash('sha256', $canonical, true)), '+/', '-_'), '=');
    }

    protected function callbackUrl(): string
    {
        if ($callback = $this->option('callback')) {
            return $callback;
        }

        if (Route::has('oauth.callback')) {
            return route('oauth.callback');
        }

        return rtrim((string) config('app.url'), '/').'/oauth/callback';
    }

    /**
     * Reuse the key already in .env so re-running keeps the same client id,
     * unless --fresh asks for a new one.
     */
    protected function signingKey(): OAuthKey
    {
        $existing = config('atp-client.oauth.private_key');

        if ($existing && ! $this->option('fresh')) {
            return OAuthKey::load($existing);
        }

        return OAuthKey::create();
    }

    protected function isLocalHost(string $host): bool
    {
        $host = strtolower($host);

        if (in_array($host, static::LOCAL_HOSTS, true)) {
            return true;
        }

        foreach (static::LOCAL_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix) && strlen($host) > strlen($suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set each variable in .env, replacing an existing line or appending.
     * A null value removes the variable.
     *
     * @param  array<string, string|null>  $values
     */
    protected function writeEnv(array $values): void
    {
        $path = $this->laravel->environmentFilePath();
        $contents = file_exists($path) ? file_get_contents($path) : '';

        foreach ($values as $variable => $value) {
            $pattern = '/^'.preg_quote($variable, '/').'=.*$/m';

            if ($value === null) {
                $contents = preg_replace('/^'.preg_quote($variable, '/').'=.*(\R|$)/m', '', $contents);

                continue;
            }

            $line = $variable.'="'.$value.'"';

            $contents = preg_match($pattern, $contents)
                ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $contents)
                : rtrim($contents, "\n").($contents === '' ? '' : "\n").$line."\n";
        }

        file_put_contents($path, $contents);
    }
}
