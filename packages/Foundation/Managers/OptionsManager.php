<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Managers;

use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\OptionsStore;
use Epsicube\Support\Exceptions\SchemaNotFound;

class OptionsManager
{
    /** @var array<string, Schema> */
    protected array $schemas = [];

    /** @var array<string,mixed> */
    protected array $state = [];

    /** @var array<string,array<string>> */
    protected array $loadedKeys = [];

    /** @var array<string,true> */
    protected array $fullyLoaded = [];

    public function __construct(protected OptionsStore $store) {}

    public function registerSchema(Schema $schema): void
    {
        $this->schemas[$schema->identifier()] = $schema;
    }

    public function getSchema(string $group): Schema
    {
        if (! isset($this->schemas[$group])) {
            throw new SchemaNotFound($group);
        }

        return $this->schemas[$group];
    }

    /**
     * @return array<string,Schema>
     */
    public function schemas(): array
    {
        return $this->schemas;
    }

    public function get(string $group, string $key, bool $ignoreDefault = false): mixed
    {
        if (! array_key_exists($key, $this->loadedKeys[$group] ?? [])) {
            $value = $this->store->get($key, $group);
            $this->state[$group][$key] = $value;
            $this->loadedKeys[$group][$key] = true;
        }
        if ($ignoreDefault) {
            return $this->state[$group][$key] ?? null;
        }

        return $this->getSchema($group)->withDefaults([$key => $this->state[$group][$key] ?? null])[$key];
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

        $results = $this->getSchema($group)->withDefaults(values: $this->store->all($group), insertMissing: true);

        $this->state[$group] = array_merge($this->state[$group] ?? [], $results);

        $this->loadedKeys[$group] = array_replace(
            $this->loadedKeys[$group] ?? [],
            array_fill_keys(array_keys($results), true)
        );

        $this->fullyLoaded[$group] = true;

        return $this->state[$group] ?? [];
    }

    public function allStored(string $group): array
    {
        return $this->store->all($group);
    }
}
