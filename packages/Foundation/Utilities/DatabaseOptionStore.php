<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Utilities;

use Epsicube\Foundation\Models\Option;
use Epsicube\Schemas\Types\UndefinedValue;
use Epsicube\Support\Contracts\OptionsStore;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DatabaseOptionStore implements OptionsStore
{
    /**
     * {@inheritDoc}
     */
    public function get(string $key, string $group): mixed
    {
        try {
            return Option::query()->where('group', $group)->where('key', $key)->soleValue('value');
        } catch (ModelNotFoundException) {
            return new UndefinedValue;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, string $group): void
    {
        // Value null are stored as empty string (laravel json cast behaviour)
        Option::query()->updateOrCreate(
            ['key' => $key, 'group' => $group],
            ['value' => $value]
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
        return Option::query()
            ->where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function clear(string $group): void
    {
        Option::query()->where('group', $group)->delete();
    }
}
