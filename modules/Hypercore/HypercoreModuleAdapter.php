<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore;

use Illuminate\Console\Application as Artisan;
use Symfony\Component\Console\Input\InputOption;
use UniGaleModules\Hypercore\Concerns\HypercoreAdapter;
use UniGaleModules\Hypercore\Foundation\HypercoreApplier;
use UniGaleModules\Hypercore\Models\Tenant;

class HypercoreModuleAdapter extends HypercoreAdapter
{
    public function moduleIdentifier(): string
    {
        return HypercoreModule::make()->identifier();
    }

    //
    // TODO merge hypercore in base app ans use a trait on module instead of adapter
    public function register(): void
    {
        Artisan::starting(function (Artisan $artisan) {
            $artisan->getDefinition()->addOption(new InputOption(
                'tenant',
                null,
                InputOption::VALUE_REQUIRED,
                __('Run the command for a specific tenant (using identifier).')
            ));
        });
    }

    public function configureCentral(HypercoreApplier $applier): void
    {
        $this->register();
    }

    public function configureTenant(HypercoreApplier $applier, Tenant $tenant): void
    {
        $this->register();
        $applier->removeModules([$this->moduleIdentifier()]);

        $activator = HypercoreModuleActivator::make();
        $applier->injectModules($activator);
        $applier->markAsMustUse($activator->identifier());
    }
}
