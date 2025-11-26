<?php

namespace SocialDept\AtpClient\Http;

use Illuminate\Http\Client\Response as LaravelResponse;
use Illuminate\Support\Collection;

class Response
{
    public function __construct(
        protected LaravelResponse $response
    ) {}

    /**
     * Get JSON response data
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        return $this->response->json($key, $default);
    }

    /**
     * Get response as collection
     */
    public function collect(?string $key = null): Collection
    {
        return $this->response->collect($key);
    }

    /**
     * Get HTTP status code
     */
    public function status(): int
    {
        return $this->response->status();
    }

    /**
     * Check if response was successful
     */
    public function successful(): bool
    {
        return $this->response->successful();
    }

    /**
     * Check if response failed
     */
    public function failed(): bool
    {
        return $this->response->failed();
    }

    /**
     * Get response body
     */
    public function body(): string
    {
        return $this->response->body();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->response->json();
    }

    /**
     * Get underlying Laravel response
     */
    public function getResponse(): LaravelResponse
    {
        return $this->response;
    }
}
