<?php

namespace SocialDept\AtpClient\RichText;

use SocialDept\AtpSupport\Facades\Resolver;

class FacetDetector
{
    /**
     * Auto-detect all facets (mentions, links, tags) from text
     */
    public static function detect(string $text): array
    {
        $facets = [];

        // Detect mentions
        $facets = array_merge($facets, static::detectMentions($text));

        // Detect URLs
        $facets = array_merge($facets, static::detectLinks($text));

        // Detect tags
        $facets = array_merge($facets, static::detectTags($text));

        // Sort facets by byte position
        usort($facets, fn ($a, $b) => $a['index']['byteStart'] <=> $b['index']['byteStart']);

        return $facets;
    }

    /**
     * Detect mentions (@handle.bsky.social)
     */
    public static function detectMentions(string $text): array
    {
        $facets = [];
        $pattern = '/@([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?/';

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            $handle = ltrim($match[0], '@');
            $byteStart = $match[1];
            $byteEnd = $byteStart + strlen($match[0]);

            try {
                $did = Resolver::handleToDid($handle);

                $facets[] = [
                    'index' => [
                        'byteStart' => $byteStart,
                        'byteEnd' => $byteEnd,
                    ],
                    'features' => [
                        [
                            '$type' => 'app.bsky.richtext.facet#mention',
                            'did' => $did,
                        ],
                    ],
                ];
            } catch (\Exception $e) {
                // Skip if handle cannot be resolved
                continue;
            }
        }

        return $facets;
    }

    /**
     * Detect URLs (http:// and https://)
     */
    public static function detectLinks(string $text): array
    {
        $facets = [];
        $pattern = '/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)/';

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            $url = $match[0];
            $byteStart = $match[1];
            $byteEnd = $byteStart + strlen($url);

            $facets[] = [
                'index' => [
                    'byteStart' => $byteStart,
                    'byteEnd' => $byteEnd,
                ],
                'features' => [
                    [
                        '$type' => 'app.bsky.richtext.facet#link',
                        'uri' => $url,
                    ],
                ],
            ];
        }

        return $facets;
    }

    /**
     * Detect hashtags (#tag)
     */
    public static function detectTags(string $text): array
    {
        $facets = [];
        $pattern = '/#([a-zA-Z0-9_]+)/u';

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => $match) {
            $fullTag = $match[0]; // includes #
            $tag = $matches[1][$i][0]; // without #
            $byteStart = $match[1];
            $byteEnd = $byteStart + strlen($fullTag);

            $facets[] = [
                'index' => [
                    'byteStart' => $byteStart,
                    'byteEnd' => $byteEnd,
                ],
                'features' => [
                    [
                        '$type' => 'app.bsky.richtext.facet#tag',
                        'tag' => $tag,
                    ],
                ],
            ];
        }

        return $facets;
    }

    /**
     * Merge overlapping or adjacent facets
     */
    public static function mergeFacets(array $facets): array
    {
        if (empty($facets)) {
            return [];
        }

        // Sort by byte position
        usort($facets, fn ($a, $b) => $a['index']['byteStart'] <=> $b['index']['byteStart']);

        $merged = [];
        $current = $facets[0];

        for ($i = 1; $i < count($facets); $i++) {
            $next = $facets[$i];

            // Check for overlap
            if ($current['index']['byteEnd'] >= $next['index']['byteStart']) {
                // Merge features
                $current['features'] = array_merge($current['features'], $next['features']);
                $current['index']['byteEnd'] = max($current['index']['byteEnd'], $next['index']['byteEnd']);
            } else {
                $merged[] = $current;
                $current = $next;
            }
        }

        $merged[] = $current;

        return $merged;
    }
}
