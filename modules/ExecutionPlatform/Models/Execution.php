<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Models;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use UniGaleModules\ExecutionPlatform\Enum\ExecutionStatus;
use UniGaleModules\ExecutionPlatform\Facades\Workflows;

/**
 * Execution model
 *
 * Represents a single workflow execution within the Execution Platform.
 *
 * @property-read int $id
 * @property string $workflow_type Identifier of the workflow definition that was executed
 * @property array|null $input Arbitrary input payload used to start the execution
 * @property array|null $output Resulting output of the execution
 * @property string|null $last_error
 * @property string|null $note Optional note attached to the execution
 * @property ExecutionStatus $status Current status of the execution lifecycle
 * @property string|null $_workflow_id Generated UUID to identify this execution for a specific input
 * @property string|null $_run_id Generated UUID to identify this run
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Execution extends Model
{
    protected $table = 'executions';

    public $timestamps = true;

    protected static $unguarded = true; // <- empty $guarded prevent _{field} assignation

    protected function casts(): array
    {
        return [
            'input'        => 'array',
            'output'       => 'array',
            'started_at'   => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'status'       => ExecutionStatus::class,
        ];
    }

    /**
     * Transitions the execution from QUEUED to SCHEDULED and triggers the workflow.
     *
     * @return $this
     *
     * @throws Throwable
     */
    public function run(): static
    {
        if ($this->isDirty()) {
            $this->save();
            $this->refresh();
        }

        $workflow = Workflows::get($this->workflow_type);
        $this->_workflow_id = Str::uuid7(); // TODO WORKFLOW DECISION
        static::getConnection()->transaction(function () {
            // Lock all rows with the same workflow_id to prevent race conditions
            static::query()
                ->where('_workflow_id', $this->_workflow_id)
                ->lockForUpdate()
                ->get(); // Row-level locks are acquired here

            // Atomic update: move QUEUED → SCHEDULED and prevent race conditions
            $updated = static::query()
                ->whereKey($this->getKey())
                ->where('status', ExecutionStatus::QUEUED)
                ->whereNotExists(function ($query) {
                    $query->from(DB::raw('(select * from `executions`) as sub'))
                        ->select(DB::raw(1))
                        ->where('sub._workflow_id', $this->_workflow_id)
                        ->whereIn('sub.status', [ExecutionStatus::SCHEDULED, ExecutionStatus::PROCESSING]);
                })
                ->update(['status' => ExecutionStatus::SCHEDULED]);

            if (! $updated) {
                throw new Exception('Workflow cannot be scheduled, already in progress or not QUEUED.');
            }

            // Keep the local model instance up-to-date
            $this->fill(['status' => ExecutionStatus::SCHEDULED])->syncOriginal();
        });

        try {
            $workflow->run($this->id, $this->input);
        } catch (Throwable $e) {
            report($e);
        }

        return $this;
    }

    public function cancel(?string $reason = null): static
    {
        throw new RuntimeException('Not implemented');
    }
}
