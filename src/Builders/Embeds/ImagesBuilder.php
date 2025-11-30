<?php

namespace SocialDept\AtpClient\Builders\Embeds;

class ImagesBuilder
{
    protected array $images = [];

    /**
     * Create a new images builder instance
     */
    public static function make(): self
    {
        return new self;
    }

    /**
     * Add an image to the embed
     *
     * @param  mixed  $blob  BlobReference or blob array
     * @param  string  $alt  Alt text for the image
     * @param  array|null  $aspectRatio  [width, height] aspect ratio
     */
    public function add(mixed $blob, string $alt, ?array $aspectRatio = null): self
    {
        $image = [
            'image' => $this->normalizeBlob($blob),
            'alt' => $alt,
        ];

        if ($aspectRatio !== null) {
            $image['aspectRatio'] = [
                'width' => $aspectRatio[0],
                'height' => $aspectRatio[1],
            ];
        }

        $this->images[] = $image;

        return $this;
    }

    /**
     * Get all images
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Check if builder has images
     */
    public function hasImages(): bool
    {
        return ! empty($this->images);
    }

    /**
     * Get the count of images
     */
    public function count(): int
    {
        return count($this->images);
    }

    /**
     * Convert to embed array format
     */
    public function toArray(): array
    {
        return [
            '$type' => 'app.bsky.embed.images',
            'images' => $this->images,
        ];
    }

    /**
     * Normalize blob to array format
     */
    protected function normalizeBlob(mixed $blob): array
    {
        if (is_array($blob)) {
            return $blob;
        }

        if (method_exists($blob, 'toArray')) {
            return $blob->toArray();
        }

        return (array) $blob;
    }
}
