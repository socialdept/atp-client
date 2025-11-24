<?php

namespace SocialDept\AtpClient\Client;

use Illuminate\Http\Client\Factory;
use SocialDept\AtpClient\Auth\DPoPNonceManager;
use SocialDept\AtpClient\Http\HasHttp;
use SocialDept\AtpClient\Http\Response;
use SocialDept\AtpClient\Session\SessionManager;

class ChatClient
{
    use HasHttp;

    public function __construct(
        SessionManager $sessions,
        Factory $http,
        string $identifier,
    ) {
        $this->sessions = $sessions;
        $this->http = $http;
        $this->identifier = $identifier;
        $this->nonceManager = app(DPoPNonceManager::class);
    }

    /**
     * Get conversation
     */
    public function getConvo(string $convoId): Response
    {
        return $this->get('chat.bsky.convo.getConvo', compact('convoId'));
    }

    /**
     * Get conversation for members
     */
    public function getConvoForMembers(array $members): Response
    {
        return $this->get('chat.bsky.convo.getConvoForMembers', compact('members'));
    }

    /**
     * List conversations
     */
    public function listConvos(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('chat.bsky.convo.listConvos', compact('limit', 'cursor'));
    }

    /**
     * Get messages
     */
    public function getMessages(string $convoId, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('chat.bsky.convo.getMessages', compact('convoId', 'limit', 'cursor'));
    }

    /**
     * Send message
     */
    public function sendMessage(string $convoId, array $message): Response
    {
        return $this->post('chat.bsky.convo.sendMessage', compact('convoId', 'message'));
    }

    /**
     * Send message batch
     */
    public function sendMessageBatch(array $items): Response
    {
        return $this->post('chat.bsky.convo.sendMessageBatch', compact('items'));
    }

    /**
     * Delete message
     */
    public function deleteMessageForSelf(string $convoId, string $messageId): Response
    {
        return $this->post('chat.bsky.convo.deleteMessageForSelf', compact('convoId', 'messageId'));
    }

    /**
     * Update read status
     */
    public function updateRead(string $convoId, ?string $messageId = null): Response
    {
        return $this->post('chat.bsky.convo.updateRead', compact('convoId', 'messageId'));
    }

    /**
     * Mute conversation
     */
    public function muteConvo(string $convoId): Response
    {
        return $this->post('chat.bsky.convo.muteConvo', compact('convoId'));
    }

    /**
     * Unmute conversation
     */
    public function unmuteConvo(string $convoId): Response
    {
        return $this->post('chat.bsky.convo.unmuteConvo', compact('convoId'));
    }

    /**
     * Leave conversation
     */
    public function leaveConvo(string $convoId): Response
    {
        return $this->post('chat.bsky.convo.leaveConvo', compact('convoId'));
    }

    /**
     * Get log
     */
    public function getLog(?string $cursor = null): Response
    {
        return $this->get('chat.bsky.convo.getLog', compact('cursor'));
    }

    /**
     * Get actor metadata
     */
    public function getActorMetadata(): Response
    {
        return $this->get('chat.bsky.actor.getActorMetadata');
    }

    /**
     * Export account data
     */
    public function exportAccountData(): Response
    {
        return $this->get('chat.bsky.actor.exportAccountData');
    }

    /**
     * Delete account
     */
    public function deleteAccount(): Response
    {
        return $this->post('chat.bsky.actor.deleteAccount');
    }
}
