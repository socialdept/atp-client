# Sessions & Keep-Alive

How AtpClient manages OAuth sessions, why refresh is fragile, and how to keep sessions alive reliably.

## Two tokens, two lifetimes

An authenticated session holds two tokens that behave very differently:

| Token | Lifetime | How it is refreshed |
|-------|----------|---------------------|
| **Access token** | Short — minutes. **Varies per PDS** (e.g. ~400–800s); read `expires_in`, never assume. | Automatically, on demand, whenever a call needs it (or reactively on a `401`). |
| **Refresh token** | Long, but **not advertised** and **single-use / rotating**. Dies from *inactivity*, not a fixed clock. Confidential clients can reach ~2 years *if kept refreshed*. | By exercising it — each refresh returns a new one and revokes the old. |

Two consequences drive everything below:

1. **You cannot trust a stored expiry.** The token endpoint never returns a refresh-token expiry, and `expires_in` is only as honest as the PDS. The only authoritative "this session is dead" signal is a real `invalid_grant` (or an account-status failure) when you actually try.
2. **Refresh is single-use.** Two concurrent refreshes of the same token → one wins, the other gets `invalid_grant`. If you lose the rotated token (crash between receiving and persisting), the session is bricked.

## Classifying failures

`OAuthErrorClassifier` turns a failed token response (or exception) into a `RefreshFailureReason`. Use `isTerminal()` / `isTransient()` — never string-match yourself.

| Terminal (reconnect required) | Transient (retry, never flag) |
|---|---|
| `InvalidGrant` — revoked / consumed / expired refresh token | `UseDpopNonce` — DPoP nonce challenge (auto-retried) |
| `InvalidClient` — client assertion rejected | `TemporarilyUnavailable`, `SlowDown` |
| `MissingRefreshToken` | `RateLimited` (HTTP 429) |
| `AccountInactive` — takendown / suspended / deactivated | `ServerError` (5xx / HTML proxy page) |
| `AccountNotFound` — "could not find user info" (deleted) | `Network` (connection failure) |
| | `Unknown` — unmapped body (transient by policy — never flag off something you couldn't parse) |

`RefreshFailureReason::legacyReason()` maps to the old event strings (`missing`/`invalid`/`auth_failed`/`transient`) so existing listeners keep working.

## Flagging a dead session (only on a real refusal)

Listen for `SessionRefreshFailed` and flag **only** on terminal reasons:

```php
use SocialDept\AtpClient\Events\SessionRefreshFailed;

Event::listen(SessionRefreshFailed::class, function (SessionRefreshFailed $event) {
    if ($event->failureReason?->isTerminal()) {
        // Prompt the user to reconnect. Do NOT flag on transient failures.
    }
});
```

## Liveness probe

`AtpClient::probe()` runs `com.atproto.server.getSession` (which refreshes the access token under the hood if needed) and returns a `SessionHealth`:

```php
$health = Atp::as($did)->probe();

$health->isHealthy();    // reachable, access works, account active
$health->needsRefresh(); // live account, stale access token — force a refresh
$health->isTerminal();   // dead grant or account gone/inactive — flag for reconnect
// otherwise !$health->reachable — transient (network/5xx/rate limit); leave alone
```

Note: `getSession` validates the *access* token and returns account `active`/`status`. The refresh token itself is only truly tested by attempting a refresh (which, being single-use, has a cost).

## Keeping sessions alive

Because refresh tokens die from **inactivity**, keep them warm by refreshing any session that has been idle longer than a conservative threshold — well inside the (undiscoverable, but generous) idle limit. Drive this off "when did we last successfully refresh", not a computed expiry.

- Refresh under the per-DID lock (below) so a background job never double-consumes a token.
- Refreshing does not extend a public client's hard session cap; for confidential clients it is what keeps the session alive.
- Flag for reconnect only on a terminal failure.

A minimal scheduled keep-alive:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    foreach (idleSessions(days: 7) as $session) {           // your storage: last_refreshed_at < now-7d
        try {
            Atp::as($session->did)->refresh($session->did);  // forces a rotation under the lock
            $session->touchLastRefreshedAt();
        } catch (\SocialDept\AtpClient\Exceptions\TransientAuthFailureException) {
            // transient — try again next run
        }
        // Terminal failures dispatch SessionRefreshFailed; flag in that listener.
    }
})->dailyAt('04:00')->withoutOverlapping()->onOneServer();
```

## Single-use safety (built in)

`SessionManager` serializes refreshes per DID with an atomic cache lock:

- Config: `session.refresh_serialize` (default `true`), `session.refresh_lock_wait`, `session.refresh_lock_ttl`. Needs a lock-capable cache store (redis / memcached / database / array / file).
- On lock contention it **adopts** a concurrently-rotated token instead of replaying the consumed one; if none is available yet it fails transient so the caller retries.

## DPoP keys must be durable

The refresh token is DPoP-bound to the session's key. If that key is lost (e.g. a non-shared or wiped key store), refresh cannot succeed. AtpClient will **not** silently mint a new key for an existing OAuth session — it throws `OAuthSessionInvalidException::missingDpopKey()` so the user reconnects cleanly. Persist the DPoP key store durably (and share it across hosts). Opt out only if you intentionally keep keys ephemeral: `session.allow_key_regeneration = true`.

## Events

| Event | When | Key fields |
|-------|------|-----------|
| `SessionAuthenticated` | Initial login / OAuth callback | `token` |
| `SessionRefreshing` | Before a refresh attempt | `session` |
| `SessionUpdated` | After a successful refresh | `session`, `token` (new) |
| `SessionRefreshFailed` | On a refresh failure | `session`, `exception`, `reason` (string), `failureReason` (typed) |
| `SessionInvalid` | Terminal death found *before* a refresh (no credentials, or missing DPoP key) — no `Session` exists yet | `did`, `reason` (typed), `exception` |

> Flag a dead session from **both** `SessionRefreshFailed` (terminal `failureReason`) and `SessionInvalid`. The former covers failures during a refresh; the latter covers sessions already unusable when first built (e.g. a scheduled job touching a grant whose key is gone), which never reach a refresh.
