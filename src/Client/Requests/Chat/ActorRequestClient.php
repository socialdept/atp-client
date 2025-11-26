<?php

namespace SocialDept\AtpClient\Client\Requests\Chat;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;

class ActorRequestClient extends Request
{
    /**
     * Get actor metadata
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.actor.getActorMetadata)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-actor-export-account-data
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.actor.getActorMetadata')]
    public function getActorMetadata(): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.actor.getActorMetadata'
        );
    }

    /**
     * Export account data
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.actor.exportAccountData)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-actor-export-account-data
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.actor.exportAccountData')]
    public function exportAccountData(): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.actor.exportAccountData'
        );
    }

    /**
     * Delete account
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.actor.deleteAccount)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-actor-delete-account
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.actor.deleteAccount')]
    public function deleteAccount(): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.actor.deleteAccount'
        );
    }
}
