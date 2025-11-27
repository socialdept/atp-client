<?php

namespace SocialDept\AtpClient\Contracts;

interface HasAtpSession
{
    /**
     * Get the ATP DID associated with this model.
     */
    public function getAtpDid(): ?string;
}
