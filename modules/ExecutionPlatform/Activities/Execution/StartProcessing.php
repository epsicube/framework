<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Activities\Execution;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Exceptions\CanceledException;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use Illuminate\Support\Str;

class StartProcessing implements Activity
{
    public function handle(array $inputs = []): ?array
    {
        $execution_id = data_get($inputs, 'execution_id');

        // Atomically verify SCHEDULED and move to PROCESSING
        $updated = Execution::query()
            ->where('id', $execution_id)
            ->where('status', ExecutionStatus::SCHEDULED)
            ->update([
                'status'     => ExecutionStatus::PROCESSING,
                'started_at' => now()->toIso8601String(),
                '_run_id'    => Str::uuid(),
            ]);

        if (! $updated) {
            throw new CanceledException('Execution cannot be processed, already in progress or not SCHEDULED.');
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function identifier(): string
    {
        return '___START_PROCESSING___';
    }

    public function label(): string
    {
        return __('Start Processing');
    }
}
