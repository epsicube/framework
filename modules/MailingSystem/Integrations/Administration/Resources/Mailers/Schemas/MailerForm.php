<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Schemas;

use EpsicubeModules\MailingSystem\Facades\Drivers;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class MailerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('General'))->schema([
                TextInput::make('name')->label(__('Name'))->required(),
                Select::make('driver')->label(__('Driver'))
                    ->options(fn () => Drivers::toIdentifierLabelMap())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Select $component): void {
                        $component
                            ->getContainer()
                            ->getComponent('configuration', withHidden: true)
                            ->getChildSchema()
                            ->fill();
                    }),
            ])->columns(2),

            Section::make(__('Sender'))
                ->schema([
                    TextInput::make('from_email')->label(__('Email'))->email()->required(),
                    TextInput::make('from_name')->label(__('Name'))->nullable(),
                ])->columns(2),

            Section::make(__('Configuration'))->columnSpanFull()
                ->statePath('configuration')->key('configuration')
                ->live()
                ->schema(function (Get $get) {
                    $driver = $get('driver');
                    if (blank($driver)) {
                        return [];
                    }

                    $driverInstance = Drivers::safeGet($driver);
                    if (! $driverInstance) {
                        return [];
                    }

                    $schema = \Epsicube\Schemas\Schema::create('config');
                    $driverInstance->inputSchema($schema);

                    return $schema->toFilamentComponents(Operation::Create);
                })->hiddenWhenAllChildComponentsHidden(),
        ]);
    }
}
