<?php

namespace SocialDept\AtpClient\Client\Requests\Chat;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Chat\Convo\GetLogResponse;
use SocialDept\AtpClient\Data\Responses\Chat\Convo\GetMessagesResponse;
use SocialDept\AtpClient\Data\Responses\Chat\Convo\LeaveConvoResponse;
use SocialDept\AtpClient\Data\Responses\Chat\Convo\ListConvosResponse;
use SocialDept\AtpClient\Data\Responses\Chat\Convo\SendMessageBatchResponse;
use SocialDept\AtpClient\Enums\Nsid\ChatConvo;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\ConvoView;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\DeletedMessageView;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\MessageView;

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
    public function getConvo(string $convoId): ConvoView
    {
        $response = $this->atp->client->get(
            endpoint: ChatConvo::GetConvo,
            params: compact('convoId')
        );

        return ConvoView::fromArray($response->json()['convo']);
    }

    /**
     * Get conversation for members
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.getConvoForMembers)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-convo-for-members
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.getConvoForMembers')]
    public function getConvoForMembers(array $members): ConvoView
    {
        $response = $this->atp->client->get(
            endpoint: ChatConvo::GetConvoForMembers,
            params: compact('members')
        );

        return ConvoView::fromArray($response->json()['convo']);
    }

    /**
     * List conversations
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.listConvos)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-list-convos
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.listConvos')]
    public function listConvos(int $limit = 50, ?string $cursor = null): ListConvosResponse
    {
        $response = $this->atp->client->get(
            endpoint: ChatConvo::ListConvos,
            params: compact('limit', 'cursor')
        );

        return ListConvosResponse::fromArray($response->json());
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
    ): GetMessagesResponse {
        $response = $this->atp->client->get(
            endpoint: ChatConvo::GetMessages,
            params: compact('convoId', 'limit', 'cursor')
        );

        return GetMessagesResponse::fromArray($response->json());
    }

    /**
     * Send message
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.sendMessage)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-send-message
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.sendMessage')]
    public function sendMessage(string $convoId, array $message): MessageView
    {
        $response = $this->atp->client->post(
            endpoint: ChatConvo::SendMessage,
            body: compact('convoId', 'message')
        );

        return MessageView::fromArray($response->json());
    }

    /**
     * Send message batch
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.sendMessageBatch)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-send-message-batch
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.sendMessageBatch')]
    public function sendMessageBatch(array $items): SendMessageBatchResponse
    {
        $response = $this->atp->client->post(
            endpoint: ChatConvo::SendMessageBatch,
            body: compact('items')
        );

        return SendMessageBatchResponse::fromArray($response->json());
    }

    /**
     * Delete message for self
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.deleteMessageForSelf)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-delete-message-for-self
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.deleteMessageForSelf')]
    public function deleteMessageForSelf(string $convoId, string $messageId): DeletedMessageView
    {
        $response = $this->atp->client->post(
            endpoint: ChatConvo::DeleteMessageForSelf,
            body: compact('convoId', 'messageId')
        );

        return DeletedMessageView::fromArray($response->json());
    }

    /**
     * Update read status
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.updateRead)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-update-read
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.updateRead')]
    public function updateRead(string $convoId, ?string $messageId = null): ConvoView
    {
        $response = $this->atp->client->post(
            endpoint: ChatConvo::UpdateRead,
            body: compact('convoId', 'messageId')
        );

        return ConvoView::fromArray($response->json()['convo']);
    }

    /**
     * Mute conversation
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.muteConvo)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-mute-convo
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.muteConvo')]
    public function muteConvo(string $convoId): ConvoView
    {
        $response = $this->atp->client->post(
            endpoint: ChatConvo::MuteConvo,
            body: compact('convoId')
        );

        return ConvoView::fromArray($response->json()['convo']);
    }

    /**
     * Unmute conversation
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.unmuteConvo)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-unmute-convo
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.unmuteConvo')]
    public function unmuteConvo(string $convoId): ConvoView
    {
        $response = $this->atp->client->post(
            endpoint: ChatConvo::UnmuteConvo,
            body: compact('convoId')
        );

        return ConvoView::fromArray($response->json()['convo']);
    }

    /**
     * Leave conversation
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.leaveConvo)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-leave-convo
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.leaveConvo')]
    public function leaveConvo(string $convoId): LeaveConvoResponse
    {
        $response = $this->atp->client->post(
            endpoint: ChatConvo::LeaveConvo,
            body: compact('convoId')
        );

        return LeaveConvoResponse::fromArray($response->json());
    }

    /**
     * Get log
     *
     * @requires transition:chat.bsky (rpc:chat.bsky.convo.getLog)
     *
     * @see https://docs.bsky.app/docs/api/chat-bsky-convo-get-log
     */
    #[RequiresScope(Scope::TransitionChat, granular: 'rpc:chat.bsky.convo.getLog')]
    public function getLog(?string $cursor = null): GetLogResponse
    {
        $response = $this->atp->client->get(
            endpoint: ChatConvo::GetLog,
            params: compact('cursor')
        );

        return GetLogResponse::fromArray($response->json());
    }
}
