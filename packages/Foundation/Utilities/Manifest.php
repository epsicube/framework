<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Utilities;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Use composer to extract packages defined modules
 * "extra": {
 *     "epsicube": {
 *         "modules": ["YourPackage\\ModuleClass"]
 *     }
 * }
 */
class Manifest
{
    /** @var array The loaded manifest array */
    public array $manifest;

    public function __construct(protected Filesystem $files, protected string $vendorPath, protected string $manifestPath) {}

    public function config(string $key): array
    {
        return (new Collection($this->getManifest()))->flatMap(function ($configuration) use (&$key) {
            return (array) ($configuration[$key] ?? []);
        })->filter()->all();
    }

    protected function getManifest(): array
    {
        if (isset($this->manifest)) {
            return $this->manifest;
        }

        if ($this->files->exists($this->manifestPath)) {
            return $this->manifest = $this->files->getRequire($this->manifestPath);
        }

        return $this->manifest = $this->collectManifest();
    }

    /**
     * Build the manifest array from composer installed packages without writing to disk.
     */
    protected function collectManifest(): array
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);

            $packages = $installed['packages'] ?? $installed;
        }

        return (new Collection($packages))
            ->mapWithKeys(fn ($package) => [$package['name'] => $package['extra']['epsicube'] ?? []])
            ->filter()
            ->all();
    }

    /**
     * Explicitly generate and persist the cache file to disk.
     */
    public function generateCache(): void
    {
        if (! is_writable($dirname = dirname($this->manifestPath))) {
            throw new RuntimeException("The {$dirname} directory must be present and writable.");
        }
        $this->files->replace(
            $this->manifestPath, '<?php return '.var_export($this->collectManifest(), true).';'
        );
    }

    public function cleanGeneratedCache(): void
    {
        if ($this->files->exists($this->manifestPath) && $this->files->isWritable($this->manifestPath)) {
            $this->files->delete($this->manifestPath);
        }
    }
}
