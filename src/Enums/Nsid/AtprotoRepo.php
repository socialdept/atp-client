<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum AtprotoRepo: string
{
    use HasScopeHelpers;
    case CreateRecord = 'com.atproto.repo.createRecord';
    case DeleteRecord = 'com.atproto.repo.deleteRecord';
    case PutRecord = 'com.atproto.repo.putRecord';
    case GetRecord = 'com.atproto.repo.getRecord';
    case ListRecords = 'com.atproto.repo.listRecords';
    case UploadBlob = 'com.atproto.repo.uploadBlob';
    case DescribeRepo = 'com.atproto.repo.describeRepo';
}
