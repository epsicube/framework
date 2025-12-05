<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Concerns;

use EpsicubeModules\Hypercore\Foundation\HypercoreApplier;
use EpsicubeModules\Hypercore\Models\Tenant;

abstract class HypercoreAdapter
{
    abstract public function moduleIdentifier(): string;

    abstract public function configureCentral(HypercoreApplier $applier): void;

    abstract public function configureTenant(HypercoreApplier $applier, Tenant $tenant): void;
}
