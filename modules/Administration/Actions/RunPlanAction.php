<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Actions;

use AllowDynamicProperties;
use Closure;
use Epsicube\Support\Plan;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Throwable;

#[AllowDynamicProperties]
class RunPlanAction extends Action
{
    /** @var Closure():Plan|Plan */
    protected Closure|Plan $plan;

    /** @var Closure():array|array */
    protected Closure|array $runArgs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->modalheading(__('Run plan'));
        $this->modaldescription(__('The following plan will be executed.'));

        $this->modalSubmitAction(fn (Action $action) => $action->label(__('Run plan')));
        $this->modalCancelAction(fn (Action $action) => $action->hidden());

        $this->failureNotificationTitle(__('Execution failed'));
        $this->successNotificationTitle(__('Execution successful'));
        $this->schema(fn (self $action): array => $action->getPlanSchema());

        $this->action(function (self $action) {
            try {
                ($action->getPlan())(...$action->getRunArgs());
                $action->success();
            } catch (Throwable $exception) {
                $action->failureNotificationBody($exception->getMessage());
                $action->failure();
            }
        });
    }

    /**
     * @param  Plan|Closure():Plan  $plan
     * @param  array|Closure():array  $runArgs
     */
    public function plan(Plan|Closure $plan, array|Closure $runArgs = []): static
    {
        $this->plan = $plan;
        $this->runArgs = $runArgs;

        return $this;
    }

    public function getPlan(): Plan
    {
        return $this->evaluate($this->plan);
    }

    public function getRunArgs(): array
    {
        return $this->evaluate($this->runArgs);
    }

    /**
     * @return list<array{label: string, callback: callable, hidden: bool}>
     */
    public function getVisibleTasks(): array
    {
        return array_values(array_filter(
            $this->getPlan()->getTasks(),
            static fn (array $task): bool => ! $task['hidden'],
        ));
    }

    /**
     * @return array<int, Section>
     */
    public function getPlanSchema(): array
    {
        $tasks = $this->getVisibleTasks();
        if (empty($tasks)) {
            return [];
        }

        return [
            Section::make(__('Planned tasks'))
                ->icon(Heroicon::CommandLine)
                ->dense()
                ->compact()
                ->secondary()
                ->schema(array_map(function (array $task, int $index): TextEntry {
                    return TextEntry::make("task_{$index}")
                        ->hiddenLabel()
                        ->getConstantStateUsing(fn (): string => $task['label'])
                        ->icon(Heroicon::ChevronRight)
                        ->iconColor(Color::Gray);
                }, $tasks, array_keys($tasks))),
        ];
    }
}
