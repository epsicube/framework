<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

use EpsicubeModules\ExecutionPlatform\Enum\WorkflowEventType;
use Exception;
use Fiber;
use Throwable;

/**
 * @internal
 */
class WorkflowContext
{
    public function __construct(
        private WorkflowEngine $engine,
        private WorkflowStub $stub
    ) {}

    /**
     * @throws WorkflowCancelException
     * @throws Throwable
     */
    public function executeActivity(string $activityClass, array $input = [], ?RetryOptions $retryOptions = null): mixed
    {
        $index = $this->stub->currentCallIndex++;
        $event = $this->pullReplay();

        if ($event) {
            if ($event->type === WorkflowEventType::ActivityCompleted) {
                return $this->replayActivity($activityClass, $event);
            }

            match ($event->type) {
                WorkflowEventType::ActivityFailed => $this->handleActivityFailure($activityClass, $event),
                WorkflowEventType::ActivityScheduled, WorkflowEventType::ActivityStarted, WorkflowEventType::ActivityAttemptFailed => $this->suspendForActivity($activityClass),
                default => throw new WorkflowHistoryMismatchException("ExecuteActivity ({$activityClass})", $event->type, $index),
            };
        }

        // 2. ASYNCHRONOUS SCHEDULING
        $this->engine->log("   <fg=yellow;options=bold>⚙️ [SCHEDULE]</> Activity <fg=white>{$activityClass}</> (Index: {$index})");
        $this->engine->scheduleActivityTask($this->stub, $activityClass, $input, $index, $retryOptions?->toArray() ?? []);

        Fiber::suspend();

        return null; // Never reached
    }

    private function replayActivity(string $activityClass, WorkflowEvent $event): mixed
    {
        $this->engine->log("      <fg=gray>[REPLAY]</> Activity <fg=gray>{$activityClass}</> restored.");

        return $event->data;
    }

    private function handleActivityFailure(string $activityClass, WorkflowEvent $event): never
    {
        $this->engine->log("      <fg=gray>[REPLAY]</> Activity <fg=gray>{$activityClass}</> restored with FAILURE.");
        $error = $event->data['error'] ?? 'Unknown error';

        if ($error instanceof Throwable) {
            throw $error;
        }

        throw new Exception((string) $error);
    }

    private function suspendForActivity(string $activityClass)
    {
        $this->engine->log("      ⚓ [STAY] Activity <fg=white>{$activityClass}</> running (or retrying)...");
        Fiber::suspend();
    }

    /**
     * @throws WorkflowCancelException
     * @throws Throwable
     */
    public function waitForSignal(string $name): mixed
    {
        $index = $this->stub->currentCallIndex++;
        $event = $this->pullReplay();

        if ($event) {
            return match ($event->type) {
                WorkflowEventType::SignalConsumed => $this->replaySignal($name, $event),
                default                           => throw new WorkflowHistoryMismatchException("WaitForSignal ({$name})", $event->type, $index),
            };
        }

        // 2. CONSUMPTION : Look in received signals queue
        $rank = $this->stub->consumedCounts[$name] ?? 0;
        $signalEvent = $this->stub->signalQueues[$name][$rank] ?? null;

        if ($signalEvent) {
            $this->engine->log("   <fg=magenta;options=bold>📥 [CONSUME]</> Signal <fg=white>{$name}</> consumed.");
            $this->stub->recordEvent(WorkflowEventType::SignalConsumed, $signalEvent->data, $name, $index);

            return $signalEvent->data;
        }

        // 3. SUSPENSION
        $this->engine->log("   <fg=magenta;options=bold>⏳ [WAIT-SIGNAL]</> Waiting for signal <fg=white>{$name}</>");
        Fiber::suspend();

        return null;
    }

    private function replaySignal(string $name, WorkflowEvent $event): mixed
    {
        $this->engine->log("      <fg=gray>[REPLAY]</> Signal <fg=gray>{$name}</> restored.");

        return $event->data;
    }

    /**
     * @throws WorkflowCancelException
     */
    private function pullReplay(): ?WorkflowEvent
    {
        $event = array_shift($this->stub->historyIndex);

        if ($event && $event->type === WorkflowEventType::CancellationExceptionInjected) {
            $reason = $event->data['reason'] ?? 'Replayed reason';
            $this->engine->log('      <fg=red;options=bold>[REPLAY] Injecting WorkflowCancelException - Reason: '.$reason.'</>');

            throw new WorkflowCancelException('Cancellation replayed from history', $reason);
        }

        return $event;
    }

    /**
     * @throws WorkflowCancelException
     */
    public function sideEffect(callable $fn): mixed
    {
        $index = $this->stub->currentCallIndex++;
        $event = $this->pullReplay();

        if ($event) {
            return match ($event->type) {
                WorkflowEventType::SideEffectRecorded => $this->replaySideEffect($index, $event),
                default                               => throw new WorkflowHistoryMismatchException('SideEffect', $event->type, $index),
            };
        }

        $result = $fn();
        $this->engine->log('   <fg=cyan;options=bold>🎲 [SIDE-EFFECT]</> New side-effect result recorded.');
        $this->stub->recordEvent(WorkflowEventType::SideEffectRecorded, $result, null, $index);

        return $result;
    }

    private function replaySideEffect(int $index, WorkflowEvent $event): mixed
    {
        $this->engine->log("      <fg=gray>[REPLAY]</> SideEffect <fg=gray>#{$index}</> restored.");

        return $event->data;
    }

    /**
     * @throws WorkflowCancelException
     * @throws Throwable
     */
    public function timer(int $seconds): void
    {
        $index = $this->stub->currentCallIndex++;
        $event = $this->pullReplay();

        if ($event) {
            match ($event->type) {
                WorkflowEventType::TimerFired   => $this->engine->log("      <fg=gray>[REPLAY]</> Timer <fg=gray>#{$index}</> ({$seconds}s) already expired."),
                WorkflowEventType::TimerStarted => $this->suspendForTimer($seconds),
                default                         => throw new WorkflowHistoryMismatchException("Timer ({$seconds}s)", $event->type, $index),
            };

            return;
        }

        // 2. SUSPENSION AND SCHEDULING
        $this->engine->log("   <fg=blue;options=bold>⏱️ [TIMER]</> Waiting for <fg=white>{$seconds}</> seconds...");
        $this->stub->recordEvent(WorkflowEventType::TimerStarted, ['seconds' => $seconds], null, $index);

        // Delegate delayed dispatch to Engine
        $this->engine->dispatchWorkflow($this->stub->id, $seconds);

        Fiber::suspend();

        // 3. WAKEUP : Mark as fired for next replay
        $this->stub->recordEvent(WorkflowEventType::TimerFired, null, null, $index);
    }

    private function suspendForTimer(int $seconds): void
    {
        $this->engine->log("      ⚓ [STAY] Timer <fg=white>#{$seconds}s</> still waiting...");
        Fiber::suspend();
    }
}
