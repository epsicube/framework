<?php

declare(strict_types=1);

namespace UniGale\Foundation;

use Illuminate\Foundation\Application;
use UniGale\Foundation\Bootstrap\BootstrapUnigale;
use UniGale\Foundation\Traits\CleanupProvider;

class UnigaleApplication extends Application
{
    use CleanupProvider;

    public function bootstrapWith(array $bootstrappers): void
    {
        parent::bootstrapWith(array_merge([BootstrapUnigale::class], $bootstrappers));
    }

    /**
     * Append custom suffix to all cache file
     */
    public function setCacheSuffix(?string $prefix = null): void
    {
        // Use server to keep consistency during optimize that refresh the app
        $_SERVER['APP_CACHE_SUFFIX'] = $prefix;
    }

    public function bootstrapPath($path = ''): string
    {
        if (str_starts_with($path, 'cache') && ($_SERVER['APP_CACHE_SUFFIX'] ?? false)) {
            $path = preg_replace('/(\.[^.]+)$/', '-'.$_SERVER['APP_CACHE_SUFFIX'].'$1', $path, 1);
        }

        return parent::bootstrapPath($path);
    }

    protected function normalizeCachePath($key, $default): string
    {
        if (! ($_SERVER['APP_CACHE_SUFFIX'] ?? null)) {
            return parent::normalizeCachePath($key, $default);
        }

        return parent::normalizeCachePath("{$key}_{$_SERVER['APP_CACHE_SUFFIX']}", $default);
    }
}
