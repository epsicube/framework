<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\TenantResource;

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
