<?php

namespace SocialDept\AtpClient\Console;

use Illuminate\Console\Command;
use SocialDept\AtpClient\Console\Concerns\ActsAsAuthority;
use SocialDept\AtpSchema\Authoring\BreakingChanges;
use SocialDept\AtpSchema\Authoring\Issue;
use SocialDept\AtpSchema\Authoring\LexiconDiff;
use SocialDept\AtpSchema\Authoring\LexiconFiles;
use SocialDept\AtpSchema\Authoring\LexiconLinter;
use SocialDept\AtpSchema\Authoring\NetworkLexicons;
use Throwable;

class LexiconPublishCommand extends Command
{
    use ActsAsAuthority;

    protected $signature = 'lexicon:publish
                            {nsid?* : Lexicons to publish, defaults to every local file}
                            {--as= : Handle or DID of the account that publishes them}
                            {--password= : An app password for that account, or set ATP_LEXICON_APP_PASSWORD}
                            {--update : Allow replacing a lexicon that is already published}
                            {--allow-breaking : Allow an update the breaking-change check refuses}
                            {--skip-dns-check : Publish although DNS does not name this account yet}
                            {--dry-run : Report what would be published without writing}';

    protected $description = 'Publish local lexicons to your repo as com.atproto.lexicon.schema records';

    public function handle(NetworkLexicons $network, LexiconLinter $linter): int
    {
        $files = LexiconFiles::fromConfig();
        $targets = $this->argument('nsid') ?: array_filter(array_keys($files->all()), fn (string $key) => ! str_ends_with($key, '.json'));

        if ($targets === []) {
            $this->components->warn("No lexicon files under {$files->root()}.");

            return self::SUCCESS;
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

        foreach ($targets as $nsid) {
            $outcome = $this->publish($client, $did, $nsid, $files, $network, $linter);
            $failed = $failed || str_starts_with($outcome, '<fg=red>');
            $this->components->twoColumnDetail($nsid, $outcome);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Publish one lexicon, answering what happened for the report.
     */
    protected function publish(mixed $client, string $did, string $nsid, LexiconFiles $files, NetworkLexicons $network, LexiconLinter $linter): string
    {
        try {
            $lexicon = $files->read($nsid);
        } catch (Throwable $e) {
            return '<fg=red>'.$e->getMessage().'</>';
        }

        if (($lexicon['id'] ?? null) !== $nsid) {
            return '<fg=red>the file declares '.json_encode($lexicon['id'] ?? null).', not '.$nsid.'</>';
        }

        if (Issue::anyErrors($issues = $linter->lint($lexicon))) {
            return '<fg=red>lint errors, run schema:lint: '.implode(', ', array_unique(array_map(fn (Issue $issue) => $issue->name, array_filter($issues, fn (Issue $issue) => $issue->isError())))).'</>';
        }

        // Others find the schema through DNS, so publishing it anywhere DNS
        // does not point would publish it where nobody looks.
        if (! $this->option('skip-dns-check')) {
            $authority = $network->authority($nsid);

            if ($authority !== $did) {
                return '<fg=red>'.NetworkLexicons::dnsName($nsid).($authority === null ? ' is missing' : " names {$authority}").', it should hold "did='.$did.'". Run schema:check-dns</>';
            }
        }

        try {
            $existing = $network->fetchFrom($did, $nsid);
        } catch (Throwable $e) {
            return '<fg=red>could not read the published version: '.$e->getMessage().'</>';
        }

        if ($existing !== null) {
            if (LexiconDiff::same($existing->lexicon, $lexicon)) {
                return '<fg=gray>unchanged</>';
            }

            if (! $this->option('update')) {
                return '<fg=red>already published and different, pass --update to replace it</>';
            }

            $breaking = array_filter(BreakingChanges::between($existing->lexicon, $lexicon), fn (Issue $issue) => $issue->isError());

            if ($breaking !== [] && ! $this->option('allow-breaking')) {
                return '<fg=red>breaking changes, run schema:breaking: '.implode(', ', array_unique(array_map(fn (Issue $issue) => $issue->name, $breaking))).'</>';
            }
        }

        if ($this->option('dry-run')) {
            return '<fg=yellow>would '.($existing === null ? 'publish' : 'update').'</>';
        }

        try {
            $client->atproto->repo->putRecord(
                collection: NetworkLexicons::COLLECTION,
                rkey: $nsid,
                record: ['$type' => NetworkLexicons::COLLECTION, ...$lexicon],
                swapRecord: $existing?->cid,
            );
        } catch (Throwable $e) {
            return '<fg=red>the PDS refused it: '.$e->getMessage().'</>';
        }

        return '<fg=green>'.($existing === null ? 'published' : 'updated').'</>';
    }
}
