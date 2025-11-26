<?php

namespace SocialDept\AtpClient\Client\Records;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\StrongRef;

class ProfileRecordClient extends Request
{
    /**
     * Update profile
     */
    public function update(array $profile): StrongRef
    {
        // Ensure $type is set
        if (! isset($profile['$type'])) {
            $profile['$type'] = 'app.bsky.actor.profile';
        }

        $response = $this->atp->client->post(
            endpoint: 'com.atproto.repo.putRecord',
            body: [
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.actor.profile',
                'rkey' => 'self', // Profile records always use 'self' as rkey
                'record' => $profile,
            ]
        );

        return StrongRef::fromResponse($response->json());
    }

    /**
     * Get current profile
     */
    public function get(): array
    {
        $response = $this->atp->client->get(
            endpoint: 'com.atproto.repo.getRecord',
            params: [
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.actor.profile',
                'rkey' => 'self',
            ]
        );

        return $response->json('value');
    }

    /**
     * Update display name
     */
    public function updateDisplayName(string $displayName): StrongRef
    {
        $profile = $this->getOrCreateProfile();
        $profile['displayName'] = $displayName;

        return $this->update($profile);
    }

    /**
     * Update description/bio
     */
    public function updateDescription(string $description): StrongRef
    {
        $profile = $this->getOrCreateProfile();
        $profile['description'] = $description;

        return $this->update($profile);
    }

    /**
     * Update avatar
     */
    public function updateAvatar(array $avatarBlob): StrongRef
    {
        $profile = $this->getOrCreateProfile();
        $profile['avatar'] = $avatarBlob;

        return $this->update($profile);
    }

    /**
     * Update banner
     */
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
