<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Activities\Execution;

use Illuminate\Support\Str;
use UniGale\Foundation\Concerns\Makeable;
use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\ExecutionPlatform\Enum\ExecutionStatus;
use UniGaleModules\ExecutionPlatform\Exceptions\CanceledException;
use UniGaleModules\ExecutionPlatform\Models\Execution;

class StartProcessing implements Activity
{
    use Makeable;

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
