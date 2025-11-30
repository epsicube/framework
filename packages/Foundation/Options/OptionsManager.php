<?php

declare(strict_types=1);

namespace UniGale\Foundation\Options;

use UniGale\Foundation\Contracts\HasOptions;
use UniGale\Foundation\Contracts\Module;
use UniGale\Foundation\Contracts\OptionsStore;
use UniGale\Foundation\Exceptions\DefinitionNotFoundException;

class OptionsManager
{
    /** @var array<string, OptionsDefinition> */
    protected array $definitions = [];

    /** @var array<string,mixed> */
    protected array $state = [];

    /** @var array<string,array<string>> */
    protected array $loadedKeys = [];

    /** @var array<string,true> */
    protected array $fullyLoaded = [];

    protected bool $autoloaded = false;

    public function __construct(protected OptionsStore $store) {}

    public function registerModules(Module ...$modules): void
    {
        // Generate definitions
        foreach ($modules as $module) {
            if (! is_a($module, HasOptions::class)) {
                continue;
            }

            $definition = new OptionsDefinition;
            $module->options($definition);
            $this->definitions[$module->identifier()] = $definition;
        }
    }

    public function getDefinition(string $moduleIdentifier): OptionsDefinition
    {
        if (! array_key_exists($moduleIdentifier, $this->definitions)) {
            throw new DefinitionNotFoundException($moduleIdentifier);
        }

        return $this->definitions[$moduleIdentifier];

    }

    public function get(string $key, ?string $moduleIdentifier = null, mixed $default = null): mixed
    {
        $this->ensureAutoload();

        if (! array_key_exists($key, $this->loadedKeys[$moduleIdentifier ?? '__GLOBAL__'] ?? [])) {
            $value = $this->applyDefaults(
                moduleIdentifier: $moduleIdentifier,
                options: [$key => $this->store->get($key, $moduleIdentifier)]
            )[$key];
            $this->state[$moduleIdentifier ?? '__GLOBAL__'][$key] = $value;
            $this->loadedKeys[$moduleIdentifier ?? '__GLOBAL__'][$key] = true;
        }

        return $this->state[$moduleIdentifier ?? '__GLOBAL__'][$key] ?? $default;
    }

    public function set(string $key, mixed $value, ?string $moduleIdentifier = null): void
    {
        $this->state[$moduleIdentifier ?? '__GLOBAL__'][$key] = $value;
        $this->loadedKeys[$moduleIdentifier ?? '__GLOBAL__'][$key] = true;

        $this->store->set($key, $value, $moduleIdentifier);
    }

    public function delete(string $key, ?string $moduleIdentifier = null): void
    {
        unset(
            $this->state[$moduleIdentifier ?? '__GLOBAL__'][$key],
            $this->loadedKeys[$moduleIdentifier ?? '__GLOBAL__'][$key]
        );

        $this->store->delete($key, $moduleIdentifier);
    }

    public function all(?string $moduleIdentifier = null): array
    {
        if ($this->fullyLoaded[$moduleIdentifier ?? '__GLOBAL__'] ?? false) {
            return $this->state[$moduleIdentifier ?? '__GLOBAL__'] ?? [];
        }

        $definition = $this->getDefinition($moduleIdentifier ?? '__GLOBAL__');
        $definedKeys = $definition->getDefinedKeys();
        if (empty($definedKeys)) {
            return [];
        }

        $results = $this->applyDefaults(
            moduleIdentifier: $moduleIdentifier,
            options: $this->store->all($moduleIdentifier),
            insertMissing: true
        );

        $this->state[$moduleIdentifier ?? '__GLOBAL__'] = array_merge(
            $this->state[$moduleIdentifier ?? '__GLOBAL__'] ?? [],
            $results
        );
        foreach ($results as $k => $_) {
            $this->loadedKeys[$moduleIdentifier ?? '__GLOBAL__'][$k] = true;
        }
        $this->fullyLoaded[$moduleIdentifier ?? '__GLOBAL__'] = true;

        return $this->state[$moduleIdentifier ?? '__GLOBAL__'] ?? [];
    }

    public function clear(?string $moduleIdentifier = null): void
    {
        $this->state = [];
        $this->loadedKeys = [];
        $this->fullyLoaded = [];

        $this->store->clear($moduleIdentifier);
    }

    /**
     * Inject default values into an options array according to the module definition.
     *
     * @param  array<string,mixed>  $options  The current options loaded from DB or state
     * @param  bool  $insertMissing  If true, missing defaults are inserted into state + marked as loaded
     */
    protected function applyDefaults(?string $moduleIdentifier, array $options, bool $insertMissing = false, bool $keepUnregistered = false): array
    {
        $definition = $this->getDefinition($moduleIdentifier ?? '__GLOBAL__');

        foreach ($definition->getDefinedKeys() as $key) {
            if (! array_key_exists($key, $options) && $insertMissing) {
                $options[$key] = $definition->getDefaultValue($key);
            }
            if (array_key_exists($key, $options) && $options[$key] === null) {
                $options[$key] = $definition->getDefaultValue($key);
            }
        }
        if ($keepUnregistered) {
            return $options;
        }

        return array_filter($options, fn ($_, $key) => $definition->has($key), ARRAY_FILTER_USE_BOTH);
    }

    protected function ensureAutoload(): void
    {
        if ($this->autoloaded) {
            return;
        }

        $globalKeys = []; // <- no global options at the moment
        $modulesKeys = [];
        foreach ($this->definitions as $moduleIdentifier => $definition) {
            $moduleAutoloads = $definition->getAutoloads();
            if (! empty($moduleAutoloads)) {
                $modulesKeys[$moduleIdentifier] = $moduleAutoloads;
            }
        }

        if (empty($modulesKeys) && empty($globalKeys)) {
            $this->autoloaded = true;

            return;
        }

        $results = $this->store->getMultiples(globalKeys: $globalKeys, modulesKeys: $modulesKeys);
        foreach ($results['global'] ?? [] as $key => $value) {
            $this->state['__GLOBAL__'][$key] = $value;
            $this->loadedKeys['__GLOBAL__'][$key] = true;
        }

        foreach ($results['modules'] ?? [] as $moduleIdentifier => $values) {
            foreach ($values as $key => $value) {
                $this->state[$moduleIdentifier][$key] = $value;
                $this->loadedKeys[$moduleIdentifier][$key] = true;
            }
        }

        $this->autoloaded = true;
    }
}
