<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Contracts;

use Epsicube\Support\Contracts\HasLabel;
use Epsicube\Support\Contracts\Registrable;

interface Workflow extends HasLabel, Registrable
{
    public function run(int $execution_id, array $input = []): mixed;
}
