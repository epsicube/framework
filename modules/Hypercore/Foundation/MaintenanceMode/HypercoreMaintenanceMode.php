<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Foundation\MaintenanceMode;

use Illuminate\Contracts\Foundation\MaintenanceMode as MaintenanceModeContract;
use Illuminate\Foundation\FileBasedMaintenanceMode;
use UniGaleModules\Hypercore\Models\Tenant;

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
