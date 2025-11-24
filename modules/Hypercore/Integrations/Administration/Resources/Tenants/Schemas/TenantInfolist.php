<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Schemas;

use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            //            Section::make()->schema([
            //                TextEntry::make('name')->label(__('Name'))->size(TextSize::Large)->weight(FontWeight::Bold),
            //                TextEntry::make('locale')->label(__('Locale')),
            //                TextEntry::make('currency')->label(__('Currency')),
            //            ])->columns(['md' => 2]),

        ]);
    }
}
