<?php

namespace SocialDept\AtpClient\Testing;

class RecordedRequest
{
    public function __construct(
        public readonly string $endpoint,
        public readonly string $method,
        public readonly ?array $params = null,
        public readonly ?array $body = null,
        public readonly ?string $did = null,
        public readonly bool $isPublicMode = false,
    ) {
    }

    /**
     * Check if request was made to a specific endpoint.
     */
    public function is(string $endpoint): bool
    {
        return $this->endpoint === $endpoint;
    }

    /**
     * Check if request matches an endpoint pattern (supports * wildcards).
     */
    public function matches(string $pattern): bool
    {
        if ($pattern === $this->endpoint) {
            return true;
        }

        $regex = '/^' . str_replace('\*', '[^.]+', preg_quote($pattern, '/')) . '$/';

        return (bool) preg_match($regex, $this->endpoint);
    }

    /**
     * Check if request has a specific param value.
     */
    public function hasParam(string $key, mixed $value = null): bool
    {
        if (! isset($this->params[$key])) {
            return false;
        }

        if ($value === null) {
            return true;
        }

        return $this->params[$key] === $value;
    }

    /**
     * Check if request has a specific body value.
     */
    public function hasBody(string $key, mixed $value = null): bool
    {
        if (! isset($this->body[$key])) {
            return false;
        }

        if ($value === null) {
            return true;
        }

        return $this->body[$key] === $value;
    }
}
