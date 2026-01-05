<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore;

use EpsicubeModules\Hypercore\Concerns\HypercoreAdapter;
use EpsicubeModules\Hypercore\Foundation\HypercoreApplier;
use EpsicubeModules\Hypercore\Models\Tenant;
use Illuminate\Console\Application as Artisan;
use Symfony\Component\Console\Input\InputOption;

class HypercoreModuleAdapter extends HypercoreAdapter
{
    public function moduleIdentifier(): string
    {
        return 'core::hypercore';
    }

    //
    // TODO merge hypercore in base app ans use a trait on module instead of adapter
    public function register(): void
    {
        Artisan::starting(function (Artisan $artisan): void {
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

        $activator = new HypercoreModuleActivator(app());
        $applier->injectModules($activator);
        $applier->markAsMustUse($activator->identifier());
    }
}
