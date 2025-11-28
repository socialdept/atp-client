<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Bsky;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Enums\Nsid\BskyActor;
use SocialDept\AtpClient\Data\Responses\Bsky\Actor\GetProfilesResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Actor\GetSuggestionsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Actor\SearchActorsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Actor\SearchActorsTypeaheadResponse;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileViewDetailed;

class ActorPublicRequestClient extends PublicRequest
{
    public function getProfile(string $actor): ProfileViewDetailed
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::GetProfile,
            params: compact('actor')
        );

        return ProfileViewDetailed::fromArray($response->json());
    }

    public function getProfiles(array $actors): GetProfilesResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::GetProfiles,
            params: compact('actors')
        );

        return GetProfilesResponse::fromArray($response->json());
    }

    public function getSuggestions(int $limit = 50, ?string $cursor = null): GetSuggestionsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::GetSuggestions,
            params: compact('limit', 'cursor')
        );

        return GetSuggestionsResponse::fromArray($response->json());
    }

    public function searchActors(string $q, int $limit = 25, ?string $cursor = null): SearchActorsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::SearchActors,
            params: compact('q', 'limit', 'cursor')
        );

        return SearchActorsResponse::fromArray($response->json());
    }

    public function searchActorsTypeahead(string $q, int $limit = 10): SearchActorsTypeaheadResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::SearchActorsTypeahead,
            params: compact('q', 'limit')
        );

        return SearchActorsTypeaheadResponse::fromArray($response->json());
    }
}
