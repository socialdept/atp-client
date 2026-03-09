# Testing

AtpClient ships with `Atp::fake()` — a first-party testing fake that replaces all HTTP calls with in-memory stubs. No network requests, no tokens, no PDS needed.

The API is designed to feel like Laravel's `Http::fake()`:

```php
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpClient\Enums\Nsid\BskyActor;
use SocialDept\AtpClient\Testing\FakeResponse;

$fake = Atp::fake();
$fake->stub(BskyActor::GetProfile, FakeResponse::profile(['handle' => 'alice.bsky.social']));

$profile = Atp::public()->bsky->actor->getProfile('alice.bsky.social');

$fake->assertCalled(BskyActor::GetProfile);
```

## Quick Reference

| Class | Purpose |
|-------|---------|
| `Atp::fake()` | Enable fake mode, returns `FakeAtpManager` |
| `FakeResponse` | Build stubbed responses (data arrays + error responses) |
| `ResponseSequence` | Return different responses on successive calls |
| `RecordedRequest` | Inspect recorded requests in assertion callbacks |

## Setting Up Fakes

Call `Atp::fake()` at the start of your test. It swaps the facade root with a `FakeAtpManager` that intercepts all XRPC calls.

```php
use SocialDept\AtpClient\Facades\Atp;

// Basic — unstubbed endpoints return empty 200 responses
$fake = Atp::fake();

// With inline stubs (string keys)
$fake = Atp::fake([
    'app.bsky.actor.getProfile' => FakeResponse::profile(),
    'app.bsky.feed.getTimeline' => FakeResponse::timeline(5),
]);
```

### Adding Stubs

Use `stub()` to add or update stubs after creating the fake. This is the recommended approach because it accepts NSID enums for autocomplete:

```php
use SocialDept\AtpClient\Enums\Nsid\BskyActor;
use SocialDept\AtpClient\Enums\Nsid\BskyFeed;
use SocialDept\AtpClient\Enums\Nsid\AtprotoRepo;

$fake = Atp::fake();
$fake->stub(BskyActor::GetProfile, FakeResponse::profile(['handle' => 'alice.bsky.social']));
$fake->stub(BskyFeed::GetTimeline, FakeResponse::timeline(10));
$fake->stub(AtprotoRepo::CreateRecord, FakeResponse::createRecord());
```

Stubs accept several types:

| Type | Behavior |
|------|----------|
| `array` | Returned as a 200 JSON response |
| `Response` | Returned as-is (use for errors) |
| `Closure` | Called with `($endpoint, $params, $body, $method)` |
| `ResponseSequence` | Returns next response in queue |

## NSID Enums

The package includes 13 enum classes with 103 NSID constants. Use them everywhere — `stub()`, `sequence()`, and all assertion methods accept `string|BackedEnum`:

```php
use SocialDept\AtpClient\Enums\Nsid\BskyActor;
use SocialDept\AtpClient\Enums\Nsid\BskyFeed;
use SocialDept\AtpClient\Enums\Nsid\BskyGraph;
use SocialDept\AtpClient\Enums\Nsid\AtprotoRepo;
use SocialDept\AtpClient\Enums\Nsid\AtprotoServer;

$fake = Atp::fake();
$fake->stub(BskyActor::GetProfile, FakeResponse::profile());
$fake->stub(BskyFeed::GetTimeline, FakeResponse::timeline(5));
$fake->stub(BskyGraph::GetFollowers, FakeResponse::followers(3));
$fake->stub(AtprotoRepo::CreateRecord, FakeResponse::createRecord());

// Assertions use enums too
$fake->assertCalled(BskyActor::GetProfile);
$fake->assertNotCalled(AtprotoServer::RefreshSession);
```

### Available Enum Classes

| Enum | Namespace | Examples |
|------|-----------|----------|
| `BskyActor` | `app.bsky.actor.*` | `GetProfile`, `GetProfiles`, `GetSuggestions`, `SearchActors` |
| `BskyFeed` | `app.bsky.feed.*` | `GetTimeline`, `GetAuthorFeed`, `GetPostThread`, `GetPosts`, `SearchPosts` |
| `BskyGraph` | `app.bsky.graph.*` | `GetFollowers`, `GetFollows`, `GetKnownFollowers`, `GetList` |
| `BskyLabeler` | `app.bsky.labeler.*` | `GetServices` |
| `AtprotoRepo` | `com.atproto.repo.*` | `CreateRecord`, `DeleteRecord`, `PutRecord`, `GetRecord`, `UploadBlob` |
| `AtprotoServer` | `com.atproto.server.*` | `CreateSession`, `RefreshSession`, `GetSession` |
| `AtprotoSync` | `com.atproto.sync.*` | `GetBlob`, `GetRepo`, `ListRepos` |
| `AtprotoIdentity` | `com.atproto.identity.*` | `ResolveHandle`, `UpdateHandle` |
| `ChatConvo` | `chat.bsky.convo.*` | `GetConvo`, `SendMessage`, `ListConvos` |
| `ChatActor` | `chat.bsky.actor.*` | `GetActorMetadata`, `DeleteAccount` |
| `OzoneTeam` | `tools.ozone.team.*` | `GetMember`, `ListMembers`, `AddMember` |
| `OzoneModeration` | `tools.ozone.moderation.*` | `GetEvent`, `QueryStatuses` |
| `OzoneServer` | `tools.ozone.server.*` | `GetBlob`, `GetConfig` |

> **Note:** PHP does not allow enum instances as array keys in `[...]` literals. Use `$fake->stub(BskyActor::GetProfile, ...)` (recommended) or `BskyActor::GetProfile->value` as the key when passing stubs to `Atp::fake([...])`.

## FakeResponse

`FakeResponse` is your single import for building test responses. It provides named factories for common endpoints, composable builders for structural patterns, and error responses for failure scenarios.

```php
use SocialDept\AtpClient\Testing\FakeResponse;
```

### Named Factories

#### Actor Responses

```php
// Single profile (app.bsky.actor.getProfile)
FakeResponse::profile(['handle' => 'alice.bsky.social', 'displayName' => 'Alice']);

// Multiple profiles (app.bsky.actor.getProfiles)
FakeResponse::profiles(3);

// Search actors (app.bsky.actor.searchActors)
FakeResponse::searchActors(5, cursor: 'abc');

// Typeahead search (app.bsky.actor.searchActorsTypeahead)
FakeResponse::searchActorsTypeahead(3);

// Suggestions (app.bsky.actor.getSuggestions)
FakeResponse::getSuggestions(5, cursor: 'next');
```

#### Feed Responses

```php
// Timeline (app.bsky.feed.getTimeline)
FakeResponse::timeline(10, cursor: 'abc');

// Single post view
FakeResponse::post(['record' => ['text' => 'Hello!']]);

// Feed view post (post + reply/reason context)
FakeResponse::feedViewPost();

// Post thread (app.bsky.feed.getPostThread)
FakeResponse::getPostThread(['post' => ['record' => ['text' => 'Thread root']]]);

// Multiple posts (app.bsky.feed.getPosts)
FakeResponse::getPosts(5);
```

#### Repo Responses

```php
// Create record (com.atproto.repo.createRecord)
FakeResponse::createRecord(['did' => 'did:plc:custom']);

// Put record (com.atproto.repo.putRecord) — same shape as createRecord
FakeResponse::putRecord();

// Delete record (com.atproto.repo.deleteRecord)
FakeResponse::deleteRecord();

// Get record (com.atproto.repo.getRecord)
FakeResponse::getRecord(['value' => ['$type' => 'app.bsky.feed.post', 'text' => 'Hello']]);

// List records (com.atproto.repo.listRecords)
FakeResponse::listRecords(10, cursor: 'next');

// Upload blob (com.atproto.repo.uploadBlob)
FakeResponse::uploadBlob('image/png', 12345);
```

#### Session Responses

```php
// Create session (com.atproto.server.createSession)
FakeResponse::createSession(['handle' => 'bot.bsky.social']);

// Refresh session (com.atproto.server.refreshSession)
FakeResponse::refreshSession();

// Get session (com.atproto.server.getSession)
FakeResponse::getSession(['did' => 'did:plc:custom']);
```

#### Graph Responses

```php
// Followers (app.bsky.graph.getFollowers)
FakeResponse::followers(10, cursor: 'next');

// Follows (app.bsky.graph.getFollows)
FakeResponse::follows(5);
```

#### Notification Responses

```php
// Notifications (app.bsky.notification.listNotifications)
FakeResponse::notifications(20, cursor: 'next');
```

#### Generic Helpers

```php
// Empty success
FakeResponse::empty();

// Strong reference (com.atproto.repo.strongRef)
FakeResponse::strongRef(['uri' => 'at://did:plc:test/app.bsky.feed.post/abc']);
```

### Composable Shape Builders

Many AT Protocol endpoints share the same structural patterns. These builders cover any endpoint without needing a dedicated factory:

```php
// Feed list — {key: feedViewPost[], cursor?}
// Covers: getTimeline, getAuthorFeed, getFeed, getActorLikes
FakeResponse::feedList(5, cursor: 'abc', key: 'feed');

// Profile list — {key: profileView[], cursor?}
// Covers: getFollowers, getFollows, searchActors, getSuggestions
FakeResponse::profileList(10, cursor: 'next', key: 'actors');

// Post list — {key: postView[], cursor?}
// Covers: getPosts, getQuotes, searchPosts
FakeResponse::postList(3, key: 'quotes');

// Generic cursor list — {key: items[], cursor?}
// Covers any paginated endpoint
FakeResponse::cursorList(
    items: [['uri' => 'at://...', 'cid' => 'baf...']],
    cursor: 'next',
    key: 'records',
);
```

The `key` parameter controls the response array key, making it easy to match any endpoint's shape:

```php
// app.bsky.graph.getKnownFollowers — uses 'followers' key
$fake->stub(BskyGraph::GetKnownFollowers, FakeResponse::profileList(3, key: 'followers'));

// app.bsky.feed.getQuotes — uses 'posts' key
$fake->stub(BskyFeed::GetQuotes, FakeResponse::postList(5, key: 'posts'));

// app.bsky.feed.getAuthorFeed — same shape as timeline
$fake->stub(BskyFeed::GetAuthorFeed, FakeResponse::feedList(10, cursor: 'abc'));
```

### Error Responses

Error responses return `Response` objects that trigger `AtpResponseException` when the stub is resolved, mimicking real AT Protocol error behavior:

```php
use SocialDept\AtpClient\Exceptions\AtpResponseException;

// AT Protocol standard errors
FakeResponse::expiredToken();                    // 401 ExpiredToken
FakeResponse::invalidToken();                    // 401 InvalidToken
FakeResponse::accountTakedown();                 // 401 AccountTakedown
FakeResponse::accountDeactivated();              // 401 AccountDeactivated
FakeResponse::authFactorTokenRequired();         // 401 AuthFactorTokenRequired
FakeResponse::authRequired();                    // 401 AuthenticationRequired
FakeResponse::invalidSwap();                     // 400 InvalidSwap
FakeResponse::invalidRequest('Bad input');       // 400 InvalidRequest
FakeResponse::rateLimited(60);                   // 429 RateLimitExceeded (with Retry-After)
FakeResponse::blobTooLarge();                    // 400 BlobTooLarge
FakeResponse::internalError();                   // 500 InternalServerError
FakeResponse::upstreamFailure();                 // 502 UpstreamFailure
FakeResponse::methodNotImplemented();            // 501 MethodNotImplemented

// Custom errors
FakeResponse::error('CustomError', 'Something went wrong', 422);

// Catch errors in tests
$fake->stub(AtprotoRepo::CreateRecord, FakeResponse::invalidSwap());

try {
    Atp::as('did:plc:test')->atproto->repo->createRecord(/* ... */);
} catch (AtpResponseException $e) {
    expect($e->errorCode)->toBe('InvalidSwap');
    expect($e->httpStatus)->toBe(400);
}
```

### Building Custom Responses

For full control, use `ok()` and `make()`:

```php
// Success response from array
FakeResponse::ok(['custom' => 'data'], status: 200);

// Response with custom headers
FakeResponse::make(['data' => true], status: 201, headers: ['X-Custom' => 'value']);
```

## Response Sequences

Return different responses on successive calls to the same endpoint:

```php
$fake = Atp::fake();

$fake->sequence(BskyActor::GetProfile)
    ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'first.bsky.social'])))
    ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'second.bsky.social'])));

$first = Atp::public()->bsky->actor->getProfile('any');   // first.bsky.social
$second = Atp::public()->bsky->actor->getProfile('any');  // second.bsky.social
```

### Sequence with Errors

Mix errors and successes to test retry logic:

```php
$fake->sequence(BskyActor::GetProfile)
    ->push(FakeResponse::error('InvalidRequest', 'Bad input', 400))
    ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'recovered.bsky.social'])));
```

### Fallback When Exhausted

Set a fallback response for when the sequence runs out:

```php
$fake->sequence(BskyActor::GetProfile)
    ->push(FakeResponse::ok(FakeResponse::profile(['handle' => 'first.bsky.social'])))
    ->whenEmpty(FakeResponse::ok(FakeResponse::profile(['handle' => 'fallback.bsky.social'])));
```

Or silently return an empty 200:

```php
$fake->sequence(BskyActor::GetProfile)
    ->push(FakeResponse::ok(FakeResponse::profile()))
    ->dontFailWhenEmpty();
```

### Sequence Helpers

```php
$sequence = $fake->sequence(BskyActor::GetProfile);

// Push a success response
$sequence->pushOk(['handle' => 'test']);

// Push an error response
$sequence->pushError('InvalidToken', 'Token expired', 401);

// Check if there are queued responses
$sequence->hasMore(); // bool
```

## Assertions

All assertion methods accept both string endpoints and NSID enums.

### assertCalled

Assert an endpoint was called at least once:

```php
$fake->assertCalled(BskyActor::GetProfile);
```

### assertNotCalled

Assert an endpoint was never called:

```php
$fake->assertNotCalled(BskyFeed::GetTimeline);
```

### assertCalledTimes

Assert an endpoint was called exactly N times:

```php
$fake->assertCalledTimes(BskyActor::GetProfile, 3);
```

### assertCalledWith

Assert an endpoint was called matching a callback condition:

```php
$fake->assertCalledWith(BskyActor::GetProfile, function (RecordedRequest $request) {
    return $request->hasParam('actor', 'alice.bsky.social');
});
```

### assertCalledPublicly

Assert an endpoint was called in public mode (via `Atp::public()`):

```php
$fake->assertCalledPublicly(BskyActor::GetProfile);
```

### assertCalledAuthenticated

Assert an endpoint was called in authenticated mode (via `Atp::as()`):

```php
$fake->assertCalledAuthenticated(BskyFeed::GetTimeline);

// Optionally verify the specific DID
$fake->assertCalledAuthenticated(BskyFeed::GetTimeline, 'did:plc:test123');
```

### assertNothingCalled

Assert no endpoints were called at all:

```php
$fake->assertNothingCalled();
```

### Wildcard Assertions

Assert against endpoint patterns:

```php
$fake->assertCalled('app.bsky.actor.*');
$fake->assertNotCalled('com.atproto.repo.*');
```

## Inspecting Recorded Requests

Access recorded requests for advanced inspection:

```php
// Get all recorded requests
$all = $fake->allRecorded();

// Get requests for a specific endpoint
$profileCalls = $fake->getRecordedFor(BskyActor::GetProfile);

foreach ($profileCalls as $request) {
    $request->endpoint;     // 'app.bsky.actor.getProfile'
    $request->method;       // 'GET'
    $request->params;       // ['actor' => 'alice.bsky.social']
    $request->body;         // null (GET) or array (POST)
    $request->did;          // 'did:plc:test123' or null (public)
    $request->isPublicMode; // true/false
}
```

### RecordedRequest Helpers

```php
$request->is('app.bsky.actor.getProfile');       // Exact match
$request->matches('app.bsky.actor.*');            // Wildcard match
$request->hasParam('actor', 'alice.bsky.social'); // Check query param
$request->hasBody('text', 'Hello!');              // Check body field
```

## Wildcard Stubs

Stub entire namespaces at once:

```php
$fake = Atp::fake([
    'app.bsky.actor.*' => FakeResponse::profile(['handle' => 'default.bsky.social']),
]);

// All app.bsky.actor.* endpoints return this profile
Atp::public()->bsky->actor->getProfile('any');       // works
Atp::public()->bsky->actor->getProfiles(['any']);     // works
```

Exact stubs take priority over wildcards:

```php
$fake = Atp::fake([
    'app.bsky.actor.*' => FakeResponse::profile(['handle' => 'wildcard']),
]);
$fake->stub(BskyActor::GetProfile, FakeResponse::profile(['handle' => 'exact']));

Atp::public()->bsky->actor->getProfile('test'); // 'exact' — exact match wins
```

## Callable Stubs

Use closures for dynamic responses based on the request:

```php
$fake->stub(BskyActor::GetProfile, function (string $endpoint, ?array $params, ?array $body, string $method) {
    return FakeResponse::ok(FakeResponse::profile([
        'handle' => $params['actor'] ?? 'unknown',
    ]));
});

$profile = Atp::public()->bsky->actor->getProfile('dynamic.bsky.social');
// $profile->handle === 'dynamic.bsky.social'
```

Closures can return either a `Response` object or a plain array (automatically wrapped in a 200 response).

## Authenticated Mode

Test authenticated flows with `Atp::as()`:

```php
$fake = Atp::fake();
$fake->stub(BskyFeed::GetTimeline, FakeResponse::timeline(3));

// Authenticated client — no real credentials needed
$timeline = Atp::as('did:plc:test123')->bsky->feed->getTimeline();

$fake->assertCalledAuthenticated(BskyFeed::GetTimeline, 'did:plc:test123');
```

### Public vs Authenticated Assertions

Verify that your code uses the correct mode:

```php
$fake = Atp::fake();
$fake->stub(BskyActor::GetProfile, FakeResponse::profile());

// Public call
Atp::public()->bsky->actor->getProfile('alice.bsky.social');

// Authenticated call
Atp::as('did:plc:test123')->bsky->actor->getProfile('alice.bsky.social');

$fake->assertCalledPublicly(BskyActor::GetProfile);
$fake->assertCalledAuthenticated(BskyActor::GetProfile, 'did:plc:test123');
$fake->assertCalledTimes(BskyActor::GetProfile, 2);
```

## OAuth Faking

The fake manager includes a fake OAuth engine:

```php
$fake = Atp::fake();

$oauth = $fake->oauth();
$request = $oauth->authorize('test.bsky.social');

// Returns synthetic auth data
$request->state; // 'fake-state'
$request->url;   // Fake authorization URL

// Verify OAuth calls
$oauth->recordedCalls(); // Array of recorded authorize/callback calls
```

## Preventing Stray Requests

Enable strict mode to catch unstubbed endpoints:

```php
$fake = Atp::fake();
$fake->stub(BskyActor::GetProfile, FakeResponse::profile());
$fake->preventStrayRequests();

// This works — endpoint is stubbed
Atp::public()->bsky->actor->getProfile('test');

// This throws RuntimeException — endpoint is not stubbed
Atp::public()->bsky->feed->getTimeline(); // "Unexpected ATP request to [app.bsky.feed.getTimeline]"
```

## Full Test Example

A complete test demonstrating the typical workflow:

```php
use SocialDept\AtpClient\Enums\Nsid\AtprotoRepo;
use SocialDept\AtpClient\Enums\Nsid\BskyActor;
use SocialDept\AtpClient\Enums\Nsid\BskyFeed;
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpClient\Testing\FakeResponse;

it('publishes a post and verifies the timeline', function () {
    $fake = Atp::fake();
    $fake->stub(AtprotoRepo::CreateRecord, FakeResponse::createRecord([
        'uri' => 'at://did:plc:test/app.bsky.feed.post/abc123',
    ]));
    $fake->stub(BskyFeed::GetTimeline, FakeResponse::timeline(1));
    $fake->preventStrayRequests();

    // Your application code
    $client = Atp::as('did:plc:test');
    $result = $client->atproto->repo->createRecord(/* ... */);
    $timeline = $client->bsky->feed->getTimeline();

    // Assertions
    $fake->assertCalledAuthenticated(AtprotoRepo::CreateRecord, 'did:plc:test');
    $fake->assertCalled(BskyFeed::GetTimeline);
    $fake->assertNotCalled(BskyActor::GetProfile);

    $fake->assertCalledWith(AtprotoRepo::CreateRecord, function ($request) {
        return $request->did === 'did:plc:test';
    });
});
```
