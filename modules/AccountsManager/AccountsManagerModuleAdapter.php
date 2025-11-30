<?php

declare(strict_types=1);

namespace UniGaleModules\AccountsManager;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use UniGaleModules\AccountsManager\Models\Account;
use UniGaleModules\Hypercore\Concerns\HypercoreAdapter;
use UniGaleModules\Hypercore\Facades\HypercoreActivator;
use UniGaleModules\Hypercore\Foundation\HypercoreApplier;
use UniGaleModules\Hypercore\Models\Tenant;

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
            whenEnabled: function () use ($applier) {
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
            whenEnabled: function () use ($applier) {

                $guardName = 'accounts';
                Config::set("auth.passwords.{$guardName}.connection", HypercoreActivator::centralConnectionName());

                $applier->getModule($this->moduleIdentifier())->setHypercoreContext('tenant');

                Account::$runtimeConnection = HypercoreActivator::centralConnectionName();
                Account::creating(function (Account $account) {
                    $account->hypercore_tenant_id = HypercoreActivator::tenant()->id;
                });
                Account::addGlobalScope(function (Builder $builder) {
                    $builder->where('hypercore_tenant_id', HypercoreActivator::tenant()->id);
                });
            }
        );
    }
}
