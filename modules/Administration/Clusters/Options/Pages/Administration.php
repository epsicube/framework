<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Clusters\Options\Pages;

use Epsicube\Support\Facades\Options;
use EpsicubeModules\Administration\Clusters\Options\OptionsCluster;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

class Administration extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'epsicube-administration::pages.manage-options';

    protected static ?string $cluster = OptionsCluster::class;

    public array $state = [];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Options::all('core::administration'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->schema([
            Toggle::make('enable-modules-manager')
                ->label(__('Enable Modules Manager')),

            TextInput::make('brand-name')
                ->label(__('Brand Name')),

            Toggle::make('spa')
                ->label(__('SPA')),

            Toggle::make('top-navigation')
                ->label(__('Top Navigation')),
        ]);
    }

    public function create(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Options::set('core::administration', $key, $value);
        }
    }
}
