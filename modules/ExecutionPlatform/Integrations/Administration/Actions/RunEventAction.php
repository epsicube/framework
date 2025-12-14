<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions;

use EpsicubeModules\ExecutionPlatform\Enum\EventStatus;
use EpsicubeModules\ExecutionPlatform\Enum\Icons;
use EpsicubeModules\ExecutionPlatform\Models\ExecutionEvent;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;

class RunEventAction extends Action
{
    protected ?array $activityResult = null;

    protected ?string $activityError = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hidden(fn (ExecutionEvent $record) => $record->status !== EventStatus::QUEUED);

        $this->label(__('Run'));
        $this->icon(Icons::RUN);
        $this->color('success');
        $this->modalWidth(Width::Large);

        $this->successNotificationTitle(__('Event started successfully'));
        $this->failureNotificationTitle(__('Failed to start event'));
        $this->failureNotificationBody(
            fn (?string $error) => app()->hasDebugModeEnabled() ? $error : null
        );

        $this->action(function (self $action, array $data, ExecutionEvent $record) {
            $record->run();
            if ($record->status === EventStatus::FAILED) {
                $action->activityError = $record->output_error;
                $this->failure();

                return;
            }
            $action->activityResult = $record->output;
            $this->success();
        });
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'result' => [$this->getActivityResult()],
            'error'  => [$this->getActivityError()],
            default  => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName)
        };
    }

    protected function getActivityResult(): array
    {
        return $this->activityResult;
    }

    protected function getActivityError(): ?string
    {
        return $this->activityError;
    }

    public static function getDefaultName(): ?string
    {
        return 'event-run';
    }
}
