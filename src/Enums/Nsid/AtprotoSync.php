<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum AtprotoSync: string
{
    use HasScopeHelpers;
    case GetBlob = 'com.atproto.sync.getBlob';
    case GetRepo = 'com.atproto.sync.getRepo';
    case ListRepos = 'com.atproto.sync.listRepos';
    case ListReposByCollection = 'com.atproto.sync.listReposByCollection';
    case GetLatestCommit = 'com.atproto.sync.getLatestCommit';
    case GetRecord = 'com.atproto.sync.getRecord';
    case ListBlobs = 'com.atproto.sync.listBlobs';
    case GetBlocks = 'com.atproto.sync.getBlocks';
    case GetRepoStatus = 'com.atproto.sync.getRepoStatus';
}
