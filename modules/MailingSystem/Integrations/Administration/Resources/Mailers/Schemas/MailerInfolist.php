<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Schemas;

use EpsicubeModules\MailingSystem\Facades\Drivers;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Widgets\MailerStatsOverview;
use EpsicubeModules\MailingSystem\Models\Mailer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class MailerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make()
                ->heading(fn (Mailer $record) => $record->name)
                ->description(fn (Mailer $record) => __('From: :name <:email>', ['name' => $record->from_name, 'email' => $record->from_email]))
                ->afterHeader([
                    TextEntry::make('driver')->hiddenLabel()
                        ->formatStateUsing(fn (string $state) => Drivers::safeGet($state)?->label() ?? $state)
                        ->badge(),
                ]),

            Livewire::make(MailerStatsOverview::class, fn (Mailer $record) => [
                'mailer' => $record,
            ]),
        ]);
    }

    public static function providerInfolist(): array
    {
        return [
            Section::make(__('Driver Parameters'))
                ->columnSpanFull()
                ->statePath('configuration')
                ->schema(function (Mailer $record) {
                    $driverInstance = Drivers::safeGet($record->driver);
                    if (! $driverInstance) {
                        return [];
                    }

                    $schema = \Epsicube\Schemas\Schema::create('config');
                    $driverInstance->inputSchema($schema);

                    return $schema->toFilamentComponents(Operation::View);
                })
                ->hiddenWhenAllChildComponentsHidden(),
        ];
    }
}
