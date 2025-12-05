<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Contracts;

interface HasInputSchema
{
    public function inputSchema(): array;
}
