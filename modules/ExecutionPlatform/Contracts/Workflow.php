<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Contracts;

use UniGale\Support\Contracts\HasLabel;
use UniGale\Support\Contracts\Registrable;

interface Workflow extends HasLabel, Registrable
{
    public function run(int $execution_id, array $input = []): mixed;
}
