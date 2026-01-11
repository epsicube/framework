<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Pages;

use BackedEnum;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Support\Contracts\HasOptions;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Facades\Options;
use EpsicubeModules\Administration\Enums\ApplicationGroup;
use EpsicubeModules\Administration\Enums\Icons;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Operation;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use UnitEnum;

use function Filament\Support\original_request;

class ManageOptions extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|UnitEnum|null $navigationGroup = ApplicationGroup::SYSTEM;

    protected static string|null|BackedEnum $navigationIcon = Icons::OPTION;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = '/system/options/{module?}';

    protected string $view = 'epsicube-administration::pages.manage-options';

    public string $activeModule;

    #[Url('mode')]
    public Operation $operation = Operation::View;

    public ?array $data = [];

    public array $stored = [];

    /** @var array<string,bool> */
    public array $useCustom = [];

    public function mount(): void
    {
        $this->injectCustomStyles();
        $activeModule = original_request()->route()->parameter('module');
        if (! $activeModule) {
            $this->redirect(static::getUrl(['module' => 'core::administration']));

            return;
        }
        $this->activeModule = $activeModule;
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        // Reset form
        $this->form->state(null);
        $this->form->fill(null);

        $schema = Options::getSchema($this->activeModule);

        // Stored values only (custom values)
        $this->stored = Options::store()->all($this->activeModule);
        $this->useCustom = array_reduce(
            array_keys($schema->properties()),
            function ($carry, $name) use ($schema) {
                $property = $schema->properties()[$name];
                $carry[$name] = array_key_exists($name, $this->stored) || ! $property->hasDefault();

                return $carry;
            },
            []
        );

        $state = $schema->withDefaults($this->stored);

        $this->form->state($state);
        $this->form->fill($state);
    }

    public function updatedActiveTab(): void
    {
        $this->fillForm();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->operation($this->operation->value)
            ->schema(function () {
                $modifyComponentUsing = function (Property $property, ?string $name, Component $component): void {
                    if ($name === null || ! $property->hasDefault()) {
                        return;
                    }

                    if (! ($component instanceof Field || $component instanceof Entry)) {
                        return;
                    }

                    // afterContent handles both View and Edit modes dynamically
                    $component->afterContent(function () use ($component, $name, $property) {
                        $hasStored = array_key_exists($name, $this->stored);

                        // --- VIEW MODE ------------------------------------------------------
                        // Shows an icon indicating whether the value is overridden or using module default.
                        if ($this->operation === Operation::View) {
                            return $hasStored
                                ? Icon::make(Heroicon::OutlinedExclamationCircle)
                                    ->color('warning')
                                    ->tooltip(__('Overridden value'))
                                : Icon::make(Icons::MODULE)
                                    ->color('gray')
                                    ->tooltip(__('Default value provided by the module'));
                        }

                        // --- EDIT MODE ------------------------------------------------------
                        // Allows switching between default and custom value.
                        $isCustom = $this->useCustom[$name] ?? false;

                        return Action::make('toggleDefault')
                            ->label($isCustom ? new HtmlString(Str::replace(' ', '&nbsp;', __('Restore default'))) : __('Edit'))
                            ->icon($isCustom ? Heroicon::OutlinedArrowUturnLeft : Heroicon::OutlinedPencilSquare)
                            ->color($isCustom ? 'warning' : 'gray')
                            ->tooltip(__('Click to toggle between default value and custom input'))
                            ->action(function () use ($name, $property, $component, $isCustom): void {

                                // Toggle between default and custom state
                                $this->useCustom[$name] = ! $isCustom;

                                // Update field state based on the toggle
                                $component->state(
                                    $this->useCustom[$name] ?? false
                                    ? (array_key_exists($name, $this->stored) ? $this->stored[$name] : $property->getDefault())
                                    : $property->getDefault()
                                );
                                $component->callAfterStateHydrated();
                            });
                    });

                    // Field disabled logic: respects toggle AND view mode
                    $component->disabled(fn () => ! ($this->useCustom[$name] ?? false));
                    // Always dehydrated: save() loop is authoritative
                    $component->dehydrated(true);
                };

                $schema = Options::getSchema($this->activeModule);
                $components = $schema->toFilamentComponents($this->operation, $modifyComponentUsing);
                if (empty($components)) {
                    $components = [
                        Text::make(__('This module declares using options, but it does not define any options')),
                    ];
                }

                return Section::make(__('General'))->key($this->activeModule)->schema($components);
            });
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {

            // If user selected custom value → persist it
            if ($this->useCustom[$key] ?? false) {
                Options::set($this->activeModule, $key, $value);

                continue;
            }

            // Otherwise → remove explicit storage so module default applies
            Options::delete($this->activeModule, $key);
        }

        Notification::make()->success()->title(__('Saved'))->send();
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('displayMode')
                    ->disabled()
                    ->label(fn () => $this->operation === Operation::Edit ? __('Edit Mode') : __('View Mode'))
                    ->icon(fn () => $this->operation === Operation::Edit ? Heroicon::OutlinedPencil : Heroicon::OutlinedEye)
                    ->outlined()
                    ->size(Size::Small)
                    ->color(fn () => $this->operation === Operation::Edit ? 'warning' : 'gray'),

                Action::make('toggleMode')
                    ->label(__('Switch'))
                    ->color('primary')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->iconPosition(IconPosition::After)
                    ->outlined()
                    ->size(Size::Small)
                    ->action(function (): void {
                        $this->operation = $this->operation === Operation::Edit
                            ? Operation::View
                            : Operation::Edit;

                        $this->fillForm();
                    }),
            ])->buttonGroup(),

        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
            Actions::make([
                Action::make('resetAllFields')
                    ->label(__('Undo all changes'))
                    ->color('primary')
                    ->visible(fn () => $this->operation === Operation::Edit)
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->outlined()
                    ->size(Size::Small)
                    ->action(fn () => $this->fillForm()),

                Action::make('save')
                    ->label(__('Save'))
                    ->visible(fn () => $this->operation === Operation::Edit)
                    ->keyBindings(['mod+s'])
                    ->action('save'),
            ])->alignJustify(),
        ]);
    }

    /**
     * Generate one navigation page per module
     *
     * @return array|NavigationGroup[]|NavigationItem[]
     */
    public function getSubNavigation(): array
    {
        return collect(Modules::enabled())
            ->filter(fn (Module $module) => $module instanceof HasOptions)
            ->map(fn (Module $module, string $identifier) => NavigationGroup::make($module->identity()->name)->items([
                NavigationItem::make(__('General'))
                    ->url(static::getUrl([
                        'module' => $identifier,
                        'mode'   => $this->operation !== Operation::View ? $this->operation->value : null,
                    ]))
                    ->isActiveWhen(function () use ($identifier) {
                        $request = original_request();

                        return $request->routeIs(static::getNavigationItemActiveRoutePattern())
                            && $request->route()->parameter('module') === $identifier;
                    }),
            ]))->all();
    }

    public function getPageClasses(): array
    {
        return ['ec-manage-options'];
    }

    protected function injectCustomStyles(): void
    {
        // Compact navigation
        FilamentView::registerRenderHook(PanelsRenderHook::HEAD_END, function () {
            return new HtmlString('<style>
                .ec-manage-options .fi-page-sub-navigation-sidebar{gap:0;}
                .ec-manage-options .fi-page-sub-navigation-sidebar .fi-sidebar-group{gap:0.125em;}
                .ec-manage-options .fi-page-sub-navigation-sidebar .fi-sidebar-group.fi-active{background-color: color-mix(in oklab,var(--color-black)2%,transparent);border-radius: 8px;}
                .ec-manage-options .fi-page-sub-navigation-sidebar .fi-sidebar-group.fi-active:where(.dark,.dark *){background-color: color-mix(in oklab,var(--color-white)5%,transparent);}                
                .ec-manage-options .fi-page-sub-navigation-sidebar .fi-sidebar-group.fi-active .fi-sidebar-item-btn:not(:where(.dark,.dark *)){background-color: color-mix(in oklab,var(--color-black)2%,transparent);}
                .ec-manage-options .fi-page-sub-navigation-sidebar .fi-sidebar-group.fi-active .fi-sidebar-group-label{color:var(--primary-700);}
                .ec-manage-options .fi-page-sub-navigation-sidebar .fi-sidebar-group.fi-active .fi-sidebar-group-label:where(.dark,.dark *){color:var(--primary-400);}
                .ec-manage-options .fi-page-sub-navigation-sidebar .fi-sidebar-item-btn{padding-block:0.25em; margin:0 0.5em 0.5em 0.5em;}
                .ec-manage-options .fi-page-sub-navigation-sidebar .fi-sidebar-item-grouped-border{width:5px;}
            </style>');
        }, [static::class]);
    }
}
