[![AtpClient Header](./header.png)](https://github.com/socialdept/atp-client)

<h3 align="center">
    Type-safe AT Protocol HTTP client with OAuth 2.0 support for Laravel.
</h3>

<p align="center">
    <br>
    <a href="https://packagist.org/packages/socialdept/atp-client" title="Latest Version on Packagist"><img src="https://img.shields.io/packagist/v/socialdept/atp-client.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/socialdept/atp-client" title="Total Downloads"><img src="https://img.shields.io/packagist/dt/socialdept/atp-client.svg?style=flat-square"></a>
    <a href="https://github.com/socialdept/atp-client/actions/workflows/tests.yml" title="GitHub Tests Action Status"><img src="https://img.shields.io/github/actions/workflow/status/socialdept/atp-client/tests.yml?branch=main&label=tests&style=flat-square"></a>
    <a href="license.md" title="Software License"><img src="https://img.shields.io/github/license/socialdept/atp-client?style=flat-square"></a>
</p>

---

This Laravel package talks to Bluesky and the AT Protocol. It signs users in with OAuth 2.0 (PKCE, PAR and DPoP) or app passwords, keeps their sessions refreshed, and gives you a typed API for posts, profiles, the social graph, feeds and raw repository writes. It also verifies service auth tokens and forwards XRPC calls to a user's PDS. To learn all about it, head over to [the documentation](https://socialde.pt/docs/atp-client).

Here are a few short examples of what you can do.

Sign in with an app password and post:

```php
use SocialDept\AtpClient\Facades\Atp;

$client = Atp::login('yourhandle.bsky.social', 'your-app-password');

$client->bsky->post->create('Hello from Laravel!');
```

Send a user through OAuth:

```php
$auth = Atp::oauth()->authorize('user.bsky.social');

return redirect($auth->url);
```

Act as a user whose session you stored:

```php
$timeline = Atp::as($did)->bsky->feed->getTimeline(limit: 50);
```

Accept calls from other AT Protocol services:

```php
Route::get('/xrpc/com.example.doThing', DoThingController::class)
    ->middleware('atp.service-auth:com.example.doThing');
```

Fake the client in tests:

```php
Atp::fake();
```

## Documentation

You'll find the documentation on [https://socialde.pt/docs/atp-client](https://socialde.pt/docs/atp-client).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving it? Feel free to [create an issue on GitHub](https://github.com/socialdept/atp-client/issues).

## Testing

```bash
vendor/bin/phpunit
```

## Changelog

Please see [the changelog](changelog.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [the license file](license.md) for more information.
