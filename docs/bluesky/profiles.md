# Profiles

Part of the [atp-client docs](../introduction.md#documentation). This page covers reading and updating profiles.

## Get a Profile

```php
$profile = $client->bsky->actor->getProfile('someone.bsky.social');

echo $profile->displayName;
echo $profile->description;
echo $profile->followersCount;
```

## Update Your Profile

```php
// Update display name
$client->bsky->profile->updateDisplayName('New Name');

// Update bio/description
$client->bsky->profile->updateDescription('Laravel developer building on AT Protocol');

// Replace the whole profile record (fields left out are removed)
$client->bsky->profile->update([
    'displayName' => 'New Name',
    'description' => 'New bio here',
]);
```

## Update Avatar

```php
$blob = $client->atproto->repo->uploadBlob(new SplFileInfo('/path/to/avatar.jpg'));

$client->bsky->profile->updateAvatar($blob->toArray());
```
