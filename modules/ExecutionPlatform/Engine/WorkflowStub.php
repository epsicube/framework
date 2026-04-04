<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

use EpsicubeModules\ExecutionPlatform\Enum\WorkflowEventType;
use EpsicubeModules\ExecutionPlatform\Models\WorkflowModel;
use Illuminate\Support\Str;

/**
 * @internal
 */
class WorkflowStub
{
    private static ?WorkflowEngine $engine = null;

    public string $id;

    public string $workflowClass;

    public array $input;

    public string $status = WorkflowStatus::RUNNING;

    /** @var WorkflowEvent[] */
    public array $history = [];

    /** @var WorkflowEvent[] Queue for lazy-saving */
    public array $newEvents = [];

    /** @var WorkflowEvent[] Replay queue (ordered list of step results) */
    public array $historyIndex = [];

    /** @var array<string, WorkflowEvent[]> Received signal queues (Name -> Events[]) */
    public array $signalQueues = [];

    /** @var array<string, int> Consumed signal counters (Name -> Count) */
    public array $consumedCounts = [];

    public mixed $result = null;

    public bool $cancellationInjected = false;

    public ?string $cancellationReason = null;

    /** @var array<int, array> Trace of activities [index => [status, class, result, error, attempts]] */
    public array $activities = [];

    // Ephemeral state (not persisted) used during replay
    public int $currentCallIndex = 0;

    public function __construct(string $id, string $workflowClass, array $input = [])
    {
        $this->id = $id;
        $this->workflowClass = $workflowClass;
        $this->input = $input;
    }

    public function indexHistory(): void
    {
        $this->historyIndex = [];
        $this->signalQueues = [];
        $this->consumedCounts = [];
        $this->activities = [];

        $temp = [];
        foreach (($this->history ?? []) as $event) {
            if (! ($event instanceof WorkflowEvent)) {
                continue;
            }

            if ($event->callIndex !== null) {
                // Keep only the latest event for each call index (e.g. ActivityCompleted replaces ActivityScheduled)
                $temp[$event->callIndex] = $event;

                // Trace activity states
                switch ($event->type) {
                    case WorkflowEventType::ActivityScheduled:
                        $this->activities[$event->callIndex] = [
                            'class'    => $event->name,
                            'status'   => 'scheduled',
                            'attempts' => 1,
                        ];
                        break;
                    case WorkflowEventType::ActivityStarted:
                        if (isset($this->activities[$event->callIndex])) {
                            $this->activities[$event->callIndex]['status'] = 'running';
                            $this->activities[$event->callIndex]['attempts'] = $event->data['attempt'] ?? $this->activities[$event->callIndex]['attempts'];
                        }
                        break;
                    case WorkflowEventType::ActivityAttemptFailed:
                        if (isset($this->activities[$event->callIndex])) {
                            $this->activities[$event->callIndex]['status'] = 'retrying';
                            $this->activities[$event->callIndex]['attempts'] = $event->data['attempt'] ?? ($this->activities[$event->callIndex]['attempts'] + 1);
                        }
                        break;
                    case WorkflowEventType::ActivityCompleted:
                        if (isset($this->activities[$event->callIndex])) {
                            $this->activities[$event->callIndex]['status'] = 'completed';
                            $this->activities[$event->callIndex]['result'] = $event->data;
                        }
                        break;
                    case WorkflowEventType::ActivityFailed:
                        if (isset($this->activities[$event->callIndex])) {
                            $this->activities[$event->callIndex]['status'] = 'failed';
                            $this->activities[$event->callIndex]['error'] = $event->data['error'] ?? 'Unknown error';
                        }
                        break;
                }
            }

            if ($event->type === WorkflowEventType::SignalReceived && $event->name !== null) {
                $this->signalQueues[$event->name][] = $event;
            }

            if ($event->type === WorkflowEventType::SignalConsumed && $event->name !== null) {
                $this->consumedCounts[$event->name] = ($this->consumedCounts[$event->name] ?? 0) + 1;
            }

            if ($event->type === WorkflowEventType::CancellationExceptionInjected) {
                $this->cancellationInjected = true;
                $this->cancellationReason = $event->data['reason'] ?? null;
            }

            if ($event->type === WorkflowEventType::WorkflowCancelledRequested) {
                $this->cancellationReason = $event->data['reason'] ?? null;
            }
        }

        ksort($temp);
        $this->historyIndex = array_values($temp);
    }

    private static function getEngine(): WorkflowEngine
    {
        if (self::$engine === null) {
            self::$engine = app(WorkflowEngine::class);
        }

        return self::$engine;
    }

    public static function load(string $id): ?self
    {
        return self::getEngine()->loadStub($id);
    }

    public static function start(string $workflowClass, array $input = [], ?string $id = null): self
    {
        $id ??= Str::uuid()->toString();
        try {
            self::getEngine()->startWorkflow($id, $workflowClass, $input);
        } catch (DuplicateWorkflowIdException $e) {
            // Workflow already exists, continue
        }

        return self::load($id);
    }

    public function cancel(?string $reason = null): void
    {
        self::getEngine()->cancel($this->id, $reason);
    }

    public function signal(string $name, mixed $data = null): void
    {
        self::getEngine()->signal($this->id, $name, $data);
    }

    public function processing(): bool
    {
        $status = $this->status();

        return in_array($status, [WorkflowStatus::RUNNING, WorkflowStatus::CANCELLING]);
    }

    public function status(): string
    {
        $fresh = WorkflowModel::find($this->id, ['status', 'result']);
        if ($fresh) {
            $this->status = $fresh->status;
            $this->result = $fresh->result;
        }

        return $this->status;
    }

    public function output(): mixed
    {
        $this->status(); // Refresh

        return $this->result;
    }

    public function recordEvent(WorkflowEventType $type, mixed $data = null, ?string $name = null, ?int $callIndex = null): WorkflowEvent
    {
        $event = new WorkflowEvent($type, $data, $name, $callIndex, self::getEngine()->getTick());
        $this->history[] = $event;
        $this->newEvents[] = $event;

        if ($type === WorkflowEventType::SignalReceived && $name !== null) {
            $this->signalQueues[$name][] = $event;
        }

        if ($type === WorkflowEventType::SignalConsumed && $name !== null) {
            $this->consumedCounts[$name] = ($this->consumedCounts[$name] ?? 0) + 1;
        }

        if ($type === WorkflowEventType::CancellationExceptionInjected) {
            $this->cancellationInjected = true;
        }

        return $event;
    }
}
