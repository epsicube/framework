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

    public function handle(): int
    {
        $this->components->info('Install Epsicube core and modules.');

        $commands = Epsicube::installCommands();
        $installed = true;

        foreach ($commands as $key => $command) {
            $succeeded = $this->callSilently($command) === self::SUCCESS;
            $installed = $installed && $succeeded;

            $this->components->task($key, fn () => $succeeded);
        }

        if (! $installed) {
            return self::FAILURE;
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
