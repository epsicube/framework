<?php

declare(strict_types=1);

namespace UniGale\Foundation\Contracts;

/**
 * Provides read/write access to option values within a defined scope.
 * Implementations manage values either globally or for a specific module.
 */
interface OptionsStore
{
    /**
     * Retrieve a single option value or return the default.
     *
     * @param  string  $key  The option key
     * @param  string|null  $moduleIdentifier  Optional module scope, null = global
     */
    public function get(string $key, ?string $moduleIdentifier = null): mixed;

    /**
     * Define or replace an option value.
     *
     * @param  string  $key  The option key
     * @param  mixed  $value  The value to set
     * @param  string|null  $moduleIdentifier  Optional module scope, null = global
     */
    public function set(string $key, mixed $value, ?string $moduleIdentifier = null): void;

    /**
     * Remove an option.
     *
     * @param  string  $key  The option key to remove
     * @param  string|null  $moduleIdentifier  Optional module scope, null = global
     */
    public function delete(string $key, ?string $moduleIdentifier = null): void;

    /**
     * Retrieve all option values for the current scope.
     *
     * @param  string|null  $moduleIdentifier  Optional module scope, null = global
     * @return array<string, mixed>
     */
    public function all(?string $moduleIdentifier = null): array;

    /**
     * Clear all options for the current scope.
     *
     * @param  string|null  $moduleIdentifier  Optional module scope, null = global
     */
    public function clear(?string $moduleIdentifier = null): void;

    /**
     * Retrieve multiple keys in bulk. (useful to preload items)
     *
     * @param  array<string>  $globalKeys  Keys to fetch from the global store
     * @param  array<string, array<string>>  $modulesKeys  Keys per module
     * @return array{
     *     global: array<string, mixed>,
     *     modules: array<string, array<string, mixed>>
     * }
     */
    public function getMultiples(array $globalKeys = [], array $modulesKeys = []): array;
}
