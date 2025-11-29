<?php

declare(strict_types=1);

namespace UniGale\Foundation\Options;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use UniGale\Foundation\Contracts\OptionsStore;
use UniGale\Foundation\Models\Option;

class DatabaseStore implements OptionsStore
{
    public function get(string $key, ?string $moduleIdentifier = null): mixed
    {
        return $this->withExceptionHandling(function () use ($key, $moduleIdentifier) {
            $query = Option::query()->where('key', $key);

            if ($moduleIdentifier === null) {
                $query->whereNull('module_identifier');
            } else {
                $query->where('module_identifier', $moduleIdentifier);
            }

            return $query->value('value') ?? null;
        });
    }

    /**
     * Set or update a value for a given key.
     */
    public function set(string $key, mixed $value, ?string $moduleIdentifier = null): void
    {
        Option::query()->updateOrCreate(
            ['key' => $key, 'module_identifier' => $moduleIdentifier],
            ['value' => $value, 'autoload' => $moduleIdentifier === null]
        );
    }

    /**
     * Delete an option.
     */
    public function delete(string $key, ?string $moduleIdentifier = null): void
    {
        Option::query()
            ->where('key', $key)
            ->when($moduleIdentifier, fn ($q) => $q->where('module_identifier', $moduleIdentifier))
            ->when(! $moduleIdentifier, fn ($q) => $q->whereNull('module_identifier'))
            ->delete();
    }

    /**
     * Get all options for a scope.
     *
     * @return array<string, mixed>
     */
    public function all(?string $moduleIdentifier = null): array
    {
        return $this->withExceptionHandling(fn () => Option::query()
            ->when($moduleIdentifier, fn ($q) => $q->where('module_identifier', $moduleIdentifier))
            ->when(! $moduleIdentifier, fn ($q) => $q->whereNull('module_identifier'))
            ->pluck('value', 'key')
            ->toArray()
        );
    }

    /**
     * Clear all options for a scope.
     */
    public function clear(?string $moduleIdentifier = null): void
    {
        Option::query()
            ->when($moduleIdentifier, fn ($q) => $q->where('module_identifier', $moduleIdentifier))
            ->when(! $moduleIdentifier, fn ($q) => $q->whereNull('module_identifier'))
            ->delete();
    }

    /**
     * Bulk retrieval for global and module-scoped keys.
     *
     * @param  array<string>  $globalKeys  Keys to fetch globally
     * @param  array<string, array<string>>  $modulesKeys  Keys per module
     * @return array{
     *     global: array<string, mixed>,
     *     modules: array<string, array<string, mixed>>
     * }
     */
    public function getMultiples(array $globalKeys = [], array $modulesKeys = []): array
    {
        $result = [
            'global'  => [],
            'modules' => [],
        ];

        if (empty($globalKeys) && empty($modulesKeys)) {
            return $result;
        }

        $query = Option::query()->where(0, '=', 1); // <- prevent loading all
        if (! empty($globalKeys)) {
            $query->orWhere(fn (Builder $q) => $q->whereNull('module_identifier')->whereIn('key', $globalKeys));
        }
        foreach ($modulesKeys as $moduleIdentifier => $keys) {
            if (empty($keys)) {
                continue;
            }
            $query->orWhere(fn (Builder $q) => $q->where('module_identifier', $moduleIdentifier)->whereIn('key', $keys));
        }

        foreach ($query->get(['key', 'value', 'module_identifier']) as $option) {
            if ($option->module_identifier === null) {
                $result['global'][$option->key] = $option->value;
            } else {
                $result['modules'][$option->module_identifier][$option->key] = $option->value;
            }
        }

        return $result;
    }

    private function withExceptionHandling(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (QueryException $e) {
            report($e);
        }

        return null;
    }
}
