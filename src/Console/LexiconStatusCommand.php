<?php

namespace SocialDept\AtpClient\Console;

use Illuminate\Console\Command;
use SocialDept\AtpSchema\Authoring\LexiconDiff;
use SocialDept\AtpSchema\Authoring\LexiconFiles;
use SocialDept\AtpSchema\Authoring\NetworkLexicons;
use Throwable;

class LexiconStatusCommand extends Command
{
    protected $signature = 'lexicon:status
                            {nsid?* : Lexicons to check, defaults to every local file}
                            {--did= : The DID that should publish them}';

    protected $description = 'Compare local lexicons with what the network publishes';

    public function handle(NetworkLexicons $network): int
    {
        $files = LexiconFiles::fromConfig();
        $targets = $this->argument('nsid') ?: array_filter(array_keys($files->all()), fn (string $key) => ! str_ends_with($key, '.json'));
        $expected = $this->option('did');
        $behind = false;

        foreach ($targets as $nsid) {
            try {
                $local = $files->read($nsid);
                $authority = $network->authority($nsid);

                if ($authority === null) {
                    $status = '<fg=yellow>'.NetworkLexicons::dnsName($nsid).' is missing</>';
                } elseif (is_string($expected) && $authority !== $expected) {
                    $status = "<fg=yellow>DNS names {$authority}, not {$expected}</>";
                } elseif (($published = $network->fetchFrom($authority, $nsid)) === null) {
                    $status = '<fg=yellow>not published</>';
                } elseif (LexiconDiff::same($published->lexicon, $local)) {
                    $status = '<fg=green>published, in sync</>';
                } else {
                    $status = '<fg=yellow>published, local copy differs</>';
                }
            } catch (Throwable $e) {
                $status = '<fg=red>'.$e->getMessage().'</>';
            }

            $behind = $behind || ! str_contains($status, 'in sync');
            $this->components->twoColumnDetail($nsid, $status);
        }

        return $behind ? self::FAILURE : self::SUCCESS;
    }
}
