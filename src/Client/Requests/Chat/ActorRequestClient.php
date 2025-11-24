<?php

namespace SocialDept\AtpClient\Client\Requests\Chat;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class Actor extends Request
{
    /**
     * Get actor metadata
     */
    public function getActorMetadata(): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.actor.getActorMetadata'
        );
    }

    /**
     * Export account data
     */
    public function exportAccountData(): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.actor.exportAccountData'
        );
    }

    /**
     * Delete account
     */
    public function deleteAccount(): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.actor.deleteAccount'
        );
    }
}
