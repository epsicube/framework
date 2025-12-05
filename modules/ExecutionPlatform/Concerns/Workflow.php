<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Concerns;

use EpsicubeModules\ExecutionPlatform\Activities\Execution\MarkAsCanceled;
use EpsicubeModules\ExecutionPlatform\Activities\Execution\MarkAsCompleted;
use EpsicubeModules\ExecutionPlatform\Activities\Execution\MarkAsFailed;
use EpsicubeModules\ExecutionPlatform\Activities\Execution\StartProcessing;
use EpsicubeModules\ExecutionPlatform\Contracts\Workflow as WorkflowContract;
use EpsicubeModules\ExecutionPlatform\Exceptions\CanceledException;
use Throwable;

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
