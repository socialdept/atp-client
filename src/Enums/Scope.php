<?php

namespace SocialDept\AtpClient\Enums;

use BackedEnum;

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
     * @param  string|BackedEnum  $collection  The collection NSID (e.g., 'app.bsky.feed.post')
     * @param  array|null  $actions  The action (create, update, delete)
     *
     * @return string
     */
    public static function repo(string|BackedEnum $collection, ?array $actions = []): string
    {
        $collection = $collection instanceof BackedEnum ? $collection->value : $collection;
        $scope = "repo:{$collection}";

        if (! empty($actions)) {
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
     * Request a permission set, a bundle of permissions published as a lexicon.
     *
     * The authorization server resolves the set from its NSID and shows the
     * user its title and description. When it issues the token it expands the
     * set into the granular permissions it contains, so the granted scope
     * lists those, not this include: scope.
     *
     * `$aud` fills in the audience of any rpc permission in the set marked
     * `inheritAud`, in the combined `did#serviceId` form.
     *
     * @example Scope::include('com.example.authFull')
     * @example Scope::include('com.example.authFull', 'did:web:api.example.com#svc_appview')
     */
    public static function include(string|BackedEnum $nsid, ?string $aud = null): string
    {
        $nsid = $nsid instanceof BackedEnum ? $nsid->value : $nsid;

        if ($aud === null) {
            return "include:{$nsid}";
        }

        // Matches the spec's own form, which encodes the fragment's # but
        // leaves the DID's colons readable.
        return "include:{$nsid}?aud=".strtr(rawurlencode($aud), ['%3A' => ':']);
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
