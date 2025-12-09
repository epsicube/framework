<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Pages;

use BackedEnum;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Facades\Options;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Operation;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class ManageOptions extends Page implements HasSchemas
{
    use HasTabs, InteractsWithSchemas;

    #[Url]
    public ?string $activeTab = null;

    protected string $view = 'epsicube-administration::pages.manage-options';

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedCog;

    #[Url('mode')]
    public Operation $operation = Operation::View;

    public ?array $data = [];

    public array $stored = [];

    /** @var array<string,bool> */
    public array $useCustom = [];

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        // Reset form
        $this->form->state(null);
        $this->form->fill(null);

        $schema = Options::getSchema($this->activeTab);

        // Stored values only (custom values)
        $this->stored = Options::store()->all($this->activeTab);
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

    public function getTabs(): array
    {
        return collect(Options::schemas())->map(
            fn ($_, string $id) => Tabs\Tab::make(Modules::safeGet($id)?->identity()->name ?? $id)->key('id'),
        )->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->operation($this->operation->value)
            ->schema(function () {
                return Options::getSchema($this->activeTab)->export(
                    new FilamentExporter($this->operation, function (Property $property, ?string $name, Component $component): void {
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
                                    : Icon::make(Heroicon::OutlinedCube)
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
                                ->action(function () use ($name, $property, $component, $isCustom) {

                                    // Toggle between default and custom state
                                    $this->useCustom[$name] = ! $isCustom;

                                    // Update field state based on the toggle
                                    $component->state(
                                        $this->useCustom[$name] ?? false
                                        ? (array_key_exists($name, $this->stored) ? $this->stored[$name] : $property->getDefault())
                                        : $property->getDefault()
                                    );
                                });
                        });

                        // Field disabled logic: respects toggle AND view mode
                        $component->disabled(fn () => ! ($this->useCustom[$name] ?? false));
                        // Always dehydrated: save() loop is authoritative
                        $component->dehydrated(true);
                    })
                );
            });
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {

            // If user selected custom value → persist it
            if ($this->useCustom[$key] ?? false) {
                Options::set($this->activeTab, $key, $value);

                continue;
            }

            // Otherwise → remove explicit storage so module default applies
            Options::delete($this->activeTab, $key);
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
                    ->action(function () {
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
            $this->getTabsContentComponent(),
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
}
