<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;

class TerminateCommand extends Command
{
    protected $signature = 'epsicube:terminate';

    protected $aliases = ['ec:t'];

    protected $description = 'Broadcast a termination signal to the worker.';

    public function __construct(protected Cache $cache)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->cache->forever('epsicube:work:terminate', now()->timestamp);
        $this->info('Termination signal broadcasted.');
    }
}
