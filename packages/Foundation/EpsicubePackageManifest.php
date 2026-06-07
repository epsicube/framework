<?php

declare(strict_types=1);

namespace Epsicube\Foundation;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\ServiceProvider;

class EpsicubePackageManifest extends PackageManifest
{
    protected array $exclusions = [];

    /**
     * @param  class-string<ServiceProvider>  ...$providers
     */
    public function addExclusions(string ...$providers): void
    {
        $this->exclusions = array_merge($this->exclusions, $providers);
    }

    public function providers(): array
    {
        $initial = parent::providers();

        return array_values(array_diff($initial, $this->exclusions));
    }
}
