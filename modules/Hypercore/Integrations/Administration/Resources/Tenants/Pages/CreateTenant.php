<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages;

use EpsicubeModules\Hypercore\Integrations\Administration\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
