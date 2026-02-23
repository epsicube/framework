<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Managers;

use Epsicube\Foundation\Events\PreparingModuleActivationPlan;
use Epsicube\Foundation\Events\PreparingModuleDeactivationPlan;
use Epsicube\Support\Contracts\ActivationDriver;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Enums\ConditionState;
use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Exceptions\DuplicateItemException;
use Epsicube\Support\Exceptions\UnresolvableItemException;
use Epsicube\Support\Facades\Options;
use Epsicube\Support\Modules\Module;
use Illuminate\Contracts\Foundation\Application;
use RuntimeException;
use Throwable;

class ModulesManager
{
    public function __construct(public ActivationDriver $driver) {}

    /**
     * @var array<string, Module>
     */
    protected array $modules = [];

    protected array $logs = [];

    protected KahnResolver $resolver;

    protected bool $booted = false;

    public function register(IsModule ...$modules): void
    {
        foreach ($modules as $module) {
            $identifier = $module->module()->identifier;
            if (array_key_exists($identifier, $this->modules)) {
                throw new DuplicateItemException($identifier);
            }

            $this->modules[$identifier] = $module->module();
            if ($this->booted) {
                unset($this->resolver);
                $this->modules[$identifier]->mustUse = false;
                $this->modules[$identifier]->status = ModuleStatus::DISABLED;
            }
        }
    }

    public function get(string $identifier): Module
    {
        if (! array_key_exists($identifier, $this->all())) {
            throw new UnresolvableItemException($identifier);
        }

        return $this->all()[$identifier];
    }

    public function safeGet(string $identifier): ?Module
    {
        return $this->modules[$identifier] ?? null;
    }

    /**
     * @return array<string,Module>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * @return array<string,Module>
     */
    public function enabled(): array
    {
        return array_filter($this->all(), fn (Module $module) => $module->status === ModuleStatus::ENABLED);
    }

    /**
     * @return array<string,Module>
     */
    public function disabled(): array
    {
        return array_filter($this->all(), fn (Module $module) => $module->status === ModuleStatus::DISABLED);
    }

    public function getResolver(): KahnResolver
    {
        return $this->resolver ??= new KahnResolver(...array_values($this->modules));
    }

    public function bootstrap(Application $app): void
    {
        if ($this->booted) {
            return;
        }

        $this->logs = [];
        $wanted = array_filter(
            $this->modules,
            fn (Module $m) => $this->driver->isMustUse($m) || $this->driver->isEnabled($m)
        );

        $candidates = array_filter($wanted, function (Module $m) {
            if (! $m->requirements->passes()) {
                foreach ($m->requirements->conditions as $condition) {
                    if ($condition->resultState === ConditionState::INVALID) {
                        $this->log($m, 'Requirement condition failed: '.$condition->getMessage());
                    }
                }

                return false;
            }

            return true;
        });

        $registrables = $this->getResolver()->resolve($this->log(...), ...array_values($candidates));
        $registrableIdentifiers = array_map(fn (Module $m) => $m->identifier, $registrables);

        // Fill modules with activation information
        foreach ($this->modules as $identifier => $module) {
            $module->mustUse = $this->driver->isMustUse($module);
            $module->status = match (true) {
                in_array($identifier, $registrableIdentifiers, true) => ModuleStatus::ENABLED,
                array_key_exists($identifier, $wanted)               => ModuleStatus::ERROR,
                default                                              => ModuleStatus::DISABLED,
            };
        }

        foreach ($registrables as $module) {

            Options::registerSchema($module->options);
            foreach ($module->providers as $provider) {
                $app->register($provider);
            }

            $app->booting(function () use ($module) {
                $module->supports->execute();
            });
        }

        $this->booted = true;
    }

    // ACTIVATION MANAGEMENT
    public function canBeEnabled(string|Module $module): bool
    {
        $module = is_string($module) ? $this->get($module) : $module;

        if ($module->status !== ModuleStatus::DISABLED || $this->driver->isMustUse($module)) {
            return false;
        }

        if (! $module->requirements->passes()) {
            return false;
        }

        try {
            $chain = $this->getResolver()->resolveEnableChain($module->identifier, fn () => null);

            return in_array($module, $chain, true);
        } catch (Throwable) {
            return false;
        }
    }

    public function canBeDisabled(string|Module $module): bool
    {
        $module = is_string($module) ? $this->get($module) : $module;

        if ($module->status === ModuleStatus::ERROR && ! $this->driver->isMustUse($module)) {
            return true;
        }

        if ($module->status === ModuleStatus::DISABLED || $this->driver->isMustUse($module)) {
            return false;
        }

        $chain = $this->getResolver()->resolveDisableChain($module->identifier);
        foreach ($chain as $dependent) {
            if ($dependent->identifier === $module->identifier) {
                continue;
            }

            if ($dependent->status === ModuleStatus::ENABLED) {
                return false;
            }
        }

        return true;
    }

    public function activationPlan(string|Module $module): PreparingModuleActivationPlan
    {
        $module = is_string($module) ? $this->get($module) : $module;
        if (! $this->canBeEnabled($module)) {
            throw new RuntimeException(__('This module cannot be enabled.'));
        }

        $plan = new PreparingModuleActivationPlan($module);
        event($plan);

        $plan->addTask('Mark module as enabled', function () use (&$module) {
            $this->driver->enable($module);
            $module->status = ModuleStatus::ENABLED;
        }, -1);

        // TODO class Plan/Task and use array return $tasks = event()
        return $plan;
    }

    public function deactivationPlan(string|Module $module): PreparingModuleDeactivationPlan
    {
        $module = is_string($module) ? $this->get($module) : $module;
        if (! $this->canBeDisabled($module)) {
            throw new RuntimeException(__('This module cannot be disabled.'));
        }

        $plan = new PreparingModuleDeactivationPlan($module);
        event($plan);

        $plan->addTask('Mark module as disabled', function () use (&$module) {
            $this->driver->disable($module);
            $module->status = ModuleStatus::DISABLED;
        }, -1);

        // TODO class Plan/Task and use array return $tasks = event()
        return $plan;
    }

    public function getBootstrapLogs(?string $identifier = null): array
    {
        if ($identifier) {
            return $this->logs[$identifier] ?? [];
        }

        return $this->logs;
    }

    protected function log(Module $module, string $string): void
    {
        $this->logs[$module->identifier][] = $string;
    }
}
