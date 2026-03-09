<?php

namespace SocialDept\AtpClient\Testing;

use SocialDept\AtpClient\Http\Response;

class ResponseSequence
{
    /** @var array<Response|array|\Closure> */
    protected array $responses = [];

    protected int $position = 0;

    protected Response|array|\Closure|null $fallback = null;

    protected bool $failWhenEmpty = true;

    /**
     * Push a response onto the sequence.
     */
    public function push(Response|array|\Closure $response): static
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Push an error response onto the sequence.
     */
    public function pushError(string $errorCode, string $message, int $status = 400): static
    {
        return $this->push(FakeResponse::error($errorCode, $message, $status));
    }

    /**
     * Push a successful response onto the sequence.
     */
    public function pushOk(array $data = []): static
    {
        return $this->push(FakeResponse::ok($data));
    }

    /**
     * Set a fallback response when the sequence is exhausted.
     */
    public function whenEmpty(Response|array|\Closure $response): static
    {
        $this->fallback = $response;
        $this->failWhenEmpty = false;

        return $this;
    }

    /**
     * Don't throw when sequence is exhausted — just return empty response.
     */
    public function dontFailWhenEmpty(): static
    {
        $this->failWhenEmpty = false;

        return $this;
    }

    /**
     * Get the next response in the sequence.
     *
     * @throws \RuntimeException When sequence is exhausted and no fallback is set
     */
    public function next(string $endpoint): Response
    {
        if ($this->position < count($this->responses)) {
            $response = $this->responses[$this->position];
            $this->position++;

            return $this->resolveResponse($response, $endpoint);
        }

        if ($this->fallback !== null) {
            return $this->resolveResponse($this->fallback, $endpoint);
        }

        if ($this->failWhenEmpty) {
            throw new \RuntimeException(
                "Response sequence for [{$endpoint}] is empty. " .
                'Use ->whenEmpty() to set a fallback or ->dontFailWhenEmpty() to suppress this error.'
            );
        }

        return FakeResponse::ok();
    }

    /**
     * Check if the sequence has more responses.
     */
    public function hasMore(): bool
    {
        return $this->position < count($this->responses);
    }

    /**
     * Check if the sequence is empty (exhausted or never had responses).
     */
    public function isEmpty(): bool
    {
        return count($this->responses) === 0;
    }

    /**
     * Resolve a response value to a Response instance.
     */
    protected function resolveResponse(Response|array|\Closure $response, string $endpoint): Response
    {
        if ($response instanceof \Closure) {
            $result = $response($endpoint);

            if ($result instanceof Response) {
                return $result;
            }

            return FakeResponse::ok(is_array($result) ? $result : []);
        }

        if (is_array($response)) {
            return FakeResponse::ok($response);
        }

        return $response;
    }
}
