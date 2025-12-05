<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Foundation\MaintenanceMode;

use EpsicubeModules\Hypercore\Models\Tenant;
use Illuminate\Contracts\Foundation\MaintenanceMode as MaintenanceModeContract;
use Illuminate\Foundation\FileBasedMaintenanceMode;

class HypercoreMaintenanceMode extends FileBasedMaintenanceMode implements MaintenanceModeContract
{
    public function __construct(protected Tenant $tenant) {}

    public function activate(array $payload): void
    {
        $this->tenant->update([
            'maintenance'       => true,
            '_maintenance_data' => $payload,
        ]);
    }

    public function deactivate(): void
    {
        $this->tenant->update([
            'maintenance'       => false,
            '_maintenance_data' => null,
        ]);
    }

    public function active(): bool
    {
        return $this->tenant->maintenance;
    }

    public function data(): array
    {
        return $this->tenant->_maintenance_data ?? [];
    }
}
