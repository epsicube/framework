<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Pages;

use BackedEnum;
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Facades\Options;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
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

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
        $this->fillForm();
    }

    protected function fillForm()
    {
        $this->form->state(Options::all($this->activeTab));
        $this->form->fill(Options::allStored($this->activeTab));
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
        return $schema->statePath('data')->operation($this->operation->value)->schema(fn () => [
            Options::getSchema($this->activeTab)->export(new FilamentExporter($this->operation)),
        ]);
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Options::set($this->activeTab, $key, $value);
        }

        Notification::make()->success()->title(__('Saved'))->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleMode')
                ->label(fn () => $this->operation === Operation::View ? __('Edit Mode') : __('View Mode'))
                ->icon(fn () => $this->operation === Operation::View ? Heroicon::OutlinedPencil : Heroicon::OutlinedEye)
                ->outlined()->size(Size::Small)
                ->color(fn () => $this->operation === Operation::View ? 'primary' : 'gray')
                ->action(function () {
                    $this->operation = ($this->operation === Operation::View)
                        ? Operation::Edit
                        : Operation::View;
                    $this->fillForm();
                }),
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
