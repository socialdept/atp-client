# Introduction

**atp-client** is a Laravel package for Bluesky and the AT Protocol. It gives you a fluent, typed API for signing in, posting, profiles, follows, likes and feeds, with OAuth 2.0 (PKCE, PAR and DPoP) or app passwords. Think of it as Laravel's HTTP client, for the open social web.

- **Laravel-style code**: patterns you already know.
- **OAuth 2.0**: full PKCE, PAR and DPoP.
- **App passwords**: simple sign in for scripts and bots.
- **Automatic token refresh**: sessions stay alive without manual work.
- **Typed API**: method chaining with IDE autocompletion.
- **Rich text builder**: a fluent API for mentions, links and hashtags.
- **Bluesky coverage**: posts, profiles, follows, likes and feeds.
- **AT Protocol operations**: low-level repository access when you need it.
- **Between services**: verify and mint service auth tokens, and forward XRPC calls to a user's PDS.
- **A first-party fake**: `Atp::fake()` for tests without the network.

## A first look

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

Read their timeline later with the session you stored:

```php
$timeline = Atp::as($did)->bsky->feed->getTimeline(limit: 50);
```

## Documentation

Start with [Installation](getting-started/installation.md), then [sign in](auth/signing-in.md) and set up [credential storage](auth/credentials.md) so sessions survive between requests. The docs are grouped by area, and each group has an index of its own:

- **[Getting started](getting-started/README.md)**: [Installation](getting-started/installation.md)
- **[Authentication](auth/README.md)**: [Signing in](auth/signing-in.md), [Credential storage](auth/credentials.md), [Sessions & Keep-Alive](auth/sessions.md), [Events](auth/events.md), [OAuth Scopes](auth/scopes.md)
- **[Bluesky](bluesky/README.md)**: [Posts](bluesky/posts.md), [Profiles](bluesky/profiles.md), [Social graph](bluesky/graph.md), [Feeds](bluesky/feeds.md)
- **[Repositories](repo/README.md)**: [Batch writes](repo/batch-writes.md), [Publishing lexicons](repo/lexicons.md)
- **[Between services](services/README.md)**: [Service Auth](services/service-auth.md), [Forwarding XRPC Calls](services/forwarding.md)
- **[Client Extensions](extensions.md)**: Adding your own domain and request clients.
- **[Testing](testing.md)**: Faking the client in your tests.
