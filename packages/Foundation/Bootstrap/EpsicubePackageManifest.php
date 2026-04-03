<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Bootstrap;

use Illuminate\Foundation\PackageManifest;

class EpsicubePackageManifest extends PackageManifest
{
    protected array $exclusions = [];

    public function addExclusions(array $providers): void
    {
        $this->exclusions = array_merge($this->exclusions, $providers);
    }

    public function providers(): array
    {
        $initial = parent::providers();

        return array_values(array_diff($initial, $this->exclusions));
    }
}
