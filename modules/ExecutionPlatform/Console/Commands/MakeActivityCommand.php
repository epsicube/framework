<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:activity')]
class MakeActivityCommand extends GeneratorCommand
{
    protected $name = 'make:activity';

    protected $description = 'Create a new activity class';

    protected $type = 'Activity';

    protected function buildClass($name): string
    {
        return str_replace(
            ['{{ identifier }}'],
            [Str::snake($this->getNameInput())],
            parent::buildClass($name)
        );
    }

    protected function getStub(): string
    {
        return __DIR__.'/stubs/activity.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Activities';
    }

    protected function rootNamespace(): string
    {
        return 'EpsicubeModules\ExecutionPlatform';
    }

    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return __DIR__.'/../../'.str_replace('\\', '/', $name).'.php';
    }
}
