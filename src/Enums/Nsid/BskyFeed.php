<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum BskyFeed: string
{
    use HasScopeHelpers;
    case DescribeFeedGenerator = 'app.bsky.feed.describeFeedGenerator';
    case GetAuthorFeed = 'app.bsky.feed.getAuthorFeed';
    case GetActorFeeds = 'app.bsky.feed.getActorFeeds';
    case GetActorLikes = 'app.bsky.feed.getActorLikes';
    case GetFeed = 'app.bsky.feed.getFeed';
    case GetFeedGenerator = 'app.bsky.feed.getFeedGenerator';
    case GetFeedGenerators = 'app.bsky.feed.getFeedGenerators';
    case GetLikes = 'app.bsky.feed.getLikes';
    case GetPostThread = 'app.bsky.feed.getPostThread';
    case GetPosts = 'app.bsky.feed.getPosts';
    case GetQuotes = 'app.bsky.feed.getQuotes';
    case GetRepostedBy = 'app.bsky.feed.getRepostedBy';
    case GetSuggestedFeeds = 'app.bsky.feed.getSuggestedFeeds';
    case GetTimeline = 'app.bsky.feed.getTimeline';
    case SearchPosts = 'app.bsky.feed.searchPosts';

    // Record types
    case Post = 'app.bsky.feed.post';
    case Like = 'app.bsky.feed.like';
}
