<?php

namespace SocialDept\AtpClient\RichText;

use SocialDept\AtpResolver\Facades\Resolver;

class TextBuilder
{
    protected string $text = '';
    protected array $facets = [];

    /**
     * Create a new text builder instance
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Build text using a callback
     */
    public static function build(callable $callback): array
    {
        $builder = new self();
        $callback($builder);

        return $builder->toArray();
    }

    /**
     * Add plain text
     */
    public function text(string $text): self
    {
        $this->text .= $text;

        return $this;
    }

    /**
     * Add a new line
     */
    public function newLine(): self
    {
        $this->text .= "\n";

        return $this;
    }

    /**
     * Add mention (@handle)
     */
    public function mention(string $handle, ?string $did = null): self
    {
        $handle = ltrim($handle, '@');
        $start = $this->getBytePosition();
        $this->text .= '@'.$handle;
        $end = $this->getBytePosition();

        // Resolve DID if not provided
        if (! $did) {
            try {
                $did = Resolver::handleToDid($handle);
            } catch (\Exception $e) {
                // If resolution fails, still add the text but skip the facet
                return $this;
            }
        }

        $this->facets[] = [
            'index' => [
                'byteStart' => $start,
                'byteEnd' => $end,
            ],
            'features' => [
                [
                    '$type' => 'app.bsky.richtext.facet#mention',
                    'did' => $did,
                ],
            ],
        ];

        return $this;
    }

    /**
     * Add link with custom display text
     */
    public function link(string $text, string $uri): self
    {
        $start = $this->getBytePosition();
        $this->text .= $text;
        $end = $this->getBytePosition();

        $this->facets[] = [
            'index' => [
                'byteStart' => $start,
                'byteEnd' => $end,
            ],
            'features' => [
                [
                    '$type' => 'app.bsky.richtext.facet#link',
                    'uri' => $uri,
                ],
            ],
        ];

        return $this;
    }

    /**
     * Add a URL (displayed as-is)
     */
    public function url(string $url): self
    {
        return $this->link($url, $url);
    }

    /**
     * Add hashtag
     */
    public function tag(string $tag): self
    {
        $tag = ltrim($tag, '#');

        $start = $this->getBytePosition();
        $this->text .= '#'.$tag;
        $end = $this->getBytePosition();

        $this->facets[] = [
            'index' => [
                'byteStart' => $start,
                'byteEnd' => $end,
            ],
            'features' => [
                [
                    '$type' => 'app.bsky.richtext.facet#tag',
                    'tag' => $tag,
                ],
            ],
        ];

        return $this;
    }

    /**
     * Auto-detect and add facets from plain text
     */
    public function autoDetect(string $text): self
    {
        $start = $this->getBytePosition();
        $this->text .= $text;

        // Detect facets in the added text
        $detected = FacetDetector::detect($text);

        // Adjust byte positions to account for existing text
        foreach ($detected as $facet) {
            $facet['index']['byteStart'] += $start;
            $facet['index']['byteEnd'] += $start;
            $this->facets[] = $facet;
        }

        return $this;
    }

    /**
     * Get current byte position
     */
    protected function getBytePosition(): int
    {
        return strlen($this->text);
    }

    /**
     * Get the text content
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Get the facets
     */
    public function getFacets(): array
    {
        return $this->facets;
    }

    /**
     * Build the final text and facets array
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'facets' => $this->facets,
        ];
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Create from existing text with auto-detection
     */
    public static function parse(string $text): array
    {
        return [
            'text' => $text,
            'facets' => FacetDetector::detect($text),
        ];
    }

    /**
     * Get character count (for post limits)
     */
    public function getCharacterCount(): int
    {
        return mb_strlen($this->text, 'UTF-8');
    }

    /**
     * Get byte count
     */
    public function getByteCount(): int
    {
        return strlen($this->text);
    }

    /**
     * Check if text exceeds AT Protocol post limit (300 graphemes)
     */
    public function exceedsLimit(int $limit = 300): bool
    {
        return $this->getGraphemeCount() > $limit;
    }

    /**
     * Get grapheme count (closest to what AT Protocol uses)
     */
    public function getGraphemeCount(): int
    {
        return grapheme_strlen($this->text);
    }

    /**
     * Convert to string (returns text only)
     */
    public function __toString(): string
    {
        return $this->text;
    }
}
