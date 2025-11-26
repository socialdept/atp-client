<?php

namespace SocialDept\AtpClient;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SocialDept\AtpClient\Auth\ClientMetadataManager;
use SocialDept\AtpClient\Auth\DPoPKeyManager;
use SocialDept\AtpClient\Auth\DPoPNonceManager;
use SocialDept\AtpClient\Auth\OAuthEngine;
use SocialDept\AtpClient\Auth\ScopeChecker;
use SocialDept\AtpClient\Auth\ScopeGate;
use SocialDept\AtpClient\Auth\TokenRefresher;
use SocialDept\AtpClient\Enums\ScopeEnforcementLevel;
use SocialDept\AtpClient\Http\Middleware\RequiresScopeMiddleware;
use SocialDept\AtpClient\Console\GenerateOAuthKeyCommand;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Http\Controllers\ClientMetadataController;
use SocialDept\AtpClient\Http\Controllers\JwksController;
use SocialDept\AtpClient\Http\DPoPClient;
use SocialDept\AtpClient\Session\SessionManager;
use SocialDept\AtpClient\Storage\EncryptedFileKeyStore;

class AtpClientServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/client.php', 'atp-client');

        // Register contracts
        $this->app->singleton(CredentialProvider::class, function ($app) {
            $provider = config('client.credential_provider');

            return new $provider();
        });

        $this->app->singleton(KeyStore::class, function ($app) {
            return new EncryptedFileKeyStore(
                storage_path('app/atp-keys')
            );
        });

        // Register core services
        $this->app->singleton(ClientMetadataManager::class);
        $this->app->singleton(DPoPKeyManager::class);
        $this->app->singleton(DPoPNonceManager::class);
        $this->app->singleton(DPoPClient::class);
        $this->app->singleton(TokenRefresher::class);
        $this->app->singleton(SessionManager::class, function ($app) {
            return new SessionManager(
                credentials: $app->make(CredentialProvider::class),
                refresher: $app->make(TokenRefresher::class),
                dpopManager: $app->make(DPoPKeyManager::class),
                keyStore: $app->make(KeyStore::class),
                refreshThreshold: config('client.session.refresh_threshold', 300),
            );
        });
        $this->app->singleton(OAuthEngine::class);
        $this->app->singleton(ScopeChecker::class, function ($app) {
            return new ScopeChecker(
                config('atp-client.scope_enforcement', ScopeEnforcementLevel::Permissive)
            );
        });

        // Register ScopeGate for AtpScope facade
        $this->app->singleton('atp-scope', function ($app) {
            return new ScopeGate(
                $app->make(SessionManager::class),
                $app->make(ScopeChecker::class),
            );
        });

        // Register main client facade accessor
        $this->app->bind('atp-client', function ($app) {
            return new class($app)
            {
                protected $app;

                protected ?CredentialProvider $defaultProvider = null;

                public function __construct($app)
                {
                    $this->app = $app;
                }

                public function as(string $actor): AtpClient
                {
                    return new AtpClient(
                        $this->app->make(SessionManager::class),
                        $actor
                    );
                }

                public function login(string $actor, string $password): AtpClient
                {
                    $this->app->make(SessionManager::class)
                        ->fromAppPassword($actor, $password);

                    return $this->as($actor);
                }

                public function oauth(): OAuthEngine
                {
                    return $this->app->make(OAuthEngine::class);
                }

                public function setDefaultProvider(CredentialProvider $provider): void
                {
                    $this->defaultProvider = $provider;
                    $this->app->instance(CredentialProvider::class, $provider);
                }
            };
        });
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/client.php' => config_path('client.php'),
            ], 'atp-client-config');

            $this->commands([
                GenerateOAuthKeyCommand::class,
            ]);
        }

        $this->registerRoutes();
        $this->registerMiddleware();
    }

    /**
     * Register middleware aliases
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('atp.scope', RequiresScopeMiddleware::class);
    }

    /**
     * Register OAuth metadata routes
     */
    protected function registerRoutes(): void
    {
        if (config('client.oauth.disabled')) {
            return;
        }

        $prefix = config('client.oauth.prefix', '/atp/oauth/');

        Route::prefix($prefix)->group(function () {
            Route::get('client-metadata.json', ClientMetadataController::class)
                ->name('atp.oauth.client-metadata');

            Route::get('jwks.json', JwksController::class)
                ->name('atp.oauth.jwks');
        });

        // Register recommended client id convention (see: https://atproto.com/guides/oauth#clients)
        Route::get('oauth-client-metadata.json', ClientMetadataController::class)
            ->name('atp.oauth.json');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return ['atp-client', 'atp-scope'];
    }
}
