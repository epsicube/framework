<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules;

class Dependencies
{
    /** @var array<string, string> [module_id => semver_version] */
    public array $modules = [];

    public function module(string $identifier, string $version = '*'): static
    {
        $this->modules[$identifier] = $version;

        return $this;
    }
}
