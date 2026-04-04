<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

class WorkflowStatus
{
    public const RUNNING = 'RUNNING';

    public const CANCELLING = 'CANCELLING';

    public const COMPLETED = 'COMPLETED';

    public const FAILED = 'FAILED';

    public const CANCELLED = 'CANCELLED';

    public const TERMINATED = 'TERMINATED';

    public const TIMED_OUT = 'TIMED_OUT';

    public const CONTINUED_AS_NEW = 'CONTINUED_AS_NEW';
}
