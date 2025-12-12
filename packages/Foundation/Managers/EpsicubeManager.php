<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Managers;

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

    /**
     * Register a new work command.
     *
     * @param  string  $key  Unique identifier for the worker
     * @param  string  $command  Signature of the command to run
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
        return $this->workCommands;
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
