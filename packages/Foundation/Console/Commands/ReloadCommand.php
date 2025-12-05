<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;

class ReloadCommand extends Command
{
    protected $signature = 'epsicube:reload';

    protected $description = 'Reload the epsicube:work supervisor';

    public function __construct(protected Cache $cache)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->cache->forever('epsicube:work:reload', now()->timestamp);
        $this->info('Broadcasting reload signal.');
    }
}
