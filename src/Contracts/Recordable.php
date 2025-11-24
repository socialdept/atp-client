<?php

namespace SocialDept\AtpClient\Contracts;

interface Recordable
{
    /**
     * Convert record to array for XRPC
     */
    public function toArray(): array;

    /**
     * Get the record type (lexicon NSID)
     */
    public function getType(): string;
}
