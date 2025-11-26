<?php

namespace SocialDept\AtpClient\Http\Controllers;

use Illuminate\Http\JsonResponse;
use SocialDept\AtpClient\Http\Config\OAuthMetadata;

class JwksController
{
    /**
     * Return JWKS (JSON Web Key Set)
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(OAuthMetadata::jwks());
    }
}
