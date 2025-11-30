<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Bsky\Actor\GetProfilesResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Actor\GetSuggestionsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Actor\SearchActorsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Actor\SearchActorsTypeaheadResponse;
use SocialDept\AtpClient\Enums\Nsid\BskyActor;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileViewDetailed;

class ActorRequestClient extends Request
{
    /**
     * Get actor profile
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-get-profile
     */
    public function getProfile(string $actor): ProfileViewDetailed
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::GetProfile,
            params: compact('actor')
        );

        return ProfileViewDetailed::fromArray($response->json());
    }

    /**
     * Get multiple actor profiles
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-get-profiles
     */
    public function getProfiles(array $actors): GetProfilesResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::GetProfiles,
            params: compact('actors')
        );

        return GetProfilesResponse::fromArray($response->json());
    }

    /**
     * Get suggestions for actors to follow
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-get-suggestions
     */
    public function getSuggestions(int $limit = 50, ?string $cursor = null): GetSuggestionsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::GetSuggestions,
            params: compact('limit', 'cursor')
        );

        return GetSuggestionsResponse::fromArray($response->json());
    }

    /**
     * Search for actors
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-search-actors
     */
    public function searchActors(string $q, int $limit = 25, ?string $cursor = null): SearchActorsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::SearchActors,
            params: compact('q', 'limit', 'cursor')
        );

        return SearchActorsResponse::fromArray($response->json());
    }

    /**
     * Search for actors matching a prefix (typeahead/autocomplete)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-search-actors-typeahead
     */
    public function searchActorsTypeahead(string $q, int $limit = 10): SearchActorsTypeaheadResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::SearchActorsTypeahead,
            params: compact('q', 'limit')
        );

        return SearchActorsTypeaheadResponse::fromArray($response->json());
    }
}
