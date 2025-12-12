<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Support\Facades\Epsicube;
use Illuminate\Console\Command;

class CacheCommand extends Command
{
    protected $name = 'epsicube:cache';

    protected $description = 'Generate and cache Epsicube optimizations';

    public function handle(): void
    {
        $this->components->info('Caching Epsicube.');

        $commands = Epsicube::optimizeCommands();
        foreach ($commands as $key => $command) {
            $this->components->task($key, fn () => $this->callSilently($command) === 0);
        }
        $this->newLine();
    }
}
