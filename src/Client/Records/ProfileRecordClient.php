<?php

namespace SocialDept\AtpClient\Client\Records;

use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Record;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\PutRecordResponse;
use SocialDept\AtpClient\Enums\Nsid\BskyActor;
use SocialDept\AtpClient\Enums\Scope;

class ProfileRecordClient extends Request
{
    /**
     * Update profile
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.putRecord AND repo:app.bsky.actor.profile?action=update)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function update(array $profile): PutRecordResponse
    {
        // Ensure $type is set
        if (! isset($profile['$type'])) {
            $profile['$type'] = BskyActor::Profile->value;
        }

        return $this->atp->atproto->repo->putRecord(
            repo: $this->atp->client->session()->did(),
            collection: BskyActor::Profile,
            rkey: 'self', // Profile records always use 'self' as rkey
            record: $profile
        );
    }

    /**
     * Get current profile
     *
     * @requires transition:generic (rpc:com.atproto.repo.getRecord)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.getRecord')]
    public function get(): Record
    {
        $response = $this->atp->atproto->repo->getRecord(
            repo: $this->atp->client->session()->did(),
            collection: BskyActor::Profile,
            rkey: 'self'
        );

        return Record::fromArrayRaw($response->toArray());
    }

    /**
     * Update display name
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.putRecord AND repo:app.bsky.actor.profile?action=update)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function updateDisplayName(string $displayName): PutRecordResponse
    {
        $profile = $this->getOrCreateProfile();
        $profile['displayName'] = $displayName;

        return $this->update($profile);
    }

    /**
     * Update description/bio
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.putRecord AND repo:app.bsky.actor.profile?action=update)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function updateDescription(string $description): PutRecordResponse
    {
        $profile = $this->getOrCreateProfile();
        $profile['description'] = $description;

        return $this->update($profile);
    }

    /**
     * Update avatar
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.putRecord AND repo:app.bsky.actor.profile?action=update)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function updateAvatar(array $avatarBlob): PutRecordResponse
    {
        $profile = $this->getOrCreateProfile();
        $profile['avatar'] = $avatarBlob;

        return $this->update($profile);
    }

    /**
     * Update banner
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.putRecord AND repo:app.bsky.actor.profile?action=update)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function updateBanner(array $bannerBlob): PutRecordResponse
    {
        $profile = $this->getOrCreateProfile();
        $profile['banner'] = $bannerBlob;

        return $this->update($profile);
    }

    /**
     * Get profile or create empty one if doesn't exist
     */
    protected function getOrCreateProfile(): array
    {
        try {
            return $this->get()->value;
        } catch (\Exception $e) {
            // Profile doesn't exist, return empty structure
            return [
                '$type' => BskyActor::Profile->value,
            ];
        }
    }
}
