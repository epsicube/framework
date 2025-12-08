<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Pages;

use BackedEnum;
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
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Operation;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

class ManageOptions extends Page implements HasSchemas
{
    use HasTabs,InteractsWithSchemas;

    #[Url]
    public ?string $activeTab = null;

    protected string $view = 'epsicube-administration::pages.manage-options';

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedCog;

    public ?array $data = [];

    #[Url('mode')]
    public Operation $operation = Operation::View;

    public array $stored = [];

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->stored = Options::allStored($this->activeTab);

        $this->form->fill($this->stored); // <- form
        $this->form->state(Options::all($this->activeTab)); // <- infolist
    }

    public function getTabs(): array
    {
        return collect(Options::schemas())->map(
            fn ($_, string $id) => Tabs\Tab::make(Modules::safeGet($id)?->identity()->name ?? $id)->key('id'),
        )->all();
    }

    public function updatedActiveTab(): void
    {
        $this->fillForm();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->operation($this->operation->value)->schema(function () {
            $optionsSchema = Options::getSchema($this->activeTab);

            $exporter = new FilamentExporter($this->operation, function (Component $component, ?string $name) use ($optionsSchema): void {

                if ($name === null || ! ($component instanceof Field || $component instanceof Entry)) {
                    return;
                }
                $default = $optionsSchema->properties()[$name]?->getDefault() ?? null;
                if ($default !== null && $this->operation === Operation::Edit && method_exists($component, 'placeholder')) {
                    $component->placeholder($default);
                }

                $component->afterContent(function (Component $component) use ($name) {
                    $isOverride = isset($this->stored[$name]) && $component->getState() !== null;

                    return $isOverride ? [
                        $this->operation === Operation::Edit
                            ? Action::make('resetToDefault')
                                ->label(__('Reset'))
                                ->icon(Heroicon::OutlinedArrowUturnLeft)
                                ->color('warning')
                                ->action(fn () => $component->state(null))
                            : Icon::make(Heroicon::OutlinedLockClosed)
                                ->color('warning')
                                ->tooltip(__('Overridden value')),
                    ] : [
                        Icon::make(Heroicon::OutlinedLockOpen)->color('gray')->tooltip(
                            $this->operation === Operation::Edit
                           ? __('Leave empty to keep the module default')
                           : __('Default value provided by the module')
                        ),
                    ];
                });
            });

            return $optionsSchema->export($exporter);
        });
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Options::set($this->activeTab, $key, $value);
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
                    ->outlined()->size(Size::Small)
                    ->color(fn () => $this->operation === Operation::Edit ? 'warning' : 'gray'),

                Action::make('toggleMode')
                    ->label(__('Switch'))
                    ->color('primary')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)->iconPosition(IconPosition::After)
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
                Action::make('save')->label(__('Save'))
                    ->visible(fn () => $this->operation === Operation::Edit)
                    ->action('save')
                    ->keyBindings(['mod+s']),
            ]),
        ]);
    }
}
