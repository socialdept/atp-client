# Social graph

Part of the [atp-client docs](../introduction.md#documentation). This page covers follows and likes.

## Follow a User

```php
// Follow requires the user's DID
$follow = $client->bsky->follow->create('did:plc:...');
```

## Unfollow a User

```php
// Get the rkey from the follow record URI
$client->bsky->follow->delete($rkey);
```

## Like a Post

```php
use SocialDept\AtpClient\Data\StrongRef;

$postRef = new StrongRef(uri: 'at://...', cid: 'bafyre...');

$like = $client->bsky->like->create($postRef);
```

## Unlike a Post

```php
$client->bsky->like->delete($rkey);
```
