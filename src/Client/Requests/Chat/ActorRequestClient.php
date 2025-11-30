<?php

namespace SocialDept\AtpClient\Client\Requests\Chat;

use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\EmptyResponse;
use SocialDept\AtpClient\Enums\Nsid\ChatActor;
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
    #[ScopedEndpoint(Scope::TransitionChat, granular: 'rpc:chat.bsky.actor.getActorMetadata')]
    public function getActorMetadata(): Response
    {
        return $this->atp->client->get(
            endpoint: ChatActor::GetActorMetadata
        );
    }

    /**
     * Export account data (returns JSONL stream)
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.actor.exportAccountData)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-actor-export-account-data
     */
    #[ScopedEndpoint(Scope::TransitionChat, granular: 'rpc:chat.bsky.actor.exportAccountData')]
    public function exportAccountData(): Response
    {
        return $this->atp->client->get(
            endpoint: ChatActor::ExportAccountData
        );
    }

    /**
     * Delete account
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.actor.deleteAccount)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-actor-delete-account
     */
    #[ScopedEndpoint(Scope::TransitionChat, granular: 'rpc:chat.bsky.actor.deleteAccount')]
    public function deleteAccount(): EmptyResponse
    {
        $this->atp->client->post(
            endpoint: ChatActor::DeleteAccount
        );

        return new EmptyResponse;
    }
}
