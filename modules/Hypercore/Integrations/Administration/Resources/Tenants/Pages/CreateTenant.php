<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages;

use Filament\Resources\Pages\CreateRecord;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\TenantResource;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
