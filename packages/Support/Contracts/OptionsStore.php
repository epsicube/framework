<?php

declare(strict_types=1);

namespace UniGale\Support\Contracts;

interface OptionsStore
{
    /**
     * Retrieve a single option value or return the default.
     */
    public function get(string $key, string $group): mixed;

    /**
     * Define or replace an option value.
     */
    public function set(string $key, mixed $value, string $group): void;

    /**
     * Remove an option
     */
    public function delete(string $key, string $group): void;

    /**
     * Retrieve all option values for the specific group.
     *
     * @return array<string, mixed>
     */
    public function all(string $group): array;

    /**
     * Clear all options for the specified group.
     */
    public function clear(string $group): void;
}
