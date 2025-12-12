<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Support\Facades\Epsicube;
use Illuminate\Console\Command;

class ClearCommand extends Command
{
    protected $name = 'epsicube:clear';

    protected $description = 'Clear Epsicube cached optimizations';

    public function handle(): void
    {
        $this->components->info('Clearing cached Epsicube files.');

        $commands = Epsicube::optimizeCommands();

        foreach ($commands as $key => $command) {
            $this->components->task($key, fn () => $this->callSilently($command) === 0);
        }

        $this->newLine();
    }
}
