<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Facades;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use UniGaleModules\Hypercore\Console\CacheCommand;

class Hypercore
{
    /**
     * TODO auto-recreate
     * Cache cannot be recreated when running "php artisan --tenant=x up"
     */
    public static function updateCache(bool $force = false): void
    {
        // On tenant context, command doesn't exists
        if ($_SERVER['hypercore::tenant'] ?? false) {
            if (File::exists(static::getCachePath())) {
                File::delete(static::getCachePath());
            }

            return;
        }

        // Only recreate when force or when exists
        if ($force || File::exists(static::getCachePath())) {
            Artisan::call(CacheCommand::class);
        }

    }

    public static function getCachePath(): string
    {
        // Force no suffix to ensure artisan --tenant=. up works
        $initial = $_SERVER['APP_CACHE_SUFFIX'] ?? null;
        try {
            $_SERVER['APP_CACHE_SUFFIX'] = null;

            return app()->bootstrapPath('cache/unigale-tenants.php');
        } finally {
            $_SERVER['APP_CACHE_SUFFIX'] = $initial;
        }
    }
}
