<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Console;

use EpsicubeModules\Hypercore\Facades\Hypercore;
use EpsicubeModules\Hypercore\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LogicException;
use Throwable;

class CacheCommand extends Command
{
    protected $signature = 'epsicube-tenants:cache';

    protected $description = 'Create a cache file for faster tenants loading';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): void
    {
        $this->callSilent(ClearCommand::class);
        $tenants = $this->getFreshTenants();

        $cacheFilePath = Hypercore::getCachePath();
        $this->files->put(
            $cacheFilePath, '<?php return '.var_export($tenants, true).';'.PHP_EOL
        );
        try {
            require $cacheFilePath;
        } catch (Throwable $e) {
            $this->files->delete($cacheFilePath);
            throw new LogicException('Your tenant cache file is not serializable.', 0, $e);
        }

        $this->components->info('Tenants cached successfully.');
    }

    protected function getFreshTenants(): array
    {
        $tenants = Tenant::query()->get();

        return [
            'tenants'  => $tenants->mapWithKeys(fn (Tenant $t) => [$t->identifier => $t->getRawOriginal()])->all(),
            'patterns' => $tenants->reduce(function ($cache, Tenant $tenant) {

                $regexScheme = $tenant->scheme ? preg_quote($tenant->scheme, '#') : '.*';
                $regexDomain = str_replace('\*', '.*', preg_replace('#\\\\\*\.#', '(?:.*\.)?', preg_quote($tenant->domain ?? '*', '#')));
                $regexPath = $tenant->path ? preg_quote("/{$tenant->path}", '#') : '';

                $pattern = "#^{$regexScheme}://{$regexDomain}{$regexPath}(/.*)?$#";

                $cache[$pattern] = $tenant->identifier;

                return $cache;
            }, []),
        ];
    }
}
