<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Contracts;

use UniGale\Foundation\Contracts\HasLabel;
use UniGale\Foundation\Contracts\Registrable;

interface Activity extends HasLabel, Registrable
{
    public function handle(array $inputs = []): ?array;
}
