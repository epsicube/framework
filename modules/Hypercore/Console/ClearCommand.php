<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use UniGaleModules\Hypercore\Facades\Hypercore;

class ClearCommand extends Command
{
    protected $signature = 'unigale-tenants:clear';

    protected $description = 'Remove the tenants cache file';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): void
    {
        $this->files->delete(Hypercore::getCachePath());
        $this->components->info('Tenants cache cleared successfully.');
    }
}
