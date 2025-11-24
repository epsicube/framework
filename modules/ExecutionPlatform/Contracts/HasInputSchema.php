<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Contracts;

interface HasInputSchema
{
    public function inputSchema(): array;
}
