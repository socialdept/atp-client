<?php

namespace SocialDept\AtpClient\Enums;

enum Scope: string
{
    // Transition scopes (current)
    case Atproto = 'atproto';
    case TransitionGeneric = 'transition:generic';
    case TransitionEmail = 'transition:email';
    case TransitionChat = 'transition:chat.bsky';

    /**
     * Build a repo scope string for record operations.
     *
     * @param  string  $collection  The collection NSID (e.g., 'app.bsky.feed.post')
     * @param  array|null  $actions  The action (create, update, delete)
     *
     * @return string
     */
    public static function repo(string $collection, ?array $actions = []): string
    {
        $scope = "repo:{$collection}";

        if (!empty($actions)) {
            $scope .= '?' . implode('&', array_map(fn ($action) => "action={$action}", $actions));
        }

        return $scope;
    }

    /**
     * Build an RPC scope string for endpoint access.
     *
     * @param  string  $lxm  The lexicon method ID (e.g., 'app.bsky.feed.getTimeline')
     */
    public static function rpc(string $lxm): string
    {
        return "rpc:{$lxm}";
    }

    /**
     * Build a blob scope string for uploads.
     *
     * @param  string|null  $mimeType  The mime type pattern (e.g., 'image/*', '*\/*')
     */
    public static function blob(?string $mimeType = null): string
    {
        return 'blob:'.($mimeType ?? '*/*');
    }

    /**
     * Build an account scope string.
     *
     * @param  string  $attr  The account attribute (e.g., 'email', 'status')
     */
    public static function account(string $attr): string
    {
        return "account:{$attr}";
    }

    /**
     * Build an identity scope string.
     *
     * @param  string  $attr  The identity attribute (e.g., 'handle')
     */
    public static function identity(string $attr): string
    {
        return "identity:{$attr}";
    }
}
