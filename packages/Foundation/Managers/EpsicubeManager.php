<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Managers;

use Illuminate\Console\Command;
use InvalidArgumentException;

class EpsicubeManager
{
    /**
     * @var array<string, string>
     */
    protected array $workCommands = [
        'schedule' => 'schedule:work --no-ansi --whisper',
        //        'queue'    => 'queue:work',
    ];

    /** @var array<string, string> */
    protected array $optimizeCommands = [];

    /** @var array<string, string> */
    protected array $clearCommands = [];

    /** @var array<string, string> */
    protected array $installCommands = [];

    /**
     * Register a new work command.
     *
     * @param  string  $key  Unique identifier for the worker
     * @param  string|class-string<Command>  $command  Signature of the command to run
     *
     * @throws InvalidArgumentException
     */
    public function addWorkCommand(string $key, string $command): void
    {
        if (array_key_exists($key, $this->workCommands)) {
            throw new InvalidArgumentException(
                sprintf("A work command with key '%s' already exists.", $key)
            );
        }

        $this->workCommands[$key] = $command;
    }

    public function workCommands(): array
    {
        return array_map(function (string $command) {
            if (class_exists($command) && is_a($command, Command::class, true)) {
                return (new $command)->getName();
            }

            return $command;
        }, $this->workCommands);
    }

    public function addOptimizeCommand(string $key, string $command): void
    {
        if (array_key_exists($key, $this->optimizeCommands)) {
            throw new InvalidArgumentException(
                sprintf("An optimize command with key '%s' already exists.", $key)
            );
        }

        $this->optimizeCommands[$key] = $command;
    }

    public function optimizeCommands(): array
    {
        return $this->optimizeCommands;
    }

    public function addInstallCommand(string $key, string $command): void
    {
        if (array_key_exists($key, $this->installCommands)) {
            throw new InvalidArgumentException(
                sprintf("An install command with key '%s' already exists.", $key)
            );
        }

        $this->installCommands[$key] = $command;
    }

    public function installCommands(): array
    {
        return $this->installCommands;
    }

    public function addClearCommand(string $key, string $command): void
    {
        if (array_key_exists($key, $this->clearCommands)) {
            throw new InvalidArgumentException(
                sprintf("A clear command with key '%s' already exists.", $key)
            );
        }

        $this->clearCommands[$key] = $command;
    }

    public function clearCommands(): array
    {
        return $this->clearCommands;
    }
}
