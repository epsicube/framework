<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Managers;

use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Schema;
use Epsicube\Schemas\Types\UndefinedValue;
use Epsicube\Support\Contracts\OptionsStore;
use Epsicube\Support\Exceptions\MissingRequiredOptionsException;
use Epsicube\Support\Exceptions\OptionNotRegisteredException;
use Epsicube\Support\Exceptions\SchemaNotFound;
use Epsicube\Support\Exceptions\UnresolvableOptionException;

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

    // TODO command use third option withDefault, change that by exposing store
    /**
     * Get an option value for a schema group and key.
     *
     * Loads the value from the store if not already loaded.
     * Returns the stored value if present (even if null),
     * otherwise falls back to the property default if defined.
     *
     * @throws OptionNotRegisteredException if the property is not defined in the schema
     * @throws UnresolvableOptionException if the property has no stored value and no default
     */
    public function get(string $group, string $key): mixed
    {
        if (! array_key_exists($key, $this->loadedKeys[$group] ?? [])) {
            $value = $this->store->get($key, $group);
            if (! $value instanceof UndefinedValue) {
                $this->state[$group][$key] = $value;
            }
            $this->loadedKeys[$group][$key] = true;
        }

        // Return stored value if exists (even if null)
        if (array_key_exists($key, $this->state[$group] ?? [])) {
            return $this->state[$group][$key];
        }

        // Retrieve property from schema
        $schema = $this->getSchema($group);

        if (! $property = $schema->property($key)) {
            throw OptionNotRegisteredException::forSchema($schema, $key);
        }

        // Fallback on default if defined
        if (! $property->hasDefault()) {
            throw UnresolvableOptionException::forSchema($schema, $key);
        }

        return $property->getDefault();
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

    /**
     * Retrieve all options for a group, applying defaults and enforcing required fields.
     *
     * @return array<string, mixed>
     *
     * @throws MissingRequiredOptionsException if any required property is missing
     */
    public function all(string $group): array
    {
        $schema = $this->getSchema($group);

        // Required keys
        $requiredKeys = array_keys(array_filter(
            $schema->properties(),
            fn (Property $property) => !$property->isOptional()
        ));

        $defaults = array_map(
            fn (Property $property) => $property->getDefault(),
            array_filter($schema->properties(), fn (Property $property) => $property->hasDefault())
        );

        $stored = array_filter(
            $this->store->all($group),
            fn (mixed $v) => ! ($v instanceof UndefinedValue)
        );

        $all = array_merge($defaults, $stored);
        $missingRequired = array_diff($requiredKeys, array_keys($all));
        if (! empty($missingRequired)) {
            throw MissingRequiredOptionsException::forSchema($schema, $missingRequired);
        }

        // Merge into existing state and loadedKeys
        $this->state[$group] = array_merge($this->state[$group] ?? [], $all);
        $this->loadedKeys[$group] = array_replace(
            $this->loadedKeys[$group] ?? [],
            array_fill_keys(array_keys($all), true)
        );
        $this->fullyLoaded[$group] = true;

        return $all;
    }

    // Expose store
    public function store(): OptionsStore
    {
        return $this->store;
    }
}
