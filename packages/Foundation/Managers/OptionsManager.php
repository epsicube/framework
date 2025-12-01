<?php

declare(strict_types=1);

namespace UniGale\Foundation\Managers;

use UniGale\Support\Contracts\OptionsStore;
use UniGale\Support\Exceptions\DefinitionNotFoundException;
use UniGale\Support\OptionsDefinition;

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

    public function registerDefinition(string $group, OptionsDefinition $definition): void
    {
        $this->definitions[$group] = $definition;
    }

    public function getDefinition(string $group): OptionsDefinition
    {
        if (! array_key_exists($group, $this->definitions)) {
            throw new DefinitionNotFoundException($group);
        }

        return $this->definitions[$group];
    }

    /**
     * @return array<string,OptionsDefinition>
     */
    public function definitions(): array
    {
        return $this->definitions;

    }

    public function get(string $group, string $key, bool $ignoreDefault = false): mixed
    {
        $this->ensureAutoload();

        if (! array_key_exists($key, $this->loadedKeys[$group] ?? [])) {
            $value = $this->store->get($key, $group);
            $this->state[$group][$key] = $value;
            $this->loadedKeys[$group][$key] = true;
        }
        if ($ignoreDefault) {
            return $this->state[$group][$key] ?? null;
        }

        return $this->applyDefaults(
            group: $group,
            options: [$key => $this->state[$group][$key] ?? null]
        )[$key];
    }

    public function set(string $group, string $key, mixed $value): void
    {
        $this->state[$group][$key] = $value;
        $this->loadedKeys[$group][$key] = true;

        $this->store->set($key, $value, $group);
    }

    public function delete(string $group, string $key): void
    {
        unset(
            $this->state[$group][$key],
            $this->loadedKeys[$group][$key]
        );

        $this->store->delete($key, $group);
    }

    public function all(string $group): array
    {
        if ($this->fullyLoaded[$group] ?? false) {
            return $this->state[$group] ?? [];
        }

        $results = $this->applyDefaults(group: $group, options: $this->store->all($group), insertMissing: true);

        $this->state[$group] = array_merge(
            $this->state[$group] ?? [],
            $results
        );
        foreach ($results as $k => $_) {
            $this->loadedKeys[$group][$k] = true;
        }
        $this->fullyLoaded[$group] = true;

        return $this->state[$group] ?? [];
    }

    /**
     * Inject default values into an options array according to the group definition.
     *
     * @param  array<string,mixed>  $options  The current options loaded from DB or state
     * @param  bool  $insertMissing  If true, missing defaults are inserted into state + marked as loaded
     */
    protected function applyDefaults(string $group, array $options, bool $insertMissing = false, bool $keepUnregistered = false): array
    {
        $definition = $this->getDefinition($group);

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
        foreach ($this->definitions as $group => $definition) {
            $autoloads = $definition->getAutoloads();
            if (! empty($autoloads)) {
                $groupedKeys[$group] = $autoloads;
            }
        }

        if (empty($groupedKeys)) {
            $this->autoloaded = true;

            return;
        }

        $results = $this->store->getMultiples($groupedKeys);
        foreach ($results as $group => $values) {
            foreach ($values as $key => $value) {
                $this->state[$group][$key] = $value;
                $this->loadedKeys[$group][$key] = true;
            }
        }

        $this->autoloaded = true;
    }
}
