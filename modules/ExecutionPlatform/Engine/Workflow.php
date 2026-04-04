<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

use Throwable;

abstract class Workflow
{
    public function __construct(
        protected WorkflowContext $context
    ) {}

    abstract public function run(array $input): mixed;

    /**
     * @throws WorkflowCancelException
     * @throws Throwable
     */
    protected function executeActivity(string $class, array $input = [], ?RetryOptions $retryOptions = null): mixed
    {
        return $this->context->executeActivity($class, $input, $retryOptions);
    }

    /**
     * @throws WorkflowCancelException
     * @throws Throwable
     */
    protected function waitForSignal(string $name): mixed
    {
        return $this->context->waitForSignal($name);
    }

    /**
     * @throws WorkflowCancelException
     */
    protected function sideEffect(callable $fn): mixed
    {
        return $this->context->sideEffect($fn);
    }

    /**
     * @throws WorkflowCancelException
     * @throws Throwable
     */
    protected function timer(int $seconds): void
    {
        $this->context->timer($seconds);
    }
}
