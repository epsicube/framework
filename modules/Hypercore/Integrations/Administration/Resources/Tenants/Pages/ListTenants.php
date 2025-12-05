<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages;

use EpsicubeModules\Hypercore\Integrations\Administration\Resources\Tenants\TenantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
