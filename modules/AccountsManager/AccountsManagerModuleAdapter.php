<?php

declare(strict_types=1);

namespace EpsicubeModules\AccountsManager;

use EpsicubeModules\AccountsManager\Models\Account;
use EpsicubeModules\Hypercore\Concerns\HypercoreAdapter;
use EpsicubeModules\Hypercore\Facades\HypercoreActivator;
use EpsicubeModules\Hypercore\Foundation\HypercoreApplier;
use EpsicubeModules\Hypercore\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

class AccountsManagerModuleAdapter extends HypercoreAdapter
{
    public function moduleIdentifier(): string
    {
        return 'core::accounts-manager';
    }

    public function configureCentral(HypercoreApplier $applier): void
    {
        $applier->injectIntegrations(
            identifier: $this->moduleIdentifier(),
            whenEnabled: function () use ($applier): void {
                $applier->getModule($this->moduleIdentifier())->setHypercoreContext('central');
            }
        );
    }

    public function configureTenant(HypercoreApplier $applier, Tenant $tenant): void
    {
        // Always enable module on child apps ( not necessary )
        $applier->markAsMustUse($this->moduleIdentifier());

        // Configure model to be scoped
        $applier->injectIntegrations(
            identifier: $this->moduleIdentifier(),
            whenEnabled: function () use ($applier): void {

                $guardName = 'accounts';
                Config::set("auth.passwords.{$guardName}.connection", HypercoreActivator::centralConnectionName());

                $applier->getModule($this->moduleIdentifier())->setHypercoreContext('tenant');

                Account::$runtimeConnection = HypercoreActivator::centralConnectionName();
                Account::creating(function (Account $account): void {
                    $account->hypercore_tenant_id = HypercoreActivator::tenant()->id;
                });
                Account::addGlobalScope(function (Builder $builder): void {
                    $builder->where('hypercore_tenant_id', HypercoreActivator::tenant()->id);
                });
            }
        );
    }
}
