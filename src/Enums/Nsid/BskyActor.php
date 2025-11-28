<?php

namespace SocialDept\AtpClient\Enums\Nsid;

use SocialDept\AtpClient\Enums\Nsid\Concerns\HasScopeHelpers;

enum BskyActor: string
{
    use HasScopeHelpers;
    case GetProfile = 'app.bsky.actor.getProfile';
    case GetProfiles = 'app.bsky.actor.getProfiles';
    case GetSuggestions = 'app.bsky.actor.getSuggestions';
    case SearchActors = 'app.bsky.actor.searchActors';
    case SearchActorsTypeahead = 'app.bsky.actor.searchActorsTypeahead';

    // Record type
    case Profile = 'app.bsky.actor.profile';
}
