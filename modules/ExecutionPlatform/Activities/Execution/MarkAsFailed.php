<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Activities\Execution;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use RuntimeException;

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
                'completed_at' => now(),
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
