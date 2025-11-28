<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum ChatConvo: string
{
    use HasScopeHelpers;
    case GetConvo = 'chat.bsky.convo.getConvo';
    case GetConvoForMembers = 'chat.bsky.convo.getConvoForMembers';
    case ListConvos = 'chat.bsky.convo.listConvos';
    case GetMessages = 'chat.bsky.convo.getMessages';
    case SendMessage = 'chat.bsky.convo.sendMessage';
    case SendMessageBatch = 'chat.bsky.convo.sendMessageBatch';
    case DeleteMessageForSelf = 'chat.bsky.convo.deleteMessageForSelf';
    case UpdateRead = 'chat.bsky.convo.updateRead';
    case MuteConvo = 'chat.bsky.convo.muteConvo';
    case UnmuteConvo = 'chat.bsky.convo.unmuteConvo';
    case LeaveConvo = 'chat.bsky.convo.leaveConvo';
    case GetLog = 'chat.bsky.convo.getLog';
}
