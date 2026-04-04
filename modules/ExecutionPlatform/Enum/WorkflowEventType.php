<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Enum;

enum WorkflowEventType: string
{
    case WorkflowStarted = 'WorkflowStarted';
    case WorkflowCancelledRequested = 'WorkflowCancelledRequested';
    case SignalReceived = 'SignalReceived';
    case SignalConsumed = 'SignalConsumed';
    case ActivityScheduled = 'ActivityScheduled';
    case ActivityStarted = 'ActivityStarted';
    case ActivityCompleted = 'ActivityCompleted';
    case ActivityAttemptFailed = 'ActivityAttemptFailed';
    case ActivityFailed = 'ActivityFailed';
    case CancellationExceptionInjected = 'CancellationExceptionInjected';
    case SideEffectRecorded = 'SideEffectRecorded';
    case TimerStarted = 'TimerStarted';
    case TimerFired = 'TimerFired';
}
