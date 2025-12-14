<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions;

use EpsicubeModules\ExecutionPlatform\Enum\EventStatus;
use EpsicubeModules\ExecutionPlatform\Enum\EventType;
use EpsicubeModules\ExecutionPlatform\Enum\Icons;
use EpsicubeModules\ExecutionPlatform\Models\ExecutionEvent;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;

class ReRunEventAction extends Action
{
    protected ?array $activityResult = null;

    protected ?string $activityError = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hidden(fn (ExecutionEvent $record) => $record->activity_type === EventType::ACTIVITY);

        $this->label(__('Re-run'));
        $this->icon(Icons::RUN);
        $this->color('info');
        $this->modalWidth(Width::Large);

        $this->successNotificationTitle(__('Event re-run successfully started'));
        $this->failureNotificationTitle(__('Failed to re-run event'));
        $this->failureNotificationBody(
            fn (?string $error) => app()->hasDebugModeEnabled() ? $error : null
        );

        $this->action(function (self $action, array $data, ExecutionEvent $record) {
            $clone = (new ExecutionEvent([
                'execution_id'  => $record->execution_id,
                'activity_type' => $record->activity_type,
                'event_type'    => $record->event_type,
                'tries'         => 1,
                'input'         => $record->input,
                'status'        => EventStatus::QUEUED,
            ]))->run();

            if ($clone->status === EventStatus::FAILED) {
                $this->activityError = $clone->output_error;
                $this->failure();

                return;
            }

            $action->activityResult = $clone->output;
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

    public static function getDefaultName(): ?string
    {
        return 'event-rerun';
    }

    protected function getActivityResult(): array
    {
        return $this->activityResult;
    }

    protected function getActivityError(): ?string
    {
        return $this->activityError;
    }
}
