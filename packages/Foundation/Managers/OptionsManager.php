<?php

declare(strict_types=1);

namespace UniGale\Foundation\Managers;

use UniGale\Support\Contracts\Module;
use UniGale\Support\Contracts\OptionsStore;
use UniGale\Support\Exceptions\DefinitionNotFoundException;
use UniGale\Support\OptionsDefinition;

class OptionsManager
{
    /** @var array<string, OptionsDefinition> */
    protected array $moduleDefinitions = [];

    /** @var array<string,mixed> */
    protected array $state = [];

    /** @var array<string,array<string>> */
    protected array $loadedKeys = [];

    /** @var array<string,true> */
    protected array $fullyLoaded = [];

    protected bool $autoloaded = false;

    public function __construct(protected OptionsStore $store) {}

    public function registerDefinition(string $moduleIdentifier, OptionsDefinition $definition): void
    {
        $this->moduleDefinitions[$moduleIdentifier] = $definition;
    }

    public function getDefinition(string $moduleIdentifier): OptionsDefinition
    {
        if (! array_key_exists($moduleIdentifier, $this->moduleDefinitions)) {
            throw new DefinitionNotFoundException($moduleIdentifier);
        }

        return $this->moduleDefinitions[$moduleIdentifier];
    }

    /**
     * @return array<string,OptionsDefinition>
     */
    public function definitions(): array
    {
        return $this->moduleDefinitions;

    }

    public function get(string $key, ?string $moduleIdentifier = null, bool $ignoreDefault = false): mixed
    {
        $this->ensureAutoload();

        if (! array_key_exists($key, $this->loadedKeys[$moduleIdentifier] ?? [])) {
            $value = $this->store->get($key, $moduleIdentifier);
            $this->state[$moduleIdentifier][$key] = $value;
            $this->loadedKeys[$moduleIdentifier][$key] = true;
        }
        if ($ignoreDefault) {
            return $this->state[$moduleIdentifier][$key] ?? null;
        }

        return $this->applyDefaults(
            moduleIdentifier: $moduleIdentifier,
            options: [$key => $this->state[$moduleIdentifier][$key] ?? null]
        )[$key];
    }

    public function set(string $key, mixed $value, ?string $moduleIdentifier = null): void
    {
        $this->state[$moduleIdentifier][$key] = $value;
        $this->loadedKeys[$moduleIdentifier][$key] = true;

        $this->store->set($key, $value, $moduleIdentifier);
    }

    public function delete(string $key, ?string $moduleIdentifier = null): void
    {
        unset(
            $this->state[$moduleIdentifier][$key],
            $this->loadedKeys[$moduleIdentifier][$key]
        );

        $this->store->delete($key, $moduleIdentifier);
    }

    public function all(?string $moduleIdentifier = null): array
    {
        if ($this->fullyLoaded[$moduleIdentifier] ?? false) {
            return $this->state[$moduleIdentifier] ?? [];
        }

        $results = $this->applyDefaults(
            moduleIdentifier: $moduleIdentifier,
            options: $this->store->all($moduleIdentifier),
            insertMissing: true
        );

        $this->state[$moduleIdentifier] = array_merge(
            $this->state[$moduleIdentifier] ?? [],
            $results
        );
        foreach ($results as $k => $_) {
            $this->loadedKeys[$moduleIdentifier][$k] = true;
        }
        $this->fullyLoaded[$moduleIdentifier] = true;

        return $this->state[$moduleIdentifier] ?? [];
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
        $definition = $this->getDefinition($moduleIdentifier);

        foreach (array_keys($definition->all()) as $key) {
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

        $groupedKeys = [];
        foreach ($this->moduleDefinitions as $moduleIdentifier => $definition) {
            $moduleAutoloads = $definition->getAutoloads();
            if (! empty($moduleAutoloads)) {
                $groupedKeys[$moduleIdentifier] = $moduleAutoloads;
            }
        }

        if (empty($groupedKeys)) {
            $this->autoloaded = true;

            return;
        }

        $results = $this->store->getMultiples($groupedKeys);
        foreach ($results as $moduleIdentifier => $values) {
            foreach ($values as $key => $value) {
                $this->state[$moduleIdentifier][$key] = $value;
                $this->loadedKeys[$moduleIdentifier][$key] = true;
            }
        }

        $this->autoloaded = true;
    }
}
