<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Activities\Execution;

use RuntimeException;
use UniGale\Foundation\Concerns\Makeable;
use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\ExecutionPlatform\Enum\ExecutionStatus;
use UniGaleModules\ExecutionPlatform\Models\Execution;

class MarkAsCanceled implements Activity
{
    use Makeable;

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
