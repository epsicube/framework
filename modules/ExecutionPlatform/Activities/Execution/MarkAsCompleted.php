<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Activities\Execution;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use RuntimeException;

class MarkAsCompleted implements Activity
{
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
                'completed_at' => now(),
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
