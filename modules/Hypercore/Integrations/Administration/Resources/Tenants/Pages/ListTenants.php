<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\TenantResource;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
