<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

use DateTimeImmutable;
use EpsicubeModules\ExecutionPlatform\Enum\WorkflowEventType;
use EpsicubeModules\ExecutionPlatform\Events\ActivityCompleted;
use EpsicubeModules\ExecutionPlatform\Events\ActivityFailed;
use EpsicubeModules\ExecutionPlatform\Events\ActivityScheduled;
use EpsicubeModules\ExecutionPlatform\Events\ActivityStarted;
use EpsicubeModules\ExecutionPlatform\Events\SignalReceived;
use EpsicubeModules\ExecutionPlatform\Events\WorkflowCancelled;
use EpsicubeModules\ExecutionPlatform\Events\WorkflowCompleted;
use EpsicubeModules\ExecutionPlatform\Events\WorkflowFailed;
use EpsicubeModules\ExecutionPlatform\Events\WorkflowStarted;
use EpsicubeModules\ExecutionPlatform\Jobs\ActivityJob;
use EpsicubeModules\ExecutionPlatform\Jobs\WorkflowJob;
use EpsicubeModules\ExecutionPlatform\Models\WorkflowEvent as WorkflowEventModel;
use EpsicubeModules\ExecutionPlatform\Models\WorkflowModel;
use EpsicubeModules\ExecutionPlatform\Models\WorkflowSignal;
use Fiber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WorkflowEngine
{
    private int $currentTick = 0;

    public function __construct(
        private $output = null
    ) {}

    public function setTick(int $tick): void
    {
        $this->currentTick = $tick;
    }

    public function getTick(): int
    {
        return $this->currentTick;
    }

    public function setOutput($output): void
    {
        $this->output = $output;
    }

    public function log(string $m): void
    {
        if ($this->output) {
            $this->output->writeln($m);
        } else {
            Log::info('[WorkflowEngine] '.strip_tags($m));
        }
    }

    public function startWorkflow(string $id, string $workflowClass, array $input = []): void
    {
        if (WorkflowModel::where('id', $id)->exists()) {
            throw new DuplicateWorkflowIdException($id);
        }

        $stub = new WorkflowStub($id, $workflowClass, $input);
        $stub->recordEvent(WorkflowEventType::WorkflowStarted, $input);
        $this->saveStub($stub);
    }

    public function resume(string $id, ?WorkflowStub $stub = null): void
    {
        $stub ??= $this->loadStub($id);
        if (! $stub || ! in_array($stub->status, [WorkflowStatus::RUNNING, WorkflowStatus::CANCELLING])) {
            return;
        }

        $this->log("\n<fg=cyan;options=bold>⚡ [RESUME]</> Workflow <fg=white>{$id}</> (".count($stub->history).' events)');

        $context = new WorkflowContext($this, $stub);
        $workflowClass = $stub->workflowClass;
        $stub->currentCallIndex = 0;

        if (! class_exists($workflowClass)) {
            $stub->status = WorkflowStatus::FAILED;
            $stub->result = "Workflow class {$workflowClass} not found.";
            $this->saveStub($stub);

            return;
        }

        $workflow = new $workflowClass($context);
        $fiber = new Fiber(fn () => $workflow->run($stub->input));

        try {
            $fiber->start();

            if ($stub->status === WorkflowStatus::CANCELLING && $fiber->isSuspended()) {
                if (! $stub->cancellationInjected) {
                    $reason = $stub->cancellationReason;
                    $this->log('   <fg=red;options=bold>┕ Injecting WorkflowCancelException (Reason: '.($reason ?? 'N/A').')</>');
                    $stub->recordEvent(WorkflowEventType::CancellationExceptionInjected, ['reason' => $reason], null, $stub->currentCallIndex++);
                    $this->saveStub($stub);
                    $fiber->throw(new WorkflowCancelException('Cancellation requested by user', $reason));
                }
            }

            if ($fiber->isTerminated()) {
                $stub->status = WorkflowStatus::COMPLETED;
                $stub->result = $fiber->getReturn();
                $this->log('   <fg=green;options=bold>✅ SUCCESS</> : '.json_encode($stub->result));
            }
        } catch (WorkflowCancelException $e) {
            $stub->status = WorkflowStatus::CANCELLED;
            $stub->result = $e->getMessage();
            $this->log("   <fg=yellow;options=bold>🚫 CANCELLED</> : {$e->getMessage()}");
        } catch (Throwable $e) {
            $stub->status = WorkflowStatus::FAILED;
            $stub->result = $e->getMessage();
            $this->log("   <fg=red;options=bold>❌ WORKFLOW FAILED</> : {$e->getMessage()}");
        }

        $this->saveStub($stub);
    }

    public function signal(string $id, string $name, mixed $data = null): void
    {
        $stub = $this->loadStub($id);
        if ($stub && in_array($stub->status, [WorkflowStatus::RUNNING, WorkflowStatus::CANCELLING])) {
            $this->log("   <fg=magenta>📩 [SIGNAL]</> Signal <fg=white>{$name}</> received for <fg=white>{$id}</>");

            WorkflowSignal::create([
                'workflow_id' => $id,
                'name'        => $name,
                'payload'     => $data,
            ]);

            $stub->recordEvent(WorkflowEventType::SignalReceived, $data, $name);
            $this->saveStub($stub);
        }
    }

    public function cancel(string $id, ?string $reason = null): void
    {
        $stub = $this->loadStub($id);
        if ($stub && $stub->status === WorkflowStatus::RUNNING) {
            $stub->status = WorkflowStatus::CANCELLING;
            $stub->recordEvent(WorkflowEventType::WorkflowCancelledRequested, ['reason' => $reason]);
            $this->saveStub($stub);
        }
    }

    public function scheduleActivityTask(WorkflowStub $stub, string $class, array $input, int $index, array $retryOptions = []): void
    {
        $this->log("   <fg=blue;options=bold>⚙️ [ACTIVITY-SCHED]</> {$class} (Index #{$index})");

        $stub->recordEvent(WorkflowEventType::ActivityScheduled, [
            'input'        => $input,
            'retryOptions' => $retryOptions,
        ], $class, $index);

        $this->saveStub($stub);
    }

    public function recordActivityStarted(string $workflowId, string $class, int $index, int $attempt): void
    {
        $this->log("      <fg=gray>┕ Activity started (Attempt #{$attempt})</>");
        $this->pushEvent($workflowId, WorkflowEventType::ActivityStarted, ['attempt' => $attempt], $class, $index);
    }

    public function recordActivityCompleted(string $workflowId, string $class, int $index, mixed $result): void
    {
        $this->log("      <fg=green>┕ Success !</> (Activity {$class} #{$index})");
        $this->pushEvent($workflowId, WorkflowEventType::ActivityCompleted, $result, $class, $index);
    }

    public function recordActivityAttemptFailed(string $workflowId, string $class, int $index, int $attempt, mixed $error): void
    {
        $message = ($error instanceof Throwable) ? $error->getMessage() : (string) $error;
        $this->log("      <fg=yellow>┕ Failure (Attempt #{$attempt})</> : {$message}. Retry scheduled...");

        $this->pushEvent($workflowId, WorkflowEventType::ActivityAttemptFailed, [
            'error'   => $message,
            'attempt' => $attempt,
        ], $class, $index);
    }

    public function recordActivityFailed(string $workflowId, string $class, int $index, int $attempt, mixed $error): void
    {
        $message = ($error instanceof Throwable) ? $error->getMessage() : (string) $error;
        $this->log("      <fg=red>┕ FATAL FAILURE</> (Attempt #{$attempt}) : {$message}. Activity stopped.");

        $this->pushEvent($workflowId, WorkflowEventType::ActivityFailed, [
            'error'   => $message,
            'attempt' => $attempt,
        ], $class, $index);
    }

    protected function pushEvent(string $workflowId, WorkflowEventType $type, mixed $data = null, ?string $name = null, ?int $index = null): void
    {
        $event = new WorkflowEvent($type, $data, $name, $index, $this->currentTick);

        DB::transaction(function () use ($workflowId, $event) {
            WorkflowEventModel::create([
                'workflow_id' => $workflowId,
                'type'        => $event->type,
                'payload'     => serialize($event),
                'target'      => $event->name,
                'index'       => $event->callIndex,
                'tick'        => $event->tick,
                'created_at'  => now(),
            ]);

            $model = WorkflowModel::find($workflowId);
            if (! $model) {
                return;
            }

            switch ($event->type) {
                case WorkflowEventType::ActivityStarted:
                    ActivityStarted::dispatch($model, $event->name, $event->callIndex, $event->data['attempt'] ?? 1);
                    break;
                case WorkflowEventType::ActivityCompleted:
                    ActivityCompleted::dispatch($model, $event->name, $event->callIndex, $event->data);
                    DB::afterCommit(fn () => $this->dispatchWorkflow($workflowId));
                    break;
                case WorkflowEventType::ActivityFailed:
                    ActivityFailed::dispatch($model, $event->name, $event->callIndex, $event->data['error'] ?? '');
                    DB::afterCommit(fn () => $this->dispatchWorkflow($workflowId));
                    break;
                case WorkflowEventType::ActivityAttemptFailed:
                    DB::afterCommit(fn () => $this->dispatchWorkflow($workflowId));
                    break;
            }
        });
    }

    public function saveStub(WorkflowStub $stub): WorkflowModel
    {
        return DB::transaction(function () use ($stub) {
            $model = WorkflowModel::updateOrCreate(
                ['id' => $stub->id],
                [
                    'workflow_class' => $stub->workflowClass,
                    'input'          => $stub->input,
                    'status'         => $stub->status,
                    'result'         => $stub->result,
                ]
            );

            $needsWorkflowDispatch = false;
            $needsActivityDispatch = [];

            if (! empty($stub->newEvents)) {
                $eventsToInsert = [];
                foreach ($stub->newEvents as $event) {
                    $eventsToInsert[] = [
                        'workflow_id' => $stub->id,
                        'type'        => $event->type,
                        'payload'     => serialize($event),
                        'target'      => $event->name,
                        'index'       => $event->callIndex,
                        'tick'        => $event->tick,
                        'created_at'  => now(),
                    ];

                    switch ($event->type) {
                        case WorkflowEventType::WorkflowStarted:
                            WorkflowStarted::dispatch($model);
                            $needsWorkflowDispatch = true;
                            break;
                        case WorkflowEventType::SignalReceived:
                            SignalReceived::dispatch($model, $event->name, $event->data);
                            $needsWorkflowDispatch = true;
                            break;
                        case WorkflowEventType::ActivityScheduled:
                            ActivityScheduled::dispatch($model, $event->name, $event->data['input'] ?? [], $event->callIndex);
                            $needsActivityDispatch[] = [
                                'class'        => $event->name,
                                'input'        => $event->data['input'] ?? [],
                                'index'        => $event->callIndex,
                                'retryOptions' => $event->data['retryOptions'] ?? [],
                            ];
                            break;
                        case WorkflowEventType::ActivityStarted:
                            ActivityStarted::dispatch($model, $event->name, $event->callIndex, $event->data['attempt'] ?? 1);
                            break;
                        case WorkflowEventType::ActivityCompleted:
                            ActivityCompleted::dispatch($model, $event->name, $event->callIndex, $event->data);
                            $needsWorkflowDispatch = true;
                            break;
                        case WorkflowEventType::ActivityFailed:
                            ActivityFailed::dispatch($model, $event->name, $event->callIndex, $event->data['error'] ?? '');
                            $needsWorkflowDispatch = true;
                            break;
                        case WorkflowEventType::ActivityAttemptFailed:
                            $needsWorkflowDispatch = true;
                            break;
                    }
                }
                WorkflowEventModel::insert($eventsToInsert);
                $stub->newEvents = [];
            }

            if ($model->wasRecentlyCreated || $model->wasChanged('status')) {
                if ($stub->status === WorkflowStatus::COMPLETED) {
                    WorkflowCompleted::dispatch($model, $stub->result);
                } elseif ($stub->status === WorkflowStatus::FAILED) {
                    WorkflowFailed::dispatch($model, (string) $stub->result);
                } elseif ($stub->status === WorkflowStatus::CANCELLED) {
                    WorkflowCancelled::dispatch($model);
                }
            }

            DB::afterCommit(function () use ($stub, $needsWorkflowDispatch, $needsActivityDispatch) {
                if ($needsWorkflowDispatch) {
                    $this->dispatchWorkflow($stub->id);
                }
                foreach ($needsActivityDispatch as $act) {
                    ActivityJob::dispatch($stub->id, $act['class'], $act['input'], $act['index'], $act['retryOptions']);
                }
            });

            return $model;
        });
    }

    public function resumeWithLock(string $id): void
    {
        DB::transaction(function () use ($id) {
            $stub = $this->loadStub($id, true);
            if ($stub && in_array($stub->status, [WorkflowStatus::RUNNING, WorkflowStatus::CANCELLING])) {
                $this->resume($id, $stub);
            }
        });
    }

    public function canExecuteActivity(string $workflowId, int $index): bool
    {
        return DB::transaction(function () use ($workflowId, $index) {
            $model = WorkflowModel::where('id', $workflowId)
                ->whereIn('status', [WorkflowStatus::RUNNING, WorkflowStatus::CANCELLING])
                ->lockForUpdate()
                ->first();

            if (! $model) {
                return false;
            }

            return ! WorkflowEventModel::where('workflow_id', $workflowId)
                ->where('index', $index)
                ->whereIn('type', [WorkflowEventType::ActivityCompleted, WorkflowEventType::ActivityFailed])
                ->exists();
        });
    }

    public function loadStub(string $id, bool $lock = false): ?WorkflowStub
    {
        $query = WorkflowModel::with('events');
        if ($lock) {
            $query->lockForUpdate();
        }

        $model = $query->find($id);
        if (! $model) {
            return null;
        }

        $stub = new WorkflowStub($model->id, $model->workflow_class, $model->input);
        $stub->status = $model->status;
        $stub->result = $model->result;

        $stub->history = $model->events->map(function ($e) {
            if (is_string($e->payload) && str_starts_with($e->payload, 'O:')) {
                return unserialize($e->payload);
            }

            return new WorkflowEvent(
                $e->type,
                $e->payload,
                $e->target,
                $e->index,
                $e->tick,
                $e->created_at ? new DateTimeImmutable($e->created_at->toDateTimeString()) : null
            );
        })->toArray();

        $stub->indexHistory();

        return $stub;
    }

    public function dispatchWorkflow(string $id, int $delay = 0): void
    {
        if ($delay > 0) {
            WorkflowJob::dispatch($id)->delay($delay);
        } else {
            WorkflowJob::dispatch($id);
        }
    }

    public function getAllStubIds(): array
    {
        return WorkflowModel::pluck('id')->toArray();
    }

    public function cleanup(): void
    {
        WorkflowModel::truncate();
        WorkflowEventModel::truncate();
    }
}
