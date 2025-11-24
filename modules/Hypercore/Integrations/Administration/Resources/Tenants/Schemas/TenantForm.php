<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Schemas;

use DateTimeZone;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Locale;
use ResourceBundle;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Wizard::make()->schema([
                Step::make(__('General'))->schema([
                    Grid::make(3)->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(150)
                            ->autofocus()
                            ->columnSpan(2),

                        TextInput::make('identifier')
                            ->label(__('Identifier'))
                            ->required()
                            ->maxLength(150)
                            ->autofocus()
                            ->columnSpan(1),

                        FusedGroup::make([
                            Select::make('scheme')->label(__('Scheme'))
                                ->options([
                                    'http'  => 'http',
                                    'https' => 'https',
                                ])->placeholder('http(s)://')
                                ->default('https')
                                ->rules(['max:5'])
                                ->nullable()
                                ->columnSpan(2),
                            TextInput::make('domain')
                                ->suffix('/')
                                ->placeholder(__('example.com'))
                                ->rules(['max:255'])
                                ->required()
                                ->columnSpan(7),
                            TextInput::make('path')
                                ->helperText(__('without /'))
                                ->rules(['max:64'])
                                ->nullable()
                                ->columnSpan(3),
                        ])->label(__('Url'))->columnSpanFull()->columns(12),

                    ]),
                ]),

                Step::make(__('Localization'))->schema([
                    Select::make('locale')
                        ->label(__('Language'))
                        ->searchable()
                        ->options(
                            collect(ResourceBundle::getLocales(''))
                                ->mapWithKeys(fn (string $id) => [$id => Locale::getDisplayName($id, app()->getLocale())])
                                ->all()
                        )
                        ->default(app()->getLocale())
                        ->rules(['max:35'])
                        ->required(),

                    Select::make('timezone')
                        ->label(__('Timezone'))
                        ->searchable()
                        ->options(array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()))
                        ->default(config('app.timezone'))
                        ->rules(['max:32'])
                        ->required(),
                ])->columns(2),

                Step::make(__('Advanced'))->schema([
                    ToggleButtons::make('debug')->label(__('Debug'))
                        ->boolean()->inline()
                        ->default(false),
                    ToggleButtons::make('maintenance')->label(__('Maintenance'))
                        ->boolean()->inline()
                        ->default(false),
                    KeyValue::make('config_overrides')
                        ->columnSpanFull()
                        ->label(__('Config Overrides'))
                        ->nullable(),
                ])->columns(2),
            ]),
        ]);
    }
}
