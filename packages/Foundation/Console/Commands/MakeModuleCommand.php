<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Support\Exceptions\DuplicateItemException;
use Epsicube\Support\Facades\Modules;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:module')]
class MakeModuleCommand extends GeneratorCommand
{
    protected $name = 'make:module';

    protected $description = 'Create a new module class';

    protected $type = 'Module';

    public function handle(): ?bool
    {
        $result = parent::handle();

        if ($result === false) {
            return $result;
        }

        if (! file_exists($this->laravel->bootstrapPath('modules.php'))) {
            file_put_contents($this->laravel->bootstrapPath('modules.php'), '<?php return [];');
        }
        ServiceProvider::addProviderToBootstrapFile(
            $this->qualifyClass($this->getNameInput()),
            $this->laravel->bootstrapPath('modules.php'),
        );

        if (! $this->option('disabled')) {
            // Force register modules for activation
            try {
                Modules::register($this->laravel->make(
                    $this->qualifyClass($this->getNameInput()),
                    ['app' => $this->laravel]
                ));
            } catch (DuplicateItemException) {
            }
            $this->callSilent(ModulesEnableCommand::class, ['identifier' => [$this->getModuleIdentifier()]]);
        }

        return $result;
    }

    protected function buildClass($name): string
    {
        return str_replace(
            ['{{ name }}', '{{ identifier }}'],
            [$this->getNameInput(), $this->getModuleIdentifier()],
            parent::buildClass($name)
        );
    }

    protected function getModuleIdentifier(): string
    {
        return 'custom::'.Str::slug($this->getNameInput());
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/module.stub');
    }

    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Modules';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the module already exists'],
            ['disabled', 'd', InputOption::VALUE_NONE, 'Do not automatically enable the module'],
        ];
    }
}
