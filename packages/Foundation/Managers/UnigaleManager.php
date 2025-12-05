<?php

declare(strict_types=1);

namespace UniGale\Foundation\Managers;

use InvalidArgumentException;

class UnigaleManager
{
    /**
     * @var array<string, string>
     */
    protected array $workCommands = [
        'schedule' => 'schedule:work',
        'queue'    => 'queue:work',
    ];

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
}
