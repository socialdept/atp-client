<?php

namespace SocialDept\AtpClient\RichText;

use SocialDept\AtpClient\Builders\Concerns\BuildsRichText;

class TextBuilder
{
    use BuildsRichText;

    /**
     * Create a new text builder instance
     */
    public static function make(): self
    {
        return new self;
    }

    /**
     * Build text using a callback
     */
    public static function build(callable $callback): array
    {
        $builder = new self;
        $callback($builder);

        return $builder->toArray();
    }

    /**
     * Build the final text and facets array
     */
    public function toArray(): array
    {
        return $this->getTextAndFacets();
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
     * Convert to string (returns text only)
     */
    public function __toString(): string
    {
        return $this->text;
    }
}
