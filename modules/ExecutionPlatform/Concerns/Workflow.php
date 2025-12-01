<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Concerns;

use Throwable;
use UniGaleModules\ExecutionPlatform\Activities\Execution\MarkAsCanceled;
use UniGaleModules\ExecutionPlatform\Activities\Execution\MarkAsCompleted;
use UniGaleModules\ExecutionPlatform\Activities\Execution\MarkAsFailed;
use UniGaleModules\ExecutionPlatform\Activities\Execution\StartProcessing;
use UniGaleModules\ExecutionPlatform\Contracts\Workflow as WorkflowContract;
use UniGaleModules\ExecutionPlatform\Exceptions\CanceledException;

abstract class Workflow implements WorkflowContract
{
    public function run(int $execution_id, array $input = []): mixed
    {
        try {
            (new StartProcessing)->handle([
                'execution_id' => $execution_id,
            ]);

            $res = $this->handle($input);

            (new MarkAsCompleted)->handle([
                'execution_id' => $execution_id,
                'output'       => $res,
            ]);

            return $res;
        } catch (CanceledException $e) {
            report($e);
            (new MarkAsCanceled)->handle([
                'execution_id' => $execution_id,
                'reason'       => $e->getReason(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            report($e);
            (new MarkAsFailed)->handle([
                'execution_id' => $execution_id,
                'error'        => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    abstract public function handle(array $input = []): mixed;
}
