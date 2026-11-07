# Posts

Part of the [atp-client docs](../introduction.md#documentation). This page covers creating, replying to, quoting and deleting posts, with rich text, images and link cards.

## Create a Simple Post

```php
$post = $client->bsky->post->create('Hello, Bluesky!');

// Returns a CreateRecordResponse with uri and cid
echo $post->uri;  // at://did:plc:.../app.bsky.feed.post/...
echo $post->cid;  // bafyre...
```

## Rich Text with Mentions, Links, and Hashtags

Use the `TextBuilder` for posts with rich text formatting:

```php
use SocialDept\AtpClient\RichText\TextBuilder;

$content = TextBuilder::make()
    ->text('Check out ')
    ->mention('someone.bsky.social')
    ->text(' and visit ')
    ->link('our website', 'https://example.com')
    ->text(' ')
    ->tag('Laravel')
    ->toArray();

$post = $client->bsky->post->create($content);
```

Or use auto-detection on plain text:

```php
// Facets are automatically detected
$post = $client->bsky->post->create(
    'Hello @someone.bsky.social! Check out https://example.com #Bluesky'
);
```

## Reply to a Post

Replies, quotes and embeds are built with `PostBuilder`, which `create()` accepts in place of text:

```php
use SocialDept\AtpClient\Builders\PostBuilder;
use SocialDept\AtpClient\Data\StrongRef;

$parent = new StrongRef(uri: 'at://...', cid: 'bafyre...');
$root = $parent; // Same as parent for direct replies

$reply = $client->bsky->post->create(
    PostBuilder::make()
        ->text('This is a reply!')
        ->replyTo(parent: $parent, root: $root)
);
```

## Quote Post

```php
$quotedPost = new StrongRef(uri: 'at://...', cid: 'bafyre...');

$quote = $client->bsky->post->create(
    PostBuilder::make()
        ->text('Interesting take!')
        ->quote($quotedPost)
);
```

## Post with Images

```php
// Upload from a Laravel request
$blob = $client->atproto->repo->uploadBlob($request->file('image'));

// Or from a file path
$blob = $client->atproto->repo->uploadBlob(new SplFileInfo('/path/to/image.jpg'));

// Or from raw binary data (mimeType required)
$blob = $client->atproto->repo->uploadBlob(
    file: file_get_contents('/path/to/image.jpg'),
    mimeType: 'image/jpeg'
);

$post = $client->bsky->post->create(
    PostBuilder::make()
        ->text('Check out this photo!')
        ->images([
            [
                'image' => $blob,
                'alt' => 'Description of the image',
            ],
        ])
);
```

## Post with External Link Card

```php
$post = $client->bsky->post->create(
    PostBuilder::make()
        ->text('Great article about Laravel')
        ->external(
            uri: 'https://example.com/article',
            title: 'Article Title',
            description: 'A brief description of the article...'
        )
);
```

## Delete a Post

```php
// Extract rkey from the post URI
$rkey = basename($post->uri);

$client->bsky->post->delete($rkey);
```
