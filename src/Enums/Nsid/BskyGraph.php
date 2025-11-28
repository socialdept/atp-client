<?php

namespace SocialDept\AtpClient\Enums\Nsid;

enum BskyGraph: string
{
    case GetFollowers = 'app.bsky.graph.getFollowers';
    case GetFollows = 'app.bsky.graph.getFollows';
    case GetKnownFollowers = 'app.bsky.graph.getKnownFollowers';
    case GetList = 'app.bsky.graph.getList';
    case GetLists = 'app.bsky.graph.getLists';
    case GetRelationships = 'app.bsky.graph.getRelationships';
    case GetStarterPack = 'app.bsky.graph.getStarterPack';
    case GetStarterPacks = 'app.bsky.graph.getStarterPacks';
    case GetSuggestedFollowsByActor = 'app.bsky.graph.getSuggestedFollowsByActor';

    // Record type
    case Follow = 'app.bsky.graph.follow';
}
