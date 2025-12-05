<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Activities\Execution;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use RuntimeException;

class MarkAsCanceled implements Activity
{
    public function handle(array $inputs = []): ?array
    {
        $execution_id = data_get($inputs, 'execution_id');
        $reason = data_get($inputs, 'reason');

        // Atomically verify SCHEDULED and move to PROCESSING
        $updated = Execution::query()
            ->where('id', $execution_id)
            ->whereIn('status', [ExecutionStatus::SCHEDULED, ExecutionStatus::PROCESSING])
            ->update([
                'status'       => ExecutionStatus::CANCELED,
                'completed_at' => now()->toIso8601String(),
                'last_error'   => $reason,
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
        return '___MARK_AS_CANCELED___';
    }

    public function label(): string
    {
        return __('Mark as canceled');
    }
}
