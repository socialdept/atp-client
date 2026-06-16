# Changelog

All notable changes to `AtpClient` will be documented in this file.

## Version 0.2.4

### Fixed
- Apply boolean query-param encoding to the **public** (unauthenticated) XRPC path too. v0.2.3 fixed only the authenticated path, so public calls such as `Atp::public($pds)->atproto->repo->listRecords(...)` still sent the default `reverse=false` as `"0"` and were rejected by strict PDSes with `Expected boolean value type (got "0")`.

## Version 0.2.3

### Fixed
- Encode boolean query parameters as the literal strings `true`/`false` on GET/DELETE requests. PHP previously cast `false` to `"0"`, which XRPC rejected with `Expected boolean value type (got "0")` (e.g. `com.atproto.repo.listRecords` with the default `reverse: false`).

## Version 1.0

### Added
- Everything
