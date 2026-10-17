# Forwarding XRPC Calls

`forward()` sends an XRPC call to the user's PDS exactly as it arrived, authenticated with the session your application holds, and hands back the PDS's response untouched.

It is the plumbing for a same-origin passthrough. A browser that holds only your application's session cookie sends ordinary XRPC requests to your server, and your server forwards them with its DPoP-bound token. The browser never sees an access or refresh token, and headers such as `atproto-proxy` survive, so service proxying works the same as it would from a client holding the token itself.

## Quick start

```php
use Illuminate\Http\Request;
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpClient\Http\ForwardedResponse;

Route::match(['get', 'post'], '/xrpc/{nsid}', function (Request $request, string $nsid) {
    $response = Atp::as($request->user()->did)->client->forward(
        method: $request->method(),
        endpoint: $nsid,
        query: $request->server->get('QUERY_STRING'),
        body: $request->getContent(asResource: true),
        headers: $request->headers->all(),
    );

    return ForwardedResponse::toSymfony($response);
})->middleware(['web', 'auth']);
```

Put the route behind your session and CSRF protection, and decide which methods it may forward. The PDS enforces the session's OAuth scopes regardless, but an allowlist of NSIDs and a denylist of account management methods are your application's job.

## What gets forwarded

| | |
|---|---|
| Methods | `GET` and `POST`, the only two XRPC uses. Anything else throws `InvalidArgumentException` |
| Query string | Passed through verbatim, so repeated parameters such as `uris=a&uris=b` keep XRPC's encoding. Pass the raw `QUERY_STRING`: Symfony's `getQueryString()` keeps only the last value of a repeated parameter |
| Request headers | Only `accept`, `accept-language`, `atproto-accept-labelers`, `atproto-proxy` and `content-type`. Cookies, the caller's own `Authorization` and everything else are dropped |
| Authentication | The stored session's token, with a DPoP proof for OAuth sessions or a Bearer token for app password sessions |

The allowlist is `HasHttp::FORWARDED_REQUEST_HEADERS`.

## Request bodies

POST bodies are copied into a `php://temp` stream before sending. A DPoP nonce challenge or a token refresh replays the request, and a one-shot stream such as `php://input` cannot be read twice. Bodies up to 2 MB stay in memory and larger ones spill to a temporary file, so a large blob upload does not have to fit in PHP's memory limit.

## Responses

`forward()` returns the PDS's response as an `Illuminate\Http\Client\Response`, with the body streamed:

- **Errors are returned, not thrown.** Unlike `get()` and `post()`, a 4xx or 5xx from the PDS comes back as a response so it can be relayed to the caller as-is.
- **A stale access token is refreshed once** and the call replayed, the same as for every other authenticated call.
- **Nothing is validated against a lexicon.** The response is whatever the PDS, or the service it proxied to, returned.

`ForwardedResponse::toSymfony()` turns it into a streamed response for your route, copying the status and only the headers that describe the content or its rate limits:

`atproto-content-labelers`, `atproto-repo-rev`, `cache-control`, `content-language`, `content-type`, `etag`, `last-modified`, `ratelimit-limit`, `ratelimit-policy`, `ratelimit-remaining`, `ratelimit-reset` and `retry-after`.

Headers about your application's own authentication with the PDS, such as `WWW-Authenticate` and `DPoP-Nonce`, are not relayed. The list is `ForwardedResponse::RELAYED_HEADERS`.

## Testing

`Atp::fake()` intercepts forwarded calls. They are recorded with the method `FORWARD_GET` or `FORWARD_POST`, the query string parsed into params, and the filtered headers under `_headers`. Like the real `forward()`, an error stub is returned rather than thrown:

```php
$fake = Atp::fake();
$fake->stub('app.bsky.feed.getPosts', FakeResponse::error('InvalidRequest', 'nope', 400));

$response = Atp::as('did:plc:alice')->client->forward('GET', 'app.bsky.feed.getPosts', 'limit=5');

$response->status(); // 400

$fake->assertCalledWith('app.bsky.feed.getPosts', fn ($request) => $request->method === 'FORWARD_GET'
    && $request->hasParam('limit', '5'));
```

## See also

- [Service Auth](service-auth.md) for calling a service directly with a token from the PDS instead of forwarding
- [Sessions](sessions.md) for how the stored session is kept valid
