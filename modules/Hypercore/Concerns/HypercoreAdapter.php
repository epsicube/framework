<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Concerns;

use UniGaleModules\Hypercore\Foundation\HypercoreApplier;
use UniGaleModules\Hypercore\Models\Tenant;

abstract class HypercoreAdapter
{
    abstract public function moduleIdentifier(): string;

    abstract public function configureCentral(HypercoreApplier $applier): void;

    abstract public function configureTenant(HypercoreApplier $applier, Tenant $tenant): void;
}
