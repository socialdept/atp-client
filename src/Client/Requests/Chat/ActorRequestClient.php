<?php

namespace SocialDept\AtpClient\Client\Requests\Chat;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class ActorRequestClient extends Request
{
    /**
     * Get actor metadata
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-actor-export-account-data
     */
    public function getActorMetadata(): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.actor.getActorMetadata'
        );
    }

    /**
     * Export account data
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-actor-export-account-data
     */
    public function exportAccountData(): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.actor.exportAccountData'
        );
    }

    /**
     * Delete account
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-actor-delete-account
     */
    public function deleteAccount(): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.actor.deleteAccount'
        );
    }
}
