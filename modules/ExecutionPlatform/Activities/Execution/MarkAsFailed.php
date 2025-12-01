<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Activities\Execution;

use RuntimeException;
use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\ExecutionPlatform\Enum\ExecutionStatus;
use UniGaleModules\ExecutionPlatform\Models\Execution;

class MarkAsFailed implements Activity
{
    public function handle(array $inputs = []): ?array
    {
        $execution_id = data_get($inputs, 'execution_id');
        $error = data_get($inputs, 'error');

        // Atomically verify SCHEDULED and move to PROCESSING
        $updated = Execution::query()
            ->where('id', $execution_id)
            ->where('status', ExecutionStatus::PROCESSING)
            ->update([
                'status'       => ExecutionStatus::FAILED,
                'completed_at' => now()->toIso8601String(),
                'last_error'   => $error,
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
        return '___MARK_AS_FAILED___';
    }

    public function label(): string
    {
        return __('Mark as failed');
    }
}
