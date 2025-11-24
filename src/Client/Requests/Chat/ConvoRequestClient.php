<?php

namespace SocialDept\AtpClient\Client\Requests\Chat;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class Convo extends Request
{
    /**
     * Get conversation
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
     */
    public function sendMessageBatch(array $items): Response
    {
        return $this->atp->client->post(
            endpoint: 'chat.bsky.convo.sendMessageBatch',
            body: compact('items')
        );
    }

    /**
     * Delete message
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
     */
    public function getLog(?string $cursor = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'chat.bsky.convo.getLog',
            params: compact('cursor')
        );
    }
}
