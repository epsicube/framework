<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Pages;

use BackedEnum;
use Composer\Semver\Semver;
use Epsicube\Support\Concerns\Condition;
use Epsicube\Support\Enums\ConditionState;
use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Conditions\Callback;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use EpsicubeModules\Administration\AdministrationOptions;
use EpsicubeModules\Administration\Enums\ApplicationGroup;
use EpsicubeModules\Administration\Enums\Icons;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

class ManageModules extends Page implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected static string|UnitEnum|null $navigationGroup = ApplicationGroup::SYSTEM;

    protected static string|null|BackedEnum $navigationIcon = Icons::MODULE;

    protected static ?string $slug = '/system/modules';

    protected string $view = 'epsicube-administration::pages.manage-modules';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return AdministrationOptions::isModulesManagerEnabled();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema($this->getModuleCards()),
        ]);
    }

    protected function getModuleCards(): array
    {
        return array_map(fn (Module $module) => Section::make($module->identifier)
            ->heading($module->identity->name)
            ->description($module->identifier)
            ->compact()
            ->afterHeader([
                ...($module->mustUse) ? [
                    TextEntry::make('mustUse')->hiddenLabel()
                        ->icon(Heroicon::ShieldCheck)->iconColor(Color::Yellow)
                        ->tooltip(__('Must-use'))
                        ->formatStateUsing(fn () => null) // Keep to avoid text
                        ->getConstantStateUsing(fn () => $module->mustUse),
                ] : [],

                ...($module->status === ModuleStatus::ERROR) ? [
                    TextEntry::make('error')->hiddenLabel()
                        ->icon(Heroicon::ExclamationTriangle)->iconColor(Color::Red)
                        ->tooltip(__('Error'))
                        ->formatStateUsing(fn () => null) // Keep to avoid text
                        ->getConstantStateUsing(fn () => $module->status === ModuleStatus::ERROR),
                ] : [],

                Action::make("toggle-{$module->identifier}")
                    ->label(match ($module->status) {
                        ModuleStatus::ENABLED  => __('Enabled'),
                        ModuleStatus::DISABLED => __('Disabled'),
                        ModuleStatus::ERROR    => __('Error'),
                    })
                    ->color(match ($module->status) {
                        ModuleStatus::ENABLED  => Color::Green,
                        ModuleStatus::DISABLED => Color::Neutral,
                        ModuleStatus::ERROR    => Color::Red,
                    })
                    ->icon(match (true) {
                        Modules::canBeDisabled($module->identifier) => Heroicon::Stop,
                        Modules::canBeEnabled($module->identifier)  => Heroicon::Play,
                        default                                     => null,
                    })->badge()

                    // Display plan
                    ->schema(function () use ($module): array {
                        if (Modules::canBeDisabled($module->identifier)) {
                            $plan = Modules::deactivationPlan($module);
                        } elseif (Modules::canBeEnabled($module->identifier)) {
                            $plan = Modules::activationPlan($module);
                        } else {
                            return [];
                        }

                        $tasks = $plan->getTasks();

                        return [
                            Section::make(__('Planned execution steps'))
                                ->icon(Heroicon::CommandLine)
                                ->dense()->compact()->secondary()
                                ->schema(array_map(function (array $task, int $index) {
                                    return TextEntry::make("task_{$index}")
                                        ->hiddenLabel()
                                        ->getConstantStateUsing(fn () => $task['label'])
                                        ->icon(Heroicon::ChevronRight)->iconColor(Color::Gray);
                                }, $tasks, array_keys($tasks))),
                        ];
                    })
                    // Modal configuration
                    ->requiresConfirmation()->modalWidth(Width::Small)
                    ->modalHeading(fn () => $module->status !== ModuleStatus::DISABLED ? __('Disable Module') : __('Enable Module'))
                    ->modalDescription(fn () => $module->status !== ModuleStatus::DISABLED
                        ? __('Are you sure you want to disable this module? Some features might become unavailable.')
                        : __('Are you sure you want to enable this module?'))
                    ->modalIcon(fn () => $module->status !== ModuleStatus::DISABLED ? Heroicon::Stop : Heroicon::Play)
                    ->modalIconColor(fn () => $module->status !== ModuleStatus::DISABLED ? Color::Red : Color::Green)
                    ->modalSubmitAction(fn (Action $action) => $action->label(match ($module->status) {
                        ModuleStatus::ENABLED, ModuleStatus::ERROR => __('Disable'),
                        ModuleStatus::DISABLED => __('Enable'),
                    })->color(match ($module->status) {
                        ModuleStatus::ENABLED, ModuleStatus::ERROR => Color::Red,
                        ModuleStatus::DISABLED => Color::Green,
                    }))
                    ->modalCancelAction(fn (Action $action) => $action->hidden())
                    ->disabled(! Modules::canBeDisabled($module->identifier) && ! Modules::canBeEnabled($module->identifier))
                    ->action(function (Action $action) use ($module) {

                        if (Modules::canBeDisabled($module->identifier)) {
                            $plan = Modules::deactivationPlan($module);
                            $action->successNotificationTitle(__('Module disabled'));
                        } elseif (Modules::canBeEnabled($module->identifier)) {
                            $plan = Modules::activationPlan($module);
                            $action->successNotificationTitle(__('Module enabled'));
                        } else {
                            $action->halt();
                        }
                        try {
                            $plan->execute();
                        } catch (Throwable $e) {
                            $action->failure();

                            return;
                        }
                        $action->success();
                        $action->redirect(static::getUrl());
                    }),
            ])->dense()->columns(2)->schema([
                TextEntry::make('author')->label(__('Author'))
                    ->copyable()
                    ->getConstantStateUsing(fn () => $module->identity->author)
                    ->color(Color::Gray),
                TextEntry::make('version')->label(__('Version'))
                    ->getConstantStateUsing(fn () => $module->version)
                    ->color(Color::Gray),

                TextEntry::make('description')->label(__('Description'))
                    ->columnSpanFull()
                    ->hidden(empty($module->identity->description))
                    ->getConstantStateUsing(fn () => $module->identity->description)
                    ->color(Color::Gray),

                $this->getConditionsComponent(__('Requirements'), $module->requirements->conditions),

                $this->getConditionsComponent(__('Dependencies'), array_map(function (string $version, string $identifier) {
                    $module = Modules::safeGet($identifier);

                    return new Callback(function () use ($module, $version) {
                        if (! $module) {
                            return false;
                        }

                        return Semver::satisfies($module->version, $version);
                    }, ($module?->identity->name ?? $identifier)." [{$version}]");
                }, $module->dependencies->modules, array_keys($module->dependencies->modules))),

                $this->getConditionsComponent(__('Supports'), array_map(
                    fn (Support $support) => $support->condition,
                    $module->supports->supports
                )),
            ]), Modules::all());
    }

    protected function getConditionsComponent(string $name, array $conditions): Section
    {

        $stats = collect($conditions)
            ->countBy(fn (Condition $condition) => $condition->run()->value)
            ->all();

        return Section::make($name)
            ->columnSpanFull()
            ->compact()->dense()->secondary()
            ->collapsible()->collapsed()
            ->hidden(empty($conditions))
            ->afterHeader([
                ...($stats[ConditionState::VALID->value] ?? null) ? [
                    TextEntry::make('valid')->hiddenLabel()
                        ->getConstantStateUsing(fn () => $stats[ConditionState::VALID->value])
                        ->color(Color::Green)->badge()
                        ->icon(Heroicon::Check)->iconPosition(IconPosition::After),
                ] : [],

                ...($stats[ConditionState::INVALID->value] ?? null) ? [
                    TextEntry::make('invalid')->hiddenLabel()
                        ->getConstantStateUsing(fn () => $stats[ConditionState::INVALID->value])
                        ->color(Color::Red)->badge()
                        ->icon(Heroicon::XMark)->iconPosition(IconPosition::After),
                ] : [],

                ...($stats[ConditionState::SKIPPED->value] ?? null) ? [
                    TextEntry::make('skipped')->hiddenLabel()
                        ->getConstantStateUsing(fn () => $stats[ConditionState::SKIPPED->value])
                        ->color(Color::Neutral)->badge()
                        ->icon(Heroicon::Minus)->iconPosition(IconPosition::After),
                ] : [],
            ])
            ->schema(array_map(function (Condition $condition) {
                $state = $condition->run();
                $color = match ($state) {
                    ConditionState::SKIPPED => Color::Neutral,
                    ConditionState::INVALID => Color::Red,
                    ConditionState::VALID   => Color::Green,
                };

                return TextEntry::make($condition->name())->hiddenLabel()
                    ->iconColor($color)->color($color)
                    ->getConstantStateUsing(fn () => $condition->name())
                    ->tooltip($condition->getMessage())
                    ->icon(match ($state) {
                        ConditionState::VALID   => Heroicon::Check,
                        ConditionState::INVALID => Heroicon::XMark,
                        ConditionState::SKIPPED => Heroicon::Minus,
                    })->iconPosition(IconPosition::Before);
            }, $conditions));
    }
}
