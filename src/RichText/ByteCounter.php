<?php

namespace SocialDept\AtpClient\RichText;

class ByteCounter
{
    /**
     * Get the byte length of a UTF-8 string
     */
    public static function length(string $text): int
    {
        return strlen($text);
    }

    /**
     * Get byte position of character at given index
     */
    public static function bytePosition(string $text, int $charIndex): int
    {
        $chars = mb_str_split($text, 1, 'UTF-8');
        $bytePos = 0;

        for ($i = 0; $i < $charIndex && $i < count($chars); $i++) {
            $bytePos += strlen($chars[$i]);
        }

        return $bytePos;
    }

    /**
     * Get substring by byte positions
     */
    public static function substring(string $text, int $byteStart, int $byteEnd): string
    {
        return substr($text, $byteStart, $byteEnd - $byteStart);
    }

    /**
     * Validate byte positions don't split multi-byte characters
     */
    public static function validateBytePositions(string $text, int $byteStart, int $byteEnd): bool
    {
        // Check if positions are within bounds
        if ($byteStart < 0 || $byteEnd > strlen($text) || $byteStart > $byteEnd) {
            return false;
        }

        // Ensure we're not splitting a multi-byte character
        $before = substr($text, 0, $byteStart);
        $middle = substr($text, $byteStart, $byteEnd - $byteStart);
        $after = substr($text, $byteEnd);

        // Check if reconstructed string is valid UTF-8
        return mb_check_encoding($before, 'UTF-8')
            && mb_check_encoding($middle, 'UTF-8')
            && mb_check_encoding($after, 'UTF-8');
    }
}
