# Publishing lexicons

Part of the [atp-client docs](../introduction.md#documentation). This page covers publishing your own lexicons to the network.

Lexicons you author are published as `com.atproto.lexicon.schema` records, keyed by the NSID, in the repo of the account the NSID's `_lexicon` DNS record names. These commands publish the files atp-schema manages in `resources/lexicons` (see its `schema:new`, `schema:lint` and `schema:check-dns`):

```bash
php artisan lexicon:status --did=did:plc:abc          # local files against the network
php artisan lexicon:publish --as=you.example.com      # with ATP_LEXICON_APP_PASSWORD set
php artisan lexicon:publish com.example.thing --as=you.example.com --update
php artisan lexicon:unpublish com.example.old --as=you.example.com
```

`--as` signs in with an app password (`--password`, or `ATP_LEXICON_APP_PASSWORD`), or with a session the app already holds for that account. Publishing is careful by default:

- A lexicon with lint errors is not published.
- DNS must already name the publishing account, since others find the schema through it. `--skip-dns-check` publishes anyway.
- An identical published schema is left alone.
- Replacing a published schema needs `--update`, and is refused when atp-schema's breaking-change check finds a breaking change, unless you pass `--allow-breaking`. The write swaps on the published version, so a concurrent change is not overwritten.
- `--dry-run` reports without writing.
