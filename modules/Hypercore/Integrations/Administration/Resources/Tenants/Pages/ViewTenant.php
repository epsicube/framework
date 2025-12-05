<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages;

use EpsicubeModules\Hypercore\Integrations\Administration\Resources\Tenants\TenantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
