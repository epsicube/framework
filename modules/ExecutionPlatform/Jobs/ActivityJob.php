<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Jobs;

use EpsicubeModules\ExecutionPlatform\Engine\WorkflowEngine;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ActivityJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    public function __construct(
        protected string $workflowId,
        protected string $activityClass,
        protected array $input,
        protected int $index,
        protected array $retryOptions,
    ) {
        $this->tries = $this->retryOptions['maxAttempts'] ?? 1;
        $this->timeout = $this->retryOptions['timeout'] ?? 60;
    }

    public function uniqueId(): string
    {
        return $this->workflowId.'-'.$this->activityClass.'-'.$this->index;
    }

    public function backoff(): array
    {
        $initial = $this->retryOptions['initialInterval'] ?? 1;
        $coeff = $this->retryOptions['backoffCoefficient'] ?? 2.0;
        $max = $this->retryOptions['maximumInterval'] ?? 100;

        $backoffs = [];
        $current = $initial;

        for ($i = 0; $i < $this->tries; $i++) {
            $backoffs[] = (int) min($current, $max);
            $current *= $coeff;
        }

        return $backoffs;
    }

    public function handle(): void
    {
        $engine = app(WorkflowEngine::class);

        // 1. Check state via Engine
        if (! $engine->canExecuteActivity($this->workflowId, $this->index)) {
            return;
        }

        // 2. Execute activity
        try {
            if (! class_exists($this->activityClass)) {
                throw new Exception("Class {$this->activityClass} not found");
            }

            $engine->recordActivityStarted($this->workflowId, $this->activityClass, $this->index, $this->attempts());

            $activity = new $this->activityClass;
            $result = $activity->run($this->input);

            // 3. Success : Record and wake up workflow via Engine
            $engine->recordActivityCompleted($this->workflowId, $this->activityClass, $this->index, $result);

        } catch (Throwable $e) {
            // 4. Handle failure via Laravel mechanism
            $isRetryable = ! in_array(get_class($e), $this->retryOptions['nonRetryableExceptions'] ?? []);

            // Record attempt via Engine
            $engine->recordActivityAttemptFailed($this->workflowId, $this->activityClass, $this->index, $this->attempts(), $e);

            if (! $isRetryable) {
                $this->fail($e); // Stop retries immediately
            }

            // Let Laravel handle retry (backoff, attempts...)
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $engine = app(WorkflowEngine::class);

        // Final activity failure
        $engine->recordActivityFailed($this->workflowId, $this->activityClass, $this->index, $this->attempts(), $e);
    }
}
