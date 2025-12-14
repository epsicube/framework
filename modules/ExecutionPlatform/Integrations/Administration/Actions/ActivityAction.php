<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions;

use Closure;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use Filament\Actions\Action;
use Filament\Support\Enums\Operation;
use Throwable;

class ActivityAction extends Action
{
    protected Closure|string|null $activityIdentifier = null;

    /** @var Closure(): array<string, mixed>|array<string, mixed> */
    protected Closure|array $activityConstants = [];

    /** @var Closure(): array<string, mixed>|array<string, mixed> */
    protected Closure|array $activityDefaults = [];

    protected ?array $activityResult = null;

    protected ?Throwable $activityError = null;

    public function activity(string|Closure $identifier, array|Closure $constants = [], array|Closure $defaults = []): static
    {
        $this->activityIdentifier = $identifier;
        $this->activityConstants = $constants;
        $this->activityDefaults = $defaults;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->fillForm(fn (self $action) => $action->getActivityDefaults());
        $this->schema(function (self $action): array {
            $activitySchema = Activities::inputSchema($action->getActivityIdentifier());
            $constants = $this->getActivityConstants();
            if (! empty($constants)) {
                $activitySchema = $activitySchema->except(...array_keys($constants));
            }

            return $activitySchema->toFilamentComponents(Operation::Create);
        });

        $this->successNotificationTitle(__('Activity successfully executed'));
        $this->failureNotificationTitle(__('Error executing activity'));
        $this->failureNotificationBody(
            fn (?Throwable $error) => app()->hasDebugModeEnabled() ? $error?->getMessage() : null
        );

        $this->action(function (self $action, array $data) {
            $data = array_merge($this->getActivityConstants(), $data);
            try {
                $execution = Activities::run($this->getActivityIdentifier(), $data);
                $action->activityResult = $execution->output ?? [];
                $action->success();
            } catch (Throwable  $e) {
                report($action->activityError = $e);
                $action->failure();
            }
        });

        $this->modalSubmitActionLabel(__('Execute'));
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'result' => [$this->getActivityResult()],
            'error'  => [$this->getActivityError()],
            default  => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName)
        };
    }

    public function getActivityIdentifier(): string
    {
        return $this->evaluate($this->activityIdentifier);
    }

    public function getActivityConstants(): array
    {
        return $this->evaluate($this->activityConstants);
    }

    public function getActivityDefaults(): array
    {
        return $this->evaluate($this->activityDefaults);
    }

    protected function getActivityResult(): array
    {
        return $this->activityResult;
    }

    protected function getActivityError(): ?Throwable
    {
        return $this->activityError;
    }
}
