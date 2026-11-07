# Feeds

Part of the [atp-client docs](../introduction.md#documentation). This page covers timelines, author feeds, search, threads and pagination.

## Get Your Timeline

```php
$timeline = $client->bsky->feed->getTimeline(limit: 50);

foreach ($timeline->feed as $item) {
    $post = $item->post;
    echo $post->author->handle . ': ' . $post->record['text'];
}
```

## Pagination with Cursors

```php
$cursor = null;

do {
    $timeline = $client->bsky->feed->getTimeline(limit: 100, cursor: $cursor);

    foreach ($timeline->feed as $item) {
        // Process posts
    }

    $cursor = $timeline->cursor;
} while ($cursor);
```

## Get Author Feed

```php
$feed = $client->bsky->feed->getAuthorFeed(
    actor: 'someone.bsky.social',
    limit: 50
);
```

## Search Posts

```php
$results = $client->bsky->feed->searchPosts(
    q: 'laravel php',
    limit: 25
);
```

## Get Post Thread

```php
$thread = $client->bsky->feed->getPostThread(
    uri: 'at://did:plc:.../app.bsky.feed.post/...',
    depth: 6
);
```

## Get Likes on a Post

```php
$likes = $client->bsky->feed->getLikes(uri: 'at://...');
```

## Get Reposts

```php
$reposts = $client->bsky->feed->getRepostedBy(uri: 'at://...');
```
