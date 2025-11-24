<?php

namespace SocialDept\AtpClient;

use Illuminate\Support\ServiceProvider;
use SocialDept\AtpClient\Auth\ClientMetadataManager;
use SocialDept\AtpClient\Auth\DPoPKeyManager;
use SocialDept\AtpClient\Auth\OAuthEngine;
use SocialDept\AtpClient\Auth\TokenRefresher;
use SocialDept\AtpClient\Client\AtpClient;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Providers\ArrayCredentialProvider;
use SocialDept\AtpClient\Session\SessionManager;
use SocialDept\AtpClient\Storage\EncryptedFileKeyStore;

class AtpClientServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atp-client.php', 'atp-client');

        // Register contracts
        $this->app->singleton(CredentialProvider::class, function ($app) {
            $provider = config('atp-client.credential_provider');

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
        $this->app->singleton(TokenRefresher::class);
        $this->app->singleton(SessionManager::class, function ($app) {
            return new SessionManager(
                credentials: $app->make(CredentialProvider::class),
                refresher: $app->make(TokenRefresher::class),
                dpopManager: $app->make(DPoPKeyManager::class),
                keyStore: $app->make(KeyStore::class),
                http: $app->make('http'),
                refreshThreshold: config('atp-client.session.refresh_threshold', 300),
            );
        });
        $this->app->singleton(OAuthEngine::class);

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

                public function as(string $identifier): AtpClient
                {
                    return new AtpClient(
                        $this->app->make(SessionManager::class),
                        $this->app->make('http'),
                        $identifier
                    );
                }

                public function login(string $identifier, string $password): AtpClient
                {
                    $session = $this->app->make(SessionManager::class)
                        ->fromAppPassword($identifier, $password);

                    return $this->as($identifier);
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
                __DIR__.'/../config/atp-client.php' => config_path('atp-client.php'),
            ], 'atp-client-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return ['atp-client'];
    }
}
