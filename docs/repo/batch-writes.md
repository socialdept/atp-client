# Batch writes

Part of the [atp-client docs](../introduction.md#documentation). This page covers writing several records in one commit.

`applyWrites()` creates, updates and deletes several records in one commit. Either every write lands or none does:

```php
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\Create;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\Delete;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\Update;

$response = $client->atproto->repo->applyWrites([
    new Create(collection: 'app.bsky.feed.post', value: ['$type' => 'app.bsky.feed.post', 'text' => 'Hello', 'createdAt' => now()->toIso8601String()]),
    new Update(collection: 'app.bsky.actor.profile', rkey: 'self', value: $profile),
    new Delete(collection: 'app.bsky.feed.like', rkey: $likeRkey),
]);

$response->commit?->rev;  // the single commit all three share
$response->results;       // one CreateResult, UpdateResult or DeleteResult per write, in order
```

A record value can be an array or a generated record object, which is serialized with its `$type`. Each write is checked against the session's scopes for its collection and action. `validate` is left out by default, so the PDS validates only lexicons it knows. Pass `validate: true` to require it or `false` to skip it, and `swapCommit` to apply the writes only on top of a known commit.
