<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Foundation\Bootstrap;

use Epsicube\Foundation\EpsicubeApplication;
use Epsicube\Foundation\Managers\ModulesManager;
use Epsicube\Support\Contracts\ActivationDriver;
use Epsicube\Support\Facades\Manifest;
use Epsicube\Support\Facades\Modules;
use EpsicubeModules\Hypercore\Activation\TenantActivationDriver;
use EpsicubeModules\Hypercore\Concerns\HypercoreAdapter;
use EpsicubeModules\Hypercore\Facades\HypercoreActivator;
use EpsicubeModules\Hypercore\Foundation\Detector\DatabaseTenantDetector;
use EpsicubeModules\Hypercore\Foundation\HypercoreApplier;
use EpsicubeModules\Hypercore\Foundation\MaintenanceMode\HypercoreMaintenanceMode;
use EpsicubeModules\Hypercore\HypercoreModuleAdapter;
use EpsicubeModules\Hypercore\Models\Tenant;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\MaintenanceModeManager;
use Illuminate\Routing\RouteGroup;
use Illuminate\Routing\Router;
use RuntimeException;

class BootstrapHypercore
{
    public function bootstrap(EpsicubeApplication $app): void
    {
        /* Keep using $_SERVER to ensure persistence in nested commands like optimize */
        if (! isset($_SERVER['hypercore::tenant'])) {
            $_SERVER['hypercore::tenant'] = (new DatabaseTenantDetector)->getDetectedTenant($app);
        }

        if (! $tenant = $_SERVER['hypercore::tenant']) {
            $this->configureCentral($app, app(Modules::$accessor));

            return;
        }
        // Force to reload configuration using new cache suffix
        $app->setCacheSuffix($tenant->identifier);
        $app->make(LoadConfiguration::class)->bootstrap($app);

        // Configure tenant overrides
        $this->configureTenant($tenant, $app, app(Modules::$accessor));
    }

    protected function configureCentral(Application $app, ModulesManager $registry): void
    {
        $this->applyCentralAdapter($app, $registry, $registry->getDriver());
    }

    protected function configureTenant(Tenant $tenant, Application $app, ModulesManager $registry): void
    {
        $tenantDriver = new TenantActivationDriver($tenant->id);
        $this->applyTenantAdapters($app, $tenant, $registry, $tenantDriver);

        // Override epsicube
        $registry->driver = $tenantDriver;
        $tenant->setConnection(HypercoreActivator::centralConnectionName());

        /**
         * Force router to generate prefix when path is defined
         * domain not possible, but scoped by detector
         */
        if ($tenant->path) {
            (function () use ($tenant): void {
                /** @var Router $this */
                $this->updateGroupStack(RouteGroup::merge(['prefix' => $tenant->path], $this->getGroupStack()));
            })->call(app('router'));
        }

        $app->resolving('url', function (UrlGenerator $url) use ($tenant): void {
            $url->forceScheme($tenant->scheme);
        });
        // Supports (fix filament do not use bootstrap path to get filename)
        $app->resolving('filament', function ($_, Application $app) use (&$tenant): void {
            $app['config']->set('filament.cache_path', $app->bootstrapPath("cache/filament-{$tenant->identifier}"));
        });

        $app->extend(
            MaintenanceModeManager::class,
            fn (MaintenanceModeManager $manager) => $manager->extend('hypercore', fn () => new HypercoreMaintenanceMode($tenant))
        );

        if ($app instanceof CachesConfiguration && $app->configurationIsCached()) {
            return;
        }

        /** @var Repository $config */
        $config = $app->make('config');
        /**
         * Extract initial database and rename it central
         * Do not extract initial database name from config (not work when cached)
         */
        $initialDbConnection = $config->get('database.connections.'.$config->get('database.default'));
        $configOverrides = array_merge([
            'app.name' => $tenant->name,
            'app.key'  => $tenant->key,

            // URLs
            'app.url'          => $tenant->url,
            'app.frontend_url' => "{$tenant->url}:3000",

            // Localisation
            'app.locale'   => $tenant->locale,
            'app.timezone' => $tenant->timezone,

            // Create tenant and central connection
            'database.connections.'.HypercoreActivator::centralConnectionName() => $initialDbConnection,
            'database.connections.'.HypercoreActivator::tenantConnectionName()  => array_merge($initialDbConnection, [
                'prefix'         => "t_{$tenant->id}_",
                'prefix_indexes' => true,
            ]),

            // Set tenant connection as default
            'database.default' => HypercoreActivator::tenantConnectionName(),

            // Cache & maintenance
            'cache.prefix'           => "{$tenant->identifier}::",
            'app.maintenance.driver' => 'hypercore',

            // Session
            'session.cookie' => "{$tenant->identifier}::session",
            'session.domain' => $tenant->domain,
            'session.path'   => "/{$tenant->path}", // <- do ont use without fixing router

            // Extras
            'app.debug' => $tenant->debug,
        ], $tenant->config_overrides ?? []);

        foreach ($configOverrides as $key => $value) {
            $config->set($key, $value);
        }
    }

    /**
     * Applies all Hypercore adapters for the tenant.
     *
     * Enabled adapters customize tenant behavior (module injection, must-use
     * marking, activation adjustments) through the HypercoreApplier.
     */
    protected function applyTenantAdapters(Application $app, Tenant $tenant, ModulesManager $registry, ActivationDriver $driver): void
    {
        $manifest = $app->get(Manifest::$accessor)->config('hypercore');
        $adapters = array_merge([HypercoreModuleAdapter::class], $manifest['adapters'] ?? []);
        foreach ($adapters as $adapterClass) {
            if (! is_a($adapterClass, HypercoreAdapter::class, true)) {
                throw new RuntimeException(
                    "Adapter class '{$adapterClass}' must extend ".HypercoreAdapter::class
                );
            }
            /** @var HypercoreAdapter $adapter */
            $adapter = $app->make($adapterClass);
            if (! $registry->isEnabled($adapter->moduleIdentifier())) {
                continue;
            }

            $applier = new HypercoreApplier($registry, $registry->get($adapter->moduleIdentifier()), $driver);
            $adapter->configureTenant($applier, $tenant);
            unset($applier);
        }
    }

    /**
     * Applies all Hypercore adapters for the central.
     *
     * Enabled adapters customize tenant behavior (module injection, must-use
     * marking, activation adjustments) through the HypercoreApplier.
     */
    protected function applyCentralAdapter(Application $app, ModulesManager $registry, ActivationDriver $driver): void
    {
        $manifest = $app->get(Manifest::$accessor)->config('hypercore');
        $adapters = array_merge([HypercoreModuleAdapter::class], $manifest['adapters'] ?? []);
        foreach ($adapters as $adapterClass) {
            if (! is_a($adapterClass, HypercoreAdapter::class, true)) {
                throw new RuntimeException(
                    "Adapter class '{$adapterClass}' must extend ".HypercoreAdapter::class
                );
            }
            /** @var HypercoreAdapter $adapter */
            $adapter = $app->make($adapterClass);
            if (! $registry->isEnabled($adapter->moduleIdentifier())) {
                continue;
            }

            $applier = new HypercoreApplier($registry, $registry->get($adapter->moduleIdentifier()), $driver);
            $adapter->configureCentral($applier);
            unset($applier);
        }
    }
}
