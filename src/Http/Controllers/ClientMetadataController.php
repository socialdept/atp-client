<?php

namespace SocialDept\AtpClient\Http\Controllers;

use Illuminate\Http\JsonResponse;
use SocialDept\AtpClient\Http\Config\OAuthMetadata;

class ClientMetadataController
{
    /**
     * Return OAuth client metadata
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(OAuthMetadata::clientMetadata());
    }
}
