<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;

class QueueHeartbeatCommand extends Command
{
    protected $signature = 'epsicube:queue-heartbeat {--max-age=90 : Seconds before the heartbeat is considered stale}';

    protected $aliases = ['ec:qh'];

    protected $description = 'Exit with a non-zero code when the queue worker has not looped recently';

    public function __construct(protected Cache $cache)
    {
        parent::__construct();
    }

    public static function cacheKey(): string
    {
        return 'queue-heartbeat:'.gethostname();
    }

    public function handle(): int
    {
        $heartbeat = $this->cache->get(static::cacheKey());

        if ($heartbeat === null || (time() - (int) $heartbeat) > (int) $this->option('max-age')) {
            $this->components->error('Queue worker heartbeat is stale.');

            return self::FAILURE;
        }

        $this->components->info('Queue worker heartbeat is fresh.');

        return self::SUCCESS;
    }
}
