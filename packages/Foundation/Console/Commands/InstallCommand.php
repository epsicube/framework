<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Support\Facades\Epsicube;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $name = 'epsicube:install';

    protected $description = 'Install Epsicube';

    protected $aliases = [
        'install:epsicube',
        'ec:i',
    ];

    public function handle(): void
    {
        $this->components->info('Install Epsicube core and modules.');

        $commands = Epsicube::installCommands();

        foreach ($commands as $key => $command) {
            $this->components->task($key, fn () => $this->callSilently($command) === 0);
        }

        $this->newLine();
    }
}
