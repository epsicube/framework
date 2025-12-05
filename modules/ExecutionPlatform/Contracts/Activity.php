<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Contracts;

use Epsicube\Support\Contracts\HasLabel;
use Epsicube\Support\Contracts\Registrable;

interface Activity extends HasLabel, Registrable
{
    public function description(): string;

    public function inputSchema(): array;

    public function handle(array $inputs = []): ?array;

    public function outputSchema(): array;
}
