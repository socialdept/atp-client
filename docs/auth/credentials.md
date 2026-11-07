# Credential storage

Part of the [atp-client docs](../introduction.md#documentation). This page covers persisting OAuth tokens between requests.

The package uses a `CredentialProvider` interface for token storage. The default `ArrayCredentialProvider` stores credentials in memory (lost on request end). For production applications, you need to implement persistent storage.

## Why You Need a Credential Provider

AT Protocol OAuth uses **single-use refresh tokens**. When a token is refreshed:
1. The old refresh token is immediately invalidated
2. A new refresh token is issued
3. You must store the new token before using it again

If you lose the refresh token, the user must re-authenticate. The `CredentialProvider` ensures tokens are safely persisted.

## How Handle Resolution Works

When you call `Atp::as('user.bsky.social')` or `Atp::login('user.bsky.social', $password)`, the package automatically resolves the handle to a DID (Decentralized Identifier). The DID is then used as the storage key for credentials. This ensures consistency even if a user changes their handle.

If resolution fails (invalid handle, network error, etc.), a `HandleResolutionException` is thrown.

## The CredentialProvider Interface

```php
interface CredentialProvider
{
    // Get stored credentials by DID
    public function getCredentials(string $did): ?Credentials;

    // Store credentials after initial OAuth or app password login
    public function storeCredentials(string $did, AccessToken $token): void;

    // Update credentials after token refresh (CRITICAL: refresh tokens are single-use!)
    public function updateCredentials(string $did, AccessToken $token): void;

    // Remove credentials (logout)
    public function removeCredentials(string $did): void;
}
```

## Built-in Credential Providers

The package includes several credential providers for different use cases:

| Provider | Persistence | Setup | Best For |
|----------|-------------|-------|----------|
| `ArrayCredentialProvider` | None (memory) | None | Testing, single requests |
| `CacheCredentialProvider` | Cache driver | None | Quick prototyping, APIs |
| `SessionCredentialProvider` | Session lifetime | None | Web apps with user sessions |
| `FileCredentialProvider` | Permanent (disk) | None | CLI tools, bots |

**CacheCredentialProvider** - Uses your app's default Laravel cache store, with no expiry:
```php
// config/atp-client.php
'credential_provider' => \SocialDept\AtpClient\Providers\CacheCredentialProvider::class,
```

**SessionCredentialProvider** - Credentials cleared when session expires or user logs out:
```php
// config/atp-client.php
'credential_provider' => \SocialDept\AtpClient\Providers\SessionCredentialProvider::class,
```

**FileCredentialProvider** - Stores credentials in `storage/app/atp-credentials/`:
```php
// config/atp-client.php
'credential_provider' => \SocialDept\AtpClient\Providers\FileCredentialProvider::class,
```

For production applications with multiple users, implement a database-backed provider as shown below.

## Database Migration

Create a migration for storing credentials:

```bash
php artisan make:migration create_atp_credentials_table
```

```php
Schema::create('atp_credentials', function (Blueprint $table) {
    $table->id();
    $table->string('did')->unique();         // Decentralized identifier (storage key)
    $table->string('handle')->nullable();    // User's handle (e.g., user.bsky.social)
    $table->string('issuer')->nullable();    // Authorization server (OAuth) or PDS (app password)
    $table->text('access_token');            // JWT access token
    $table->text('refresh_token');           // Single-use refresh token
    $table->timestamp('expires_at');         // Token expiration time
    $table->json('scope')->nullable();       // Granted OAuth scopes
    $table->string('auth_type')->default('oauth'); // 'oauth' or 'legacy'
    $table->timestamps();
});
```

## Implementing a Database Provider

```php
<?php

namespace App\Providers;

use App\Models\AtpCredential;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\Credentials;
use SocialDept\AtpClient\Enums\AuthType;

class DatabaseCredentialProvider implements CredentialProvider
{
    public function getCredentials(string $did): ?Credentials
    {
        $record = AtpCredential::where('did', $did)->first();

        if (! $record) {
            return null;
        }

        return new Credentials(
            did: $record->did,
            accessToken: $record->access_token,
            refreshToken: $record->refresh_token,
            expiresAt: $record->expires_at,
            handle: $record->handle,
            issuer: $record->issuer,
            scope: $record->scope ?? [],
            authType: AuthType::from($record->auth_type),
        );
    }

    public function storeCredentials(string $did, AccessToken $token): void
    {
        AtpCredential::updateOrCreate(
            ['did' => $did],
            [
                'handle' => $token->handle,
                'issuer' => $token->issuer,
                'access_token' => $token->accessJwt,
                'refresh_token' => $token->refreshJwt,
                'expires_at' => $token->expiresAt,
                'scope' => $token->scope,
                'auth_type' => $token->authType->value,
            ]
        );
    }

    public function updateCredentials(string $did, AccessToken $token): void
    {
        AtpCredential::where('did', $did)->update([
            'access_token' => $token->accessJwt,
            'refresh_token' => $token->refreshJwt,
            'expires_at' => $token->expiresAt,
            'handle' => $token->handle,
            'issuer' => $token->issuer,
            'scope' => $token->scope,
            'auth_type' => $token->authType->value,
        ]);
    }

    public function removeCredentials(string $did): void
    {
        AtpCredential::where('did', $did)->delete();
    }
}
```

## The AtpCredential Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtpCredential extends Model
{
    protected $fillable = [
        'did',
        'handle',
        'issuer',
        'access_token',
        'refresh_token',
        'expires_at',
        'scope',
        'auth_type',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'scope' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];
}
```

## Register Your Provider

Update your config file:

```php
// config/atp-client.php

'credential_provider' => App\Providers\DatabaseCredentialProvider::class,
```

Or bind it in a service provider:

```php
// app/Providers/AppServiceProvider.php

use SocialDept\AtpClient\Contracts\CredentialProvider;
use App\Providers\DatabaseCredentialProvider;

public function register(): void
{
    $this->app->singleton(CredentialProvider::class, DatabaseCredentialProvider::class);
}
```

## Linking to Your User Model

If you want to associate ATP credentials with your application's users:

```php
// Migration
Schema::table('atp_credentials', function (Blueprint $table) {
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
});

// AtpCredential model
public function user()
{
    return $this->belongsTo(User::class);
}

// User model
public function atpCredential()
{
    return $this->hasOne(AtpCredential::class);
}
```

Then update your provider to work with the authenticated user:

```php
public function storeCredentials(string $did, AccessToken $token): void
{
    AtpCredential::updateOrCreate(
        ['did' => $did],
        [
            'user_id' => auth()->id(),  // Link to current user
            'handle' => $token->handle,
            'issuer' => $token->issuer,
            'access_token' => $token->accessJwt,
            'refresh_token' => $token->refreshJwt,
            'expires_at' => $token->expiresAt,
        ]
    );
}
```

## Understanding the Credential Fields

| Field | Description |
|-------|-------------|
| `did` | Decentralized Identifier - the stable, permanent user ID (e.g., `did:plc:abc123...`) |
| `handle` | User's handle (e.g., `user.bsky.social`) - can change |
| `issuer` | The authorization server that issued the tokens for OAuth, or the PDS endpoint for app passwords |
| `accessToken` | JWT for API authentication (short-lived) |
| `refreshToken` | Token to get new access tokens (single-use!) |
| `expiresAt` | When the access token expires |
| `scope` | Array of granted scopes (e.g., `['atproto', 'transition:generic']`) |
| `authType` | Authentication method: `AuthType::OAuth` or `AuthType::Legacy` |

## Handling Token Refresh Events

When tokens are automatically refreshed, you can listen for events:

```php
use SocialDept\AtpClient\Events\SessionUpdated;

// In EventServiceProvider or via Event::listen()
Event::listen(SessionUpdated::class, function (SessionUpdated $event) {
    // The CredentialProvider.updateCredentials() is already called,
    // but you can do additional logging or notifications here
    Log::info("Token refreshed for: {$event->session->did()}");

    // Check if this is a legacy (app password) session
    if ($event->session->isLegacy()) {
        // Handle legacy sessions differently if needed
    }
});
```
