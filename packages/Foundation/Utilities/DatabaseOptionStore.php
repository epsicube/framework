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
    /**
     * {@inheritDoc}
     */
    public function get(string $key, string $group): mixed
    {
        return $this->withExceptionHandling(fn () => Option::query()
            ->where('group', $group)
            ->where('key', $key)
            ->value('value') ?? null
        );
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, string $group): void
    {
        Option::query()->updateOrCreate(
            ['key' => $key, 'group' => $group],
            ['value' => $value, 'autoload' => true] // <- todo autoload from definition
        );
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key, string $group): void
    {
        Option::query()
            ->where('group', $group)
            ->where('key', $key)
            ->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function all(string $group): array
    {
        return $this->withExceptionHandling(fn () => Option::query()
            ->where('group', $group)
            ->pluck('value', 'key')
            ->toArray()
        ) ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function clear(string $group): void
    {
        Option::query()->where('group', $group)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiples(array $groupedKeys = []): array
    {
        if (empty($groupedKeys)) {
            return [];
        }

        $query = Option::query()->where(0, '=', 1); // <- prevent loading all

        foreach ($groupedKeys as $group => $keys) {
            if (empty($keys)) {
                continue;
            }
            $query->orWhere(fn (Builder $q) => $q->where('group', $group)->whereIn('key', $keys));
        }

        $result = [];
        foreach ($query->get(['key', 'value', 'group']) as $option) {
            $result[$option->group][$option->key] = $option->value;
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
