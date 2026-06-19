<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Utilities;

use Epsicube\Foundation\Models\Option;
use Epsicube\Schemas\Types\UndefinedValue;
use Epsicube\Support\Contracts\OptionsStore;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseOptionStore implements OptionsStore
{
    public function isFunctional(): bool
    {
        try {
            $m = new Option;

            return DB::connection($m->getConnectionName())
                ->getSchemaBuilder()
                ->hasTable($m->getTable());
        } catch (Throwable) {
            return false;
        } finally {
            unset($m);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, string $group): mixed
    {
        try {
            return Option::query()
                ->where('group', $group)
                ->where('key', $key)
                ->soleValue('value');
        } catch (ModelNotFoundException) {
            return new UndefinedValue;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, string $group): void
    {
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
