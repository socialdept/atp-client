<?php

namespace SocialDept\AtpClient\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeAtpClientCommand extends Command
{
    protected $signature = 'make:atp-client
                            {name : The name of the client class}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new ATP domain client extension';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! Str::endsWith($name, 'Client')) {
            $name .= 'Client';
        }

        $path = $this->getPath($name);

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error("Client [{$name}] already exists!");

            return self::FAILURE;
        }

        $this->makeDirectory($path);

        $content = $this->populateStub($this->getStub(), $name);

        $this->files->put($path, $content);

        $this->components->info("Client [{$path}] created successfully.");

        $this->outputRegistrationHint($name);

        return self::SUCCESS;
    }

    protected function getPath(string $name): string
    {
        $basePath = config('atp-client.generators.client_path', 'app/Services/Clients');

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
        $basePath = config('atp-client.generators.client_path', 'app/Services/Clients');

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

    protected function outputRegistrationHint(string $name): void
    {
        $this->newLine();
        $this->components->info('Register the extension in your AppServiceProvider:');
        $this->newLine();

        $namespace = $this->getNamespace();
        $extensionName = Str::of($name)->before('Client')->camel()->toString();

        $this->line("use {$namespace}\\{$name};");
        $this->line("use SocialDept\\AtpClient\\AtpClient;");
        $this->newLine();
        $this->line("// In boot() method:");
        $this->line("AtpClient::extend('{$extensionName}', fn(AtpClient \$atp) => new {$name}(\$atp));");
    }

    protected function getStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use SocialDept\AtpClient\AtpClient;

class {{ class }}
{
    protected AtpClient $atp;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
    }

    //
}
STUB;
    }
}
