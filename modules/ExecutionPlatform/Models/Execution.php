<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Models;

use Carbon\CarbonImmutable;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionType;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Execution model
 *
 * Represents a single workflow execution within the Execution Platform.
 *
 * @property-read int $id
 * @property string $target Workflow or Activity identifier depending on execution_type
 * @property array|null $input Arbitrary input payload used to start the execution
 * @property array|null $output Resulting output of the execution
 * @property string|null $last_error
 * @property string|null $note Optional note attached to the execution
 * @property ExecutionStatus $status Current status of the execution lifecycle
 * @property ExecutionType $execution_type Type of execution: WORKFLOW or ACTIVITY
 * @property string $_idempotency_key Generated UUID used to ensure idempotency across schedules
 * @property string|null $_run_id Generated UUID to identify this run
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property int|null $execution_time_ns
 * @property int|null $memory_used_bytes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Execution extends Model
{
    protected $table = 'executions';

    public $timestamps = true;

    protected static $unguarded = true; // <- empty $guarded prevent _{field} assignation

    protected $dateFormat = 'Y-m-d\TH:i:s.uP'; // <- force microseconds and timezone

    protected function casts(): array
    {
        return [
            'input'          => 'array',
            'output'         => 'array',
            'started_at'     => 'immutable_datetime',
            'completed_at'   => 'immutable_datetime',
            'status'         => ExecutionStatus::class,
            'execution_type' => ExecutionType::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Execution $model): void {
            if (empty($model->_idempotency_key)) {
                $model->_idempotency_key = (string) Str::uuid7();
            }
            if (empty($model->status)) {
                $model->status = ExecutionStatus::QUEUED;
            }

            // Force default input to be generated when creating to ensure determinism
            $model->input = match ($model->execution_type) {
                ExecutionType::ACTIVITY => Activities::inputSchema($model->target)->withDefaults($model->input ?? []),
                // ExecutionType::WORKFLOW => Workflows::get($model->target)->toValidator($model->input ?? [])->validated(), TODO
                default => $model->input
            };
        });
    }

    /**
     * Transitions the execution from QUEUED to SCHEDULED and triggers the workflow.
     *
     * @return $this
     *
     * @throws Throwable
     */
    public function run(bool $ignoreValidation = false): static
    {
        if ($this->isDirty()) {
            $this->save();
            //            $this->refresh(); // <- retrieve fresh instance with database defaults
        }

        static::getConnection()->transaction(function (): void {
            static::query()
                ->where('_idempotency_key', $this->_idempotency_key)
                ->lockForUpdate()
                ->get();

            $updated = static::query()
                ->whereKey($this->getKey())
                ->where('status', ExecutionStatus::QUEUED)
                ->whereNotExists(function (Builder $query): void {
                    $query->fromSub(static::query(), 'sub')
                        ->select(DB::raw(1))
                        ->where('sub._idempotency_key', $this->_idempotency_key)
                        ->whereIn('sub.status', [ExecutionStatus::SCHEDULED, ExecutionStatus::PROCESSING]);
                })
                ->update(['status' => ExecutionStatus::SCHEDULED]);

            if (! $updated) {
                throw new Exception('Execution cannot be scheduled, already in progress or not QUEUED.');
            }

            $this->fill(['status' => ExecutionStatus::SCHEDULED])->syncOriginal();
        });

        // Workflow handling
        if ($this->execution_type === ExecutionType::WORKFLOW) {
            try {
                Workflows::get($this->target)->run($this->id, $this->input ?? []);
            } catch (Throwable $e) {
                report($e);
            }

            return $this;
        }

        $activity = Activities::get($this->target);

        // Activity handling, sync run
        $this->fill(['status' => ExecutionStatus::PROCESSING, 'started_at' => now()])->save();

        $error = null;
        $memoryBefore = memory_get_usage(false);
        if (function_exists('memory_reset_peak_usage')) {
            memory_reset_peak_usage();
        }
        $start = hrtime(true);
        try {
            if (! $ignoreValidation) {
                // Ensure the input matches the schema before processing.
                // We don't use the schema's default values here because they were already
                // persisted at creation to guarantee determinism (even if schema defaults change).
                Activities::inputSchema($this->target)
                    ->toValidator($this->input ?? [], prepend: ['bail'])
                    ->validate();
            }
            $result = $activity->handle($this->input ?? []);
        } catch (Throwable $e) {
            $error = $e;
            $result = null;
        }
        $durationNs = hrtime(true) - $start;

        $peakDuringHandle = memory_get_peak_usage(false);
        $memoryUsedByActivity = max(0, $peakDuringHandle - $memoryBefore);

        $this->fill([
            'status'            => $error ? ExecutionStatus::FAILED : ExecutionStatus::COMPLETED,
            'output'            => $result,
            'last_error'        => $error?->getMessage(),
            'execution_time_ns' => $durationNs,
            'memory_used_bytes' => $memoryUsedByActivity,
            'completed_at'      => now(),
        ])->save();

        if ($error) {
            throw $error;
        }

        return $this;
    }

    public function cancel(?string $reason = null): static
    {
        throw new RuntimeException('Not implemented');
    }
}
