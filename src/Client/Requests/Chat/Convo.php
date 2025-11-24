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
        return $this->atp->client->get('chat.bsky.convo.getConvo', compact('convoId'));
    }

    /**
     * Get conversation for members
     */
    public function getConvoForMembers(array $members): Response
    {
        return $this->atp->client->get('chat.bsky.convo.getConvoForMembers', compact('members'));
    }

    /**
     * List conversations
     */
    public function listConvos(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('chat.bsky.convo.listConvos', compact('limit', 'cursor'));
    }

    /**
     * Get messages
     */
    public function getMessages(string $convoId, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('chat.bsky.convo.getMessages', compact('convoId', 'limit', 'cursor'));
    }

    /**
     * Send message
     */
    public function sendMessage(string $convoId, array $message): Response
    {
        return $this->atp->client->post('chat.bsky.convo.sendMessage', compact('convoId', 'message'));
    }

    /**
     * Send message batch
     */
    public function sendMessageBatch(array $items): Response
    {
        return $this->atp->client->post('chat.bsky.convo.sendMessageBatch', compact('items'));
    }

    /**
     * Delete message
     */
    public function deleteMessageForSelf(string $convoId, string $messageId): Response
    {
        return $this->atp->client->post('chat.bsky.convo.deleteMessageForSelf', compact('convoId', 'messageId'));
    }

    /**
     * Update read status
     */
    public function updateRead(string $convoId, ?string $messageId = null): Response
    {
        return $this->atp->client->post('chat.bsky.convo.updateRead', compact('convoId', 'messageId'));
    }

    /**
     * Mute conversation
     */
    public function muteConvo(string $convoId): Response
    {
        return $this->atp->client->post('chat.bsky.convo.muteConvo', compact('convoId'));
    }

    /**
     * Unmute conversation
     */
    public function unmuteConvo(string $convoId): Response
    {
        return $this->atp->client->post('chat.bsky.convo.unmuteConvo', compact('convoId'));
    }

    /**
     * Leave conversation
     */
    public function leaveConvo(string $convoId): Response
    {
        return $this->atp->client->post('chat.bsky.convo.leaveConvo', compact('convoId'));
    }

    /**
     * Get log
     */
    public function getLog(?string $cursor = null): Response
    {
        return $this->atp->client->get('chat.bsky.convo.getLog', compact('cursor'));
    }
}
