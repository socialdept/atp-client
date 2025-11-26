<?php

namespace SocialDept\AtpClient\Client\Records;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\StrongRef;
use SocialDept\AtpClient\Enums\Scope;

class ProfileRecordClient extends Request
{
    /**
     * Update profile
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.putRecord AND repo:app.bsky.actor.profile?action=update)
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[RequiresScope(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function update(array $profile): StrongRef
    {
        // Ensure $type is set
        if (! isset($profile['$type'])) {
            $profile['$type'] = 'app.bsky.actor.profile';
        }

        $response = $this->atp->atproto->repo->putRecord(
            repo: $this->atp->client->session()->did(),
            collection: 'app.bsky.actor.profile',
            rkey: 'self', // Profile records always use 'self' as rkey
            record: $profile
        );

        return StrongRef::fromResponse($response->json());
    }

    /**
     * Get current profile
     *
     * @requires transition:generic (rpc:com.atproto.repo.getRecord)
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.getRecord')]
    public function get(): array
    {
        $response = $this->atp->atproto->repo->getRecord(
            repo: $this->atp->client->session()->did(),
            collection: 'app.bsky.actor.profile',
            rkey: 'self'
        );

        return $response->json('value');
    }

    /**
     * Update display name
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.putRecord AND repo:app.bsky.actor.profile?action=update)
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[RequiresScope(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function updateDisplayName(string $displayName): StrongRef
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
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[RequiresScope(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function updateDescription(string $description): StrongRef
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
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[RequiresScope(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function updateAvatar(array $avatarBlob): StrongRef
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
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[RequiresScope(Scope::TransitionGeneric, granular: 'repo:app.bsky.actor.profile?action=update')]
    public function updateBanner(array $bannerBlob): StrongRef
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
            return $this->get();
        } catch (\Exception $e) {
            // Profile doesn't exist, return empty structure
            return [
                '$type' => 'app.bsky.actor.profile',
            ];
        }
    }
}
