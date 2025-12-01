<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Contracts;

use UniGale\Support\Contracts\HasLabel;
use UniGale\Support\Contracts\Registrable;

interface Activity extends HasLabel, Registrable
{
    public function handle(array $inputs = []): ?array;
}
