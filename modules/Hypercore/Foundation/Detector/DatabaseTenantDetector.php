<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Foundation\Detector;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Throwable;
use UniGaleModules\Hypercore\Facades\Hypercore;
use UniGaleModules\Hypercore\Models\Tenant;

class DatabaseTenantDetector
{
    protected ?array $cache = null;

    protected bool $loadedFromCache = false;

    public function getDetectedTenant(Application $app): ?Tenant
    {
        $this->loadCache($app);

        try {
            return $app->runningInConsole()
                ? $this->detectConsoleTenant()
                : $this->detectHttpTenant();
        } catch (QueryException $e) {
            // catch for first boot without migrations
            report($e);

            return null;
        }
    }

    protected function loadCache(Application $app): void
    {
        if ($this->loadedFromCache) {
            return;
        }
        $file = Hypercore::getCachePath();
        if (! is_file($file)) {
            return;
        }

        try {
            $this->cache = require $file;

            // Rehydrate tenants
            $this->cache['tenants'] = array_map(function (array $attributes) {
                $t = (new Tenant);
                $t->exists = true;
                $t->setRawAttributes($attributes, true);

                return $t;
            }, $this->cache['tenants'] ?? []);
            $this->loadedFromCache = true;
        } catch (Throwable $e) {
            report($e);
            $this->cache = null;
        }
    }

    protected function detectConsoleTenant(): ?Tenant
    {
        $tenantIdentifier = $this->extractArg('--tenant');
        if (! $tenantIdentifier) {
            return null;
        }

        if ($this->loadedFromCache) {
            return $this->cache['tenants'][$tenantIdentifier]
                ?? throw (new ModelNotFoundException)->setModel(Tenant::class);
        }

        return Tenant::query()->where('identifier', $tenantIdentifier)->firstOrFail();
    }

    protected function detectHttpTenant(): ?Tenant
    {
        $request = Request::capture();
        if ($this->loadedFromCache) {
            $tenantIdentifier = null;
            foreach ($this->cache['patterns'] as $pattern => $identifier) {
                if (preg_match($pattern, $request->getUri())) {
                    $tenantIdentifier = $identifier;
                    break;
                }
            }

            return $tenantIdentifier ? ($this->cache['tenants'][$tenantIdentifier] ?? null) : null;
        }

        $path = mb_ltrim($request->path() ?: '', '/');

        return Tenant::query()
            ->where(fn ($q) => $q->where('scheme', $request->getScheme())->orWhereNull('scheme'))
            ->where(fn ($q) => $q->whereRaw(" ? LIKE REPLACE(domain, '*', '%')", [$request->host()]))
            ->when(! empty($path), function (Builder $q) use ($path) {
                $q->whereRaw("( ? LIKE CONCAT(path, '%') OR path IS NULL)", [$path]);
            })->when(empty($path), function (Builder $q) {
                $q->whereNull('path');
            })->first();
    }

    protected function extractArg(string $prefix): ?string
    {
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if (str_starts_with($arg, $prefix)) {
                return explode('=', $arg, 2)[1] ?? null;
            }
        }

        return null;
    }
}
