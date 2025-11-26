<?php

namespace SocialDept\AtpClient\Client\Requests\Chat;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class ConvoRequestClient extends Request
{
    /**
     * Get conversation
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-convo
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-convo-for-members
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-list-convos
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-messages
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-send-message
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-send-message-batch
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-delete-message-for-self
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-update-read
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-mute-convo
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-unmute-convo
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-leave-convo
     */
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
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-log
     */
    public function getLog(?string $cursor = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.convo.getLog',
            params: compact('cursor')
        );
    }
}
