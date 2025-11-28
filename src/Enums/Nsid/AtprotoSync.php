<?php

namespace SocialDept\AtpClient\Enums\Nsid;

enum AtprotoSync: string
{
    case GetBlob = 'com.atproto.sync.getBlob';
    case GetRepo = 'com.atproto.sync.getRepo';
    case ListRepos = 'com.atproto.sync.listRepos';
    case GetLatestCommit = 'com.atproto.sync.getLatestCommit';
    case GetRecord = 'com.atproto.sync.getRecord';
    case ListBlobs = 'com.atproto.sync.listBlobs';
    case GetBlocks = 'com.atproto.sync.getBlocks';
    case GetRepoStatus = 'com.atproto.sync.getRepoStatus';
}
