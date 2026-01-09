<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Pages;

use BackedEnum;
use Epsicube\Support\Contracts\HasDependencies;
use Epsicube\Support\Contracts\HasIntegrations;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Facades\Options;
use EpsicubeModules\Administration\ApplicationGroup;
use EpsicubeModules\Administration\Enums\Icons;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class ManageModules extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|UnitEnum|null $navigationGroup = ApplicationGroup::SYSTEM;

    protected static string|null|BackedEnum $navigationIcon = Icons::MODULE;

    protected static ?string $slug = '/system/modules';

    protected string $view = 'epsicube-administration::pages.manage-modules';

    protected static ?int $navigationSort = 10;

    public array $state = [];

    public static function canAccess(): bool
    {
        return Options::get('core::administration', 'enable-modules-manager');
    }

    public static function getNavigationLabel(): string
    {
        return __('Modules');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Manage modules');
    }

    public function mount(): void
    {
        $this->state = $this->getState();
    }

    public function modulesInfolist(Schema $schema): Schema
    {
        return $schema->statePath('state')->constantState($this->state)->schema([
            RepeatableEntry::make('modules')
                ->hiddenLabel()
                ->contained(false)
                ->grid([
                    'default' => 1,
                    'md'      => 2,
                    '2xl'     => 3,
                ])->schema([
                    Section::make()
                        ->heading(fn (array $state) => $state['name'])
                        ->columns(2)
                        ->collapsible()
                        ->afterHeader(fn (array $state) => [
                            TextEntry::make('status')->hiddenLabel()
                                ->badge()
                                ->color($this->getActivationColorForModule($state['identifier']))
                                ->getStateUsing(fn () => match (true) {
                                    $state['mu']        => __('Must-Use'),
                                    $state['enabled']   => __('Enabled'),
                                    ! $state['enabled'] => __('Disabled'),
                                }),

                        ])->schema([
                            TextEntry::make('author')->label(__('Author')),
                            TextEntry::make('version')->label(__('Version')),
                            TextEntry::make('description')->label(__('Description'))->columnSpanFull()
                                ->hidden(fn (?string $state) => empty($state)),

                            TextEntry::make('dependencies')->label(__('Dependencies'))->columnSpanFull()
                                ->hidden(fn (?array $state) => empty($state))
                                ->formatStateUsing(fn (string $state) => Modules::safeGet($state)?->identity()->name ?? $state)
                                ->badge()
                                ->color(fn (string $state) => $this->getActivationColorForModule($state)),
                            TextEntry::make('integrations')->label(__('Integrations'))->columnSpanFull()
                                ->hidden(fn (?array $state) => empty($state))
                                ->formatStateUsing(fn (string $state) => Modules::safeGet($state)?->identity()->name ?? $state)
                                ->badge()
                                ->color(fn (string $state) => $this->getActivationColorForModule($state)),
                        ])
                        ->footerActionsAlignment(Alignment::Center)
                        ->footerActions([
                            fn (array $state) => Action::make('enable')->label(__('Enable'))
                                ->link()->color(Color::Green)
                                ->visible(Modules::canBeEnabled($state['identifier']) && ! Modules::hasUnresolvedDependencies($state['identifier']))
                                ->action(function () use ($state): void {
                                    Modules::enable($state['identifier']);
                                    Notification::make()->success()->title(__('Module enabled'))->send();
                                    $this->reloadModules();
                                })->requiresConfirmation(),

                            fn (array $state) => Action::make('enableWithDependencies')->label(__('Enable with dependencies'))
                                ->link()->color(Color::Green)
                                ->visible(Modules::hasUnresolvedDependencies($state['identifier']) && Modules::canEnableWithDependencies($state['identifier']))
                                ->modalHeading(__('Enable with dependencies'))
                                ->modalDescription(__('This will enable (in order): :list', [
                                    'list' => implode(', ', array_map(
                                        fn (string $id) => Modules::safeGet($id)?->identity()->name ?? $id,
                                        Modules::resolveEnableChain($state['identifier'])
                                    )),
                                ]))
                                ->action(function () use ($state): void {
                                    Modules::enableWithDependencies($state['identifier']);
                                    Notification::make()->success()->title(__('Module enabled'))->send();
                                    $this->reloadModules();
                                })
                                ->requiresConfirmation(),

                            fn (array $state) => Action::make('disable')->label(__('Disable'))
                                ->link()->color(Color::Red)
                                ->visible(Modules::canBeDisabled($state['identifier']))
                                ->action(function () use ($state): void {
                                    Modules::disable($state['identifier']);
                                    Notification::make()->danger()->title(__('Module disabled'))->send();
                                    $this->reloadModules();
                                })->requiresConfirmation(),

                            fn (array $state) => Action::make('disableWithDependents')->label(__('Disable with dependents'))
                                ->link()->color(Color::Red)
                                ->visible(! Modules::canBeDisabled($state['identifier']) && Modules::canDisableWithDependents($state['identifier']))
                                ->modalHeading(__('Disable with dependents'))
                                ->modalDescription(__('This will disable (in order): :list', [
                                    'list' => implode(', ', array_map(
                                        fn (string $id) => Modules::safeGet($id)?->identity()->name ?? $id,
                                        Modules::resolveDisableChain($state['identifier'])
                                    )),
                                ]))
                                ->action(function () use ($state): void {
                                    Modules::disableWithDependents($state['identifier']);
                                    Notification::make()->danger()->title(__('Module disabled'))->send();
                                    $this->reloadModules();
                                })
                                ->requiresConfirmation(),
                        ]),

                ]),
        ]);
    }

    protected function getActivationColorForModule(string|Module $module): array
    {
        return match (true) {
            Modules::safeGet($module) === null => Color::Zinc, // <- absent
            Modules::isMustUse($module)        => Color::Orange, // <- present, must-use
            Modules::isEnabled($module)        => Color::Green, // <- present, enabled
            default                            => Color::Red, // <- present, disabled
        };
    }

    protected function reloadModules(): void
    {

        $this->forceRender();

        $this->reset();
        $this->mount();
        //        $this->redirect(static::getUrl(), true);
    }

    public function getState(?string $search = null): array
    {
        $modules = Modules::all();
        if (! empty($search)) {
            $search = mb_strtolower($search);
            $modules = array_filter($modules, function (Module $module) use ($search) {
                $identity = $module->identity();
                $haystack = mb_strtolower(implode(' ', [
                    $identity->name ?? '',
                    $identity->description ?? '',
                    $identity->author ?? '',
                ]));

                return str_contains(mb_strtolower($haystack), $search);
            });
        }

        $modules = array_values(array_map(fn (Module $module) => [
            'identifier'   => $module->identifier(),
            'name'         => $module->identity()->name,
            'description'  => $module->identity()->description,
            'author'       => $module->identity()->author,
            'version'      => $module->identity()->version,
            'mu'           => Modules::isMustUse($module),
            'enabled'      => Modules::isEnabled($module),
            'dependencies' => is_a($module, HasDependencies::class)
                ? $module->dependencies()->requiredModules()
                : [],
            'integrations' => is_a($module, HasIntegrations::class)
                ? array_keys($module->integrations()->registrations())
                : [],
        ], $modules));

        return ['modules' => $modules];
    }
}
