# Changelog

All notable changes to `AtpClient` will be documented in this file.

## Version 0.3.1

### Added
- `RefreshFailureReason` enum + `OAuthErrorClassifier` — structured, parse-the-`error` classification of refresh failures into terminal (`invalid_grant`, `invalid_client`, missing refresh token, account inactive/not-found) vs transient (`use_dpop_nonce`, `temporarily_unavailable`, rate-limit, 5xx, network, unknown), replacing message string-sniffing. Both real terminal shapes are covered: `invalid_grant` and the account-gone `{"error":"InvalidRequest","message":"Could not find user info..."}`.
- `SessionHealth` DTO + `AtpClient::probe()` — a cheap authenticated liveness probe over `com.atproto.server.getSession` that reports healthy / stale-access / terminal (dead grant or takendown/suspended/deactivated) / transient. Ideal for inactivity keep-alive that flags a session only on a real refusal.
- `SessionManager::refresh(string $actor)` — force a token refresh regardless of the access-token window (inactivity keep-alive, reactive-401).
- `SessionRefreshFailed::$failureReason` (typed, nullable) alongside the existing `reason` string; `config('atp-client.session.allow_key_regeneration')` (default `false`).
- `docs/sessions.md` documenting the session lifecycle and a copy-paste keep-alive command.

### Fixed
- Do not silently mint a new DPoP key for an EXISTING OAuth session when the key is missing — the refresh token is bound to the old key, so a new one guarantees `invalid_grant`. Throw `OAuthSessionInvalidException::missingDpopKey()` for a clean reconnect instead (opt out via `allow_key_regeneration`).
- Lock-timeout no longer replays a consumed single-use refresh token: it adopts a concurrently-rotated token or fails transient (`TransientAuthFailureException`), never `invalid_grant`.
- Reactive refresh on a `401` (non-`use_dpop_nonce`): the access token can be stale before its stored window elapses because `expires_in` is unreliable across PDSes, so a call now force-refreshes once and replays.
- README: corrected the stale `TokenRefreshed`/`TokenRefreshing` event references to `SessionUpdated`/`SessionRefreshing`.

## Version 0.3.0

### Fixed
- Serialize OAuth token refreshes per-DID with an atomic cache lock. Refresh tokens are single-use, so when a publish fanned out into several authenticated calls they could race to refresh the same stale token: the first rotated it, the rest got `invalid_grant`, and the session was stranded until the user reconnected. The lock now re-reads credentials before refreshing and adopts a concurrently-rotated token instead of replaying a consumed one.

### Added
- `session.refresh_serialize`, `session.refresh_lock_wait`, and `session.refresh_lock_ttl` config (env: `ATP_REFRESH_SERIALIZE`, `ATP_REFRESH_LOCK_WAIT`, `ATP_REFRESH_LOCK_TTL`). Serialization is on by default and requires a lock-capable cache store (redis, memcached, database, array, file); disable to restore the previous unsynchronized behavior.

## Version 0.2.4

### Fixed
- Apply boolean query-param encoding to the **public** (unauthenticated) XRPC path too. v0.2.3 fixed only the authenticated path, so public calls such as `Atp::public($pds)->atproto->repo->listRecords(...)` still sent the default `reverse=false` as `"0"` and were rejected by strict PDSes with `Expected boolean value type (got "0")`.

## Version 0.2.3

### Fixed
- Encode boolean query parameters as the literal strings `true`/`false` on GET/DELETE requests. PHP previously cast `false` to `"0"`, which XRPC rejected with `Expected boolean value type (got "0")` (e.g. `com.atproto.repo.listRecords` with the default `reverse: false`).

## Version 1.0

### Added
- Everything
