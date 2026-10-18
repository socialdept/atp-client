<?php

namespace SocialDept\AtpClient\Console;

use Illuminate\Console\Command;
use SocialDept\AtpClient\Console\Concerns\ActsAsAuthority;
use SocialDept\AtpSchema\Authoring\NetworkLexicons;
use Throwable;

class LexiconUnpublishCommand extends Command
{
    use ActsAsAuthority;

    protected $signature = 'lexicon:unpublish
                            {nsid* : Lexicons to remove from your repo}
                            {--as= : Handle or DID of the account that published them}
                            {--password= : An app password for that account, or set ATP_LEXICON_APP_PASSWORD}
                            {--force : Skip the confirmation}';

    protected $description = 'Remove published lexicons from your repo';

    public function handle(NetworkLexicons $network): int
    {
        $nsids = (array) $this->argument('nsid');

        // Apps that resolved these lexicons keep their copies, but no new one
        // can find them, so a schema others depend on should stay published.
        if (! $this->option('force') && ! $this->confirm('Unpublish '.implode(', ', $nsids).'? Anyone resolving them will no longer find them.')) {
            return self::FAILURE;
        }

        if (($client = $this->authorityClient()) === null) {
            return self::FAILURE;
        }

        try {
            $did = $client->client->repoDid();
        } catch (Throwable $e) {
            $this->components->error('Could not sign in: '.$e->getMessage());

            return self::FAILURE;
        }

        $failed = false;

        foreach ($nsids as $nsid) {
            try {
                if ($network->fetchFrom($did, $nsid) === null) {
                    $this->components->twoColumnDetail($nsid, '<fg=gray>not published</>');

                    continue;
                }

                $client->atproto->repo->deleteRecord(NetworkLexicons::COLLECTION, $nsid);
                $this->components->twoColumnDetail($nsid, '<fg=green>unpublished</>');
            } catch (Throwable $e) {
                $failed = true;
                $this->components->twoColumnDetail($nsid, '<fg=red>'.$e->getMessage().'</>');
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
