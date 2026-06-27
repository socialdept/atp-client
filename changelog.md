# Changelog

All notable changes to `AtpClient` will be documented in this file.

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
