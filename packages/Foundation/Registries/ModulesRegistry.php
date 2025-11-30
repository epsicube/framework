<?php

declare(strict_types=1);

namespace UniGale\Foundation\Registries;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;
use Throwable;
use UniGale\Foundation\Concerns\Registry;
use UniGale\Foundation\Contracts\ActivationDriver;
use UniGale\Foundation\Contracts\HasDependencies;
use UniGale\Foundation\Contracts\HasIntegrations;
use UniGale\Foundation\Contracts\Module;
use UniGale\Foundation\Contracts\Registrable;
use UniGale\Foundation\Exceptions\CircularDependencyException;
use UniGale\Foundation\IntegrationsManager;

/**
 * @extends Registry<Module>
 */
class ModulesRegistry extends Registry
{
    protected IntegrationsManager $integrations;

    public function __construct(protected ActivationDriver $driver)
    {
        $this->integrations = new IntegrationsManager;
    }

    public function getRegistrableType(): string
    {
        return Module::class;
    }

    public function setDriver(ActivationDriver $driver): void
    {
        $this->driver = $driver;
    }

    public function getDriver(): ActivationDriver
    {
        return $this->driver;
    }

    public function integrations(): IntegrationsManager
    {
        return $this->integrations;
    }

    /**
     * @return array<string,Module>
     */
    public function enabled(): array
    {
        return array_filter($this->all(), fn (Module $module) => $this->isEnabled($module));
    }

    /**
     * @return array<string,Module>
     */
    public function disabled(): array
    {
        return array_filter($this->all(), fn (Module $module) => ! $this->isEnabled($module));
    }

    public function enable(string|Module $module): void
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }
        if (! $this->canBeEnabled($module)) {
            $missing = $this->missingDependencies($module);
            if ($missing !== []) {
                throw new RuntimeException(
                    __('This module cannot be enabled: missing dependencies [:list].', ['list' => implode(', ', $missing)])
                );
            }

            throw new RuntimeException(__('This module cannot be enabled (must-use or already enabled).'));
        }
        $this->driver->enable($module);
    }

    public function markAsMustUse(string|Module $module): void
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }
        if (! $this->canBeEnabled($module)) {
            $missing = $this->missingDependencies($module);
            if ($missing !== []) {
                throw new RuntimeException(
                    __('This module cannot be enabled: missing dependencies [:list].', ['list' => implode(', ', $missing)])
                );
            }

            throw new RuntimeException(__('This module cannot be enabled (must-use or already enabled).'));
        }
        $this->driver->markAsMustUse($module);
    }

    public function disable(string|Module $module): void
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        // Hard block: must-use or already disabled
        if (! $this->isEnabled($module) || $this->isMustUse($module)) {
            throw new RuntimeException(__('This module cannot be disabled (must-use or already disabled).'));
        }

        // Prevent disabling a module that is a dependency of any enabled module
        $blocking = $this->enabledDependentsOf($module);
        if ($blocking !== []) {
            throw new RuntimeException(
                __('This module cannot be disabled: it is required by [:list].', ['list' => implode(', ', $blocking)])
            );
        }
        $this->driver->disable($module);
    }

    public function isEnabled(string|Module $module): bool
    {
        if (is_string($module)) {
            if (! $module = $this->safeGet($module)) {
                return false;
            }
        }

        // MU are always enabled
        if ($this->isMustUse($module)) {
            return true;
        }

        // Consider module enabled only if marked enabled by driver AND all dependencies are satisfied
        if (! $this->driver->isEnabled($module)) {
            return false;
        }
        try {
            return $this->missingDependencies($module) === [];
        } catch (CircularDependencyException) {
            // A cycle means we cannot safely consider the module enabled
            return false;
        }
    }

    /**
     * Determine if a module is marked as Must-Use (always enabled) by the driver.
     */
    public function isMustUse(string|Module $module): bool
    {
        if (is_string($module)) {
            $resolved = $this->safeGet($module);
            if (! $resolved) {
                return false; // Unknown identifier is not MU
            }
            $module = $resolved;
        }

        return $this->driver->isMustUse($module);
    }

    public function canBeEnabled(string|Module $module): bool
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        if ($this->isMustUse($module)) {
            return false;
        }

        // Cannot enable if dependencies are missing
        if ($this->missingDependencies($module) !== []) {
            return false;
        }

        return ! $this->isEnabled($module);
    }

    public function canBeDisabled(string|Module $module): bool
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        if ($this->isMustUse($module)) {
            return false;
        }

        if (! $this->isEnabled($module)) {
            return false;
        }

        // Cannot be disabled if some enabled module depends on it
        return $this->enabledDependentsOf($module) === [];
    }

    /**
     * Compute the cascade disable chain for a module: all enabled dependents first (top-down),
     * then the target module last. Throws when a Must-Use module is encountered in the dependent chain
     * or when a dependency cycle is detected among dependents traversal.
     *
     * @return string[] Ordered list of identifiers to disable
     */
    public function resolveDisableChain(string|Module $module): array
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        $order = [];
        $perm = [];
        $temp = [];

        $visit = function (Module $m) use (&$visit, &$order, &$perm, &$temp) {
            $id = $m->identifier();
            if (isset($perm[$id])) {
                return;
            }
            if (isset($temp[$id])) {
                throw new CircularDependencyException('Circular dependency detected while resolving disable chain.');
            }

            // If this module is Must-Use, it cannot be disabled in a cascade
            if ($this->isMustUse($m)) {
                throw new RuntimeException(__('Cannot disable because module [:module] is Must-Use.', ['module' => $id]));
            }

            // Only consider enabled dependents; disabled ones are irrelevant
            $temp[$id] = true;
            foreach ($this->all() as $depId => $candidate) {
                if ($depId === $id) {
                    continue;
                }
                // Skip if candidate not enabled
                if (! $this->isEnabled($candidate)) {
                    continue;
                }
                // If candidate depends on current module, it must be disabled before current
                $deps = $this->dependenciesOf($candidate);
                if (in_array($id, $deps, true)) {
                    $visit($candidate);
                }
            }

            $perm[$id] = true;
            unset($temp[$id]);

            // Only include modules that are currently enabled (idempotent behavior)
            if ($this->isEnabled($id) && ! $this->isMustUse($id)) {
                $order[] = $id;
            }
        };

        // Target must be enabled and not MU to consider cascade
        if ($this->isMustUse($module)) {
            throw new RuntimeException(__('This module cannot be disabled (must-use).'));
        }
        if (! $this->isEnabled($module)) {
            // Nothing to do: return empty chain
            return [];
        }

        $visit($module);

        return $order;
    }

    /**
     * Disable a module and all its enabled dependents in a safe order.
     * Idempotent: skips already disabled modules and never tries to disable Must-Use.
     */
    public function disableWithDependents(string|Module $module): void
    {
        $chain = $this->resolveDisableChain($module);

        foreach ($chain as $id) {
            if ($this->isMustUse($id)) {
                continue; // extra safety
            }
            if ($this->isEnabled($id)) {
                $this->driver->disable($this->get($id));
            }
        }
    }

    /**
     * Return whether a module can be disabled together with its dependents.
     * Returns false if target is Must-Use or not enabled, or if the chain cannot be resolved
     * because of Must-Use dependents or cycles.
     */
    public function canDisableWithDependents(string|Module $module): bool
    {
        try {
            if ($this->isMustUse($module) || ! $this->isEnabled($module)) {
                return false;
            }
            $chain = $this->resolveDisableChain($module);

            return $chain !== [];
        } catch (Throwable) {
            return false;
        }
    }

    protected function registerItem(string $identifier, Registrable $item): void
    {
        parent::registerItem($identifier, $item);

        if ($item instanceof HasIntegrations) {
            $this->integrations->beginRecording($identifier);
            $item->integrations($this->integrations);
            $this->integrations->endRecording();
        }
    }

    public function registerInApp(Application $app): void
    {
        $enabledModules = $this->enabled();
        foreach ($enabledModules as $module) {
            $app->register($module);
        }

        $this->integrations->runCallbacks(array_keys($enabledModules));
    }

    /**
     * Retourne la liste des identifiers de modules visés par les intégrations
     * enregistrées par le module fourni.
     *
     * @return string[]
     */
    public function integrationsOf(string|Module $module): array
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        return $this->integrations->registeredModulesFor($module->identifier());
    }

    /**
     * Returns the list of dependency identifiers declared by the given module.
     *
     * @return string[]
     */
    public function dependenciesOf(string|Module $module): array
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        if ($module instanceof HasDependencies) {
            return array_values(array_filter(array_map('strval', $module->dependencies())));
        }

        return [];
    }

    /**
     * Returns the list of missing dependencies for a module. A dependency is considered satisfied
     * if it is Must-Use or effectively enabled in the registry.
     *
     * @return string[]
     */
    public function missingDependencies(string|Module $module): array
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        return $this->missingDependenciesInternal($module, []);
    }

    /**
     * Returns the list of enabled modules identifiers that depend (directly) on the given module.
     * Used to prevent disabling a module that is required by others.
     *
     * @return string[]
     */
    protected function enabledDependentsOf(string|Module $module): array
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        $target = $module->identifier();
        $dependents = [];

        foreach ($this->all() as $identifier => $candidate) {
            if ($identifier === $target) {
                continue;
            }

            // Skip if candidate is not enabled; only enabled dependents block disabling
            if (! $this->isEnabled($candidate)) {
                continue;
            }

            $deps = $this->dependenciesOf($candidate);
            if (in_array($target, $deps, true)) {
                $dependents[] = $identifier;
            }
        }

        return $dependents;
    }

    /**
     * Internal helper to compute missing dependencies and detect cycles.
     * Returns a flat list of dependency identifiers that are not satisfied.
     * A dependency is satisfied if it is Must-Use or effectively enabled, and its
     * own dependencies are also satisfied. Unknown dependencies are reported as missing.
     *
     * @param  array<int,string>  $path  Current DFS path for cycle detection
     * @return string[]
     */
    protected function missingDependenciesInternal(Module $module, array $path): array
    {
        $id = $module->identifier();
        if (in_array($id, $path, true)) {
            throw new CircularDependencyException('Circular dependency detected: '.implode(' -> ', array_merge($path, [$id])));
        }

        $path[] = $id;

        $missing = [];
        foreach ($this->dependenciesOf($module) as $depId) {
            $dep = $this->safeGet($depId);
            if (! $dep) {
                $missing[] = $depId; // Unknown dependency

                continue;
            }

            // If dependency is not MU and not marked enabled by driver, it is missing.
            if (! $this->isMustUse($dep) && ! $this->driver->isEnabled($dep)) {
                $missing[] = $depId;

                // Still check deeper to collect unknowns/cycles under this branch
                // but only if needed; we continue to next dependency.
                continue;
            }

            // Recurse to ensure the dependency's own deps are satisfied
            $childMissing = $this->missingDependenciesInternal($dep, $path);
            foreach ($childMissing as $m) {
                $missing[] = $m;
            }
        }

        // Deduplicate while preserving order
        return array_values(array_unique($missing));
    }

    /**
     * Resolve the activation chain for a module (dependencies first, target last).
     * Throws when encountering unknown dependencies or a cycle.
     *
     * @return string[] Ordered list of identifiers to enable
     */
    public function resolveEnableChain(string|Module $module): array
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        $order = [];
        $perm = [];
        $temp = [];

        $visit = function (Module $m) use (&$visit, &$order, &$perm, &$temp) {
            $id = $m->identifier();
            if (isset($perm[$id])) {
                return;
            }
            if (isset($temp[$id])) {
                throw new CircularDependencyException('Circular dependency detected while resolving enable chain.');
            }

            $temp[$id] = true;
            foreach ($this->dependenciesOf($m) as $depId) {
                $dep = $this->safeGet($depId);
                if (! $dep) {
                    throw new RuntimeException(__('Unknown dependency [:dep] for module [:module].', ['dep' => $depId, 'module' => $id]));
                }
                $visit($dep);
            }
            $perm[$id] = true;
            unset($temp[$id]);
            $order[] = $id;
        };

        $visit($module);

        return $order;
    }

    /**
     * Enable a module alongside its required dependencies, in order. Idempotent.
     */
    public function enableWithDependencies(string|Module $module): void
    {
        $chain = $this->resolveEnableChain($module);

        foreach ($chain as $id) {
            if ($this->isMustUse($id)) {
                continue; // Must-Use considered already enabled
            }

            if (! $this->isEnabled($id)) {
                $this->driver->enable($this->get($id));
            }
        }
    }

    /**
     * Return whether a module can be enabled together with its dependencies.
     * Returns false when the chain cannot be resolved (unknown deps or cycles),
     * or when the module is Must-Use or already effectively enabled.
     */
    public function canEnableWithDependencies(string|Module $module): bool
    {
        try {
            // If already enabled or MU, no need for the flow
            if ($this->isMustUse($module) || $this->isEnabled($module)) {
                return false;
            }

            $this->resolveEnableChain($module);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Return true when the given module has unresolved dependencies
     * (i.e. at least one dependency is missing/unknown or a cycle is detected).
     */
    public function hasUnresolvedDependencies(string|Module $module): bool
    {
        try {
            return $this->missingDependencies($module) !== [];
        } catch (CircularDependencyException) {
            // A detected cycle is considered unresolved
            return true;
        }
    }

    /**
     * Return details about missing dependencies: unknown vs not-enabled.
     * This is useful for UI messaging.
     *
     * @return array{missing: string[], unknown: string[]}
     */
    public function missingDependencyDetails(string|Module $module): array
    {
        if (is_string($module)) {
            $module = $this->get($module);
        }

        $unknown = [];
        $missing = [];
        foreach ($this->dependenciesOf($module) as $depId) {
            $dep = $this->safeGet($depId);
            if (! $dep) {
                $unknown[] = $depId;

                continue;
            }
            if (! $this->isEnabled($dep) && ! $this->isMustUse($dep)) {
                $missing[] = $depId;
            }
        }

        return [
            'missing' => array_values(array_unique($missing)),
            'unknown' => array_values(array_unique($unknown)),
        ];
    }
}
