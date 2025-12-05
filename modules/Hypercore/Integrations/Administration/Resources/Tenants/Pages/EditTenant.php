<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages;

use EpsicubeModules\Hypercore\Integrations\Administration\Resources\Tenants\TenantResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
