<?php

declare(strict_types=1);

namespace UniGaleModules\Administration\Pages;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UniGale\Foundation\Facades\Options;
use UnitEnum;

class ManageOptions extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'unigale-administration::pages.manage-options';

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 30;

    public array $state = [];

    public static function getNavigationLabel(): string
    {
        return __('Options');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Manage options');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Options::all('core::administration'));
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Integrations');
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
            Options::set($key, $value, 'core::administration');
        }
    }
}
