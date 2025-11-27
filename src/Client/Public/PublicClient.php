<?php

namespace SocialDept\AtpClient\Client\Public;

use Illuminate\Support\Facades\Http;
use SocialDept\AtpClient\Exceptions\AtpResponseException;
use SocialDept\AtpClient\Http\Response;

class PublicClient
{
    public function __construct(
        protected string $serviceUrl
    ) {}

    public function get(string $endpoint, array $params = []): Response
    {
        $url = rtrim($this->serviceUrl, '/') . '/xrpc/' . $endpoint;
        $params = array_filter($params, fn ($v) => !is_null($v));

        $response = Http::get($url, $params);

        if ($response->failed() || isset($response->json()['error'])) {
            throw AtpResponseException::fromResponse($response, $endpoint);
        }

        return new Response($response);
    }

    public function serviceUrl(): string
    {
        return $this->serviceUrl;
    }
}
