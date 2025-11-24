<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Activities\Execution;

use RuntimeException;
use UniGale\Foundation\Concerns\Makeable;
use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\ExecutionPlatform\Enum\ExecutionStatus;
use UniGaleModules\ExecutionPlatform\Models\Execution;

class MarkAsCompleted implements Activity
{
    use Makeable;

    public function handle(array $inputs = []): ?array
    {
        $execution_id = data_get($inputs, 'execution_id');
        $output = data_get($inputs, 'output');

        // Atomically verify SCHEDULED and move to PROCESSING
        $updated = Execution::query()
            ->where('id', $execution_id)
            ->where('status', ExecutionStatus::PROCESSING)
            ->update([
                'status'       => ExecutionStatus::COMPLETED,
                'completed_at' => now()->toIso8601String(),
                'output'       => $output,
            ]);

        if (! $updated) {
            throw new RuntimeException('Failed to update status');
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function identifier(): string
    {
        return '___MARK_AS_COMPLETED___';
    }

    public function label(): string
    {
        return __('Mark as completed');
    }
}
