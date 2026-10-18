<?php

namespace SocialDept\AtpClient\Console\Concerns;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Facades\Atp;

/**
 * Signing in as the account that publishes the lexicons: with an app password
 * when one is given, otherwise through a session the app already holds for
 * that account.
 */
trait ActsAsAuthority
{
    protected function authorityClient(): ?AtpClient
    {
        $actor = $this->option('as');

        if (! is_string($actor) || $actor === '') {
            $this->components->error('Pass --as with the handle or DID that publishes these lexicons.');

            return null;
        }

        $password = $this->option('password') ?: env('ATP_LEXICON_APP_PASSWORD');

        if (! is_string($password) || $password === '') {
            return Atp::as($actor);
        }

        return Atp::login($actor, $password);
    }
}
