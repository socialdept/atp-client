<?php

namespace SocialDept\AtpClient\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeAtpRequestCommand extends Command
{
    protected $signature = 'make:atp-request
                            {name : The name of the request client class}
                            {--domain=bsky : The domain to extend (bsky, atproto, chat, ozone)}
                            {--public : Generate a public request client instead of authenticated}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new ATP request client extension for an existing domain';

    protected array $validDomains = ['bsky', 'atproto', 'chat', 'ozone'];

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $domain = $this->option('domain');
        $isPublic = $this->option('public');

        if (! in_array($domain, $this->validDomains)) {
            $this->components->error("Invalid domain [{$domain}]. Valid domains: ".implode(', ', $this->validDomains));

            return self::FAILURE;
        }

        if (! Str::endsWith($name, 'Client')) {
            $name .= 'Client';
        }

        $path = $this->getPath($name);

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error("Request client [{$name}] already exists!");

            return self::FAILURE;
        }

        $this->makeDirectory($path);

        $stub = $isPublic ? $this->getPublicStub() : $this->getStub();
        $content = $this->populateStub($stub, $name);

        $this->files->put($path, $content);

        $this->components->info("Request client [{$path}] created successfully.");

        $this->outputRegistrationHint($name, $domain, $isPublic);

        return self::SUCCESS;
    }

    protected function getPath(string $name): string
    {
        $basePath = config('client.generators.request_path', 'app/Services/Clients/Requests');

        return base_path($basePath.'/'.$name.'.php');
    }

    protected function makeDirectory(string $path): void
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }
    }

    protected function getNamespace(): string
    {
        $basePath = config('client.generators.request_path', 'app/Services/Clients/Requests');

        return Str::of($basePath)
            ->replace('/', '\\')
            ->ucfirst()
            ->replace('App', 'App')
            ->toString();
    }

    protected function populateStub(string $stub, string $name): string
    {
        return str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$this->getNamespace(), $name],
            $stub
        );
    }

    protected function outputRegistrationHint(string $name, string $domain, bool $isPublic): void
    {
        $this->newLine();
        $this->components->info('Register the extension in your AppServiceProvider:');
        $this->newLine();

        $namespace = $this->getNamespace();
        $extensionName = Str::of($name)->before('Client')->camel()->toString();
        $clientClass = $isPublic ? 'AtpPublicClient' : 'AtpClient';

        $this->line("use {$namespace}\\{$name};");
        $this->line("use SocialDept\\AtpClient\\".($isPublic ? 'Client\\Public\\' : '').$clientClass.';');
        $this->newLine();
        $this->line("// In boot() method:");
        $this->line("{$clientClass}::extendDomain('{$domain}', '{$extensionName}', fn(\$domain) => new {$name}(\$domain));");
    }

    protected function getStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use SocialDept\AtpClient\Client\Requests\Request;

class {{ class }} extends Request
{
    //
}
STUB;
    }

    protected function getPublicStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;

class {{ class }} extends PublicRequest
{
    //
}
STUB;
    }
}
