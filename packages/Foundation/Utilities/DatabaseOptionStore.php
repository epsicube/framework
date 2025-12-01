<?php

declare(strict_types=1);

namespace UniGale\Foundation\Utilities;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use UniGale\Foundation\Models\Option;
use UniGale\Support\Contracts\OptionsStore;

class DatabaseOptionStore implements OptionsStore
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
     * {@inheritDoc}
     */
    public function getMultiples(array $groupedKeys = []): array
    {
        $result = [];

        if (empty($globalKeys) && empty($modulesKeys)) {
            return $result;
        }

        $query = Option::query()->where(0, '=', 1); // <- prevent loading all

        foreach ($groupedKeys as $moduleIdentifier => $moduleKeys) {
            if (empty($moduleKeys)) {
                continue;
            }
            $query->orWhere(fn (Builder $q) => $q->where('module_identifier', $moduleIdentifier)->whereIn('key', $moduleKeys));
        }

        foreach ($query->get(['key', 'value', 'module_identifier']) as $option) {
            $result[$option->module_identifier][$option->key] = $option->value;
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
