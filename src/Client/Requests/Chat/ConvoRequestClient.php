<?php

namespace SocialDept\AtpClient\Client\Requests\Chat;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;

class ConvoRequestClient extends Request
{
    /**
     * Get conversation
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.getConvo)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-convo
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.getConvo')]
    public function getConvo(string $convoId): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.convo.getConvo',
            params: compact('convoId')
        );
    }

    /**
     * Get conversation for members
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.getConvoForMembers)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-convo-for-members
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.getConvoForMembers')]
    public function getConvoForMembers(array $members): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.convo.getConvoForMembers',
            params: compact('members')
        );
    }

    /**
     * List conversations
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.listConvos)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-list-convos
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.listConvos')]
    public function listConvos(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.convo.listConvos',
            params: compact('limit', 'cursor')
        );
    }

    /**
     * Get messages
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.getMessages)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-messages
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.getMessages')]
    public function getMessages(
        string $convoId,
        int $limit = 50,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.convo.getMessages',
            params: compact('convoId', 'limit', 'cursor')
        );
    }

    /**
     * Send message
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.sendMessage)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-send-message
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.sendMessage')]
    public function sendMessage(string $convoId, array $message): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.convo.sendMessage',
            body: compact('convoId', 'message')
        );
    }

    /**
     * Send message batch
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.sendMessageBatch)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-send-message-batch
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.sendMessageBatch')]
    public function sendMessageBatch(array $items): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.convo.sendMessageBatch',
            body: compact('items')
        );
    }

    /**
     * Delete message for self
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.deleteMessageForSelf)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-delete-message-for-self
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.deleteMessageForSelf')]
    public function deleteMessageForSelf(string $convoId, string $messageId): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.convo.deleteMessageForSelf',
            body: compact('convoId', 'messageId')
        );
    }

    /**
     * Update read status
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.updateRead)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-update-read
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.updateRead')]
    public function updateRead(string $convoId, ?string $messageId = null): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.convo.updateRead',
            body: compact('convoId', 'messageId')
        );
    }

    /**
     * Mute conversation
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.muteConvo)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-mute-convo
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.muteConvo')]
    public function muteConvo(string $convoId): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.convo.muteConvo',
            body: compact('convoId')
        );
    }

    /**
     * Unmute conversation
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.unmuteConvo)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-unmute-convo
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.unmuteConvo')]
    public function unmuteConvo(string $convoId): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.convo.unmuteConvo',
            body: compact('convoId')
        );
    }

    /**
     * Leave conversation
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.leaveConvo)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-leave-convo
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.leaveConvo')]
    public function leaveConvo(string $convoId): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.convo.leaveConvo',
            body: compact('convoId')
        );
    }

    /**
     * Get log
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.getLog)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-log
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.getLog')]
    public function getLog(?string $cursor = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.convo.getLog',
            params: compact('cursor')
        );
    }
}
