<?php

namespace SocialDept\AtpClient\Console;

use Illuminate\Console\Command;
use SocialDept\AtpClient\Auth\OAuthKey;

class GenerateOAuthKeyCommand extends Command
{
    protected $signature = 'atp-client:generate-key';

    protected $description = 'Generate a new ES256 private key for OAuth';

    public function handle(): int
    {
        $this->info('Generating new ES256 private key...');
        $this->newLine();

        $key = OAuthKey::create();
        $private = $key->privateB64();

        $this->components->twoColumnDetail('Private Key', '<fg=yellow>'.$private.'</>');
        $this->newLine();

        $this->components->info('Add this to your .env file:');
        $this->line('ATP_OAUTH_PRIVATE_KEY="'.$private.'"');
        $this->newLine();

        $this->components->warn('Keep this key secret! Do not commit it to version control.');

        return self::SUCCESS;
    }
}
