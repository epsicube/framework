<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Activation;

use Epsicube\Support\Contracts\ActivationDriver;
use Epsicube\Support\Contracts\Module;
use EpsicubeModules\Hypercore\Facades\HypercoreActivator;
use EpsicubeModules\Hypercore\Models\Module as ModuleModel;

/**
 * Tenant-scoped activation driver: persists activation per tenant_id.
 */
class TenantActivationDriver implements ActivationDriver
{
    protected array $mustUseModules = [];

    /** @var array<string,bool> Current activation state for the tenant */
    protected array $state = [];

    public function __construct(
        protected int $tenantId,
    ) {}

    protected function ensureStateIsLoaded(): void
    {
        if ($this->state === []) {
            $this->state = ModuleModel::on(HypercoreActivator::centralConnectionName())
                ->where('tenant_id', $this->tenantId)
                ->pluck('enabled', 'identifier')
                ->all();
        }
    }

    public function enable(Module $module): void
    {
        $identifier = $module->identifier();
        ModuleModel::on(HypercoreActivator::centralConnectionName())->updateOrCreate(
            ['tenant_id' => $this->tenantId, 'identifier' => $identifier],
            ['enabled' => true]
        );
        $this->state[$identifier] = true;
    }

    public function disable(Module $module): void
    {
        $identifier = $module->identifier();
        ModuleModel::on(HypercoreActivator::centralConnectionName())
            ->where('tenant_id', $this->tenantId)
            ->where('identifier', $identifier)
            ->delete();
        $this->state[$identifier] = false;
    }

    public function isEnabled(Module $module): bool
    {
        $this->ensureStateIsLoaded();

        return $this->state[$module->identifier()] ?? false;
    }

    public function isMustUse(Module $module): bool
    {
        return isset($this->mustUseModules[$module->identifier()]);
    }

    public function markAsMustUse(Module $module): void
    {
        $this->mustUseModules[$module->identifier()] = true;
    }
}
