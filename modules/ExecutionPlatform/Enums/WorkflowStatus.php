<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

enum WorkflowStatus: string
{
    case RUNNING = 'RUNNING';

    case CANCELLING = 'CANCELLING';

    case COMPLETED = 'COMPLETED';

    case FAILED = 'FAILED';

    case CANCELLED = 'CANCELLED';

    case TERMINATED = 'TERMINATED';

    case TIMED_OUT = 'TIMED_OUT';

    case CONTINUED_AS_NEW = 'CONTINUED_AS_NEW';

    public function getColor(): string
    {
        return match ($this) {
            self::COMPLETED => 'success',
            self::FAILED    => 'danger',
            self::CANCELLED, self::TERMINATED,self::TIMED_OUT => 'warning',
            self::RUNNING, self::CANCELLING => 'info',
            self::CONTINUED_AS_NEW => 'primary',
        };
    }
}
