<?php

namespace SocialDept\AtpClient\Builders\Concerns;

use SocialDept\AtpClient\RichText\FacetDetector;
use SocialDept\AtpResolver\Facades\Resolver;

trait BuildsRichText
{
    protected string $text = '';

    protected array $facets = [];

    /**
     * Add plain text
     */
    public function text(string $text): self
    {
        $this->text .= $text;

        return $this;
    }

    /**
     * Add one or more new lines
     */
    public function newLine(int $count = 1): self
    {
        $this->text .= str_repeat("\n", $count);

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

        if (! $did) {
            try {
                $did = Resolver::handleToDid($handle);
            } catch (\Exception $e) {
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

        $detected = FacetDetector::detect($text);

        foreach ($detected as $facet) {
            $facet['index']['byteStart'] += $start;
            $facet['index']['byteEnd'] += $start;
            $this->facets[] = $facet;
        }

        return $this;
    }

    /**
     * Get current byte position (UTF-8 byte offset)
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
     * Get text and facets as array
     */
    protected function getTextAndFacets(): array
    {
        return [
            'text' => $this->text,
            'facets' => $this->facets,
        ];
    }

    /**
     * Get grapheme count (closest to what AT Protocol uses for limits)
     */
    public function getGraphemeCount(): int
    {
        return grapheme_strlen($this->text);
    }

    /**
     * Check if text exceeds AT Protocol post limit (300 graphemes)
     */
    public function exceedsLimit(int $limit = 300): bool
    {
        return $this->getGraphemeCount() > $limit;
    }
}
