<?php

declare(strict_types=1);

namespace UniGaleModules\McpServer\Contracts;

use UniGale\Support\Contracts\HasLabel;
use UniGale\Support\Contracts\Registrable;

interface Tool extends HasLabel, Registrable
{
    public function description(): string;

    public function inputSchema(): array;

    public function outputSchema(): array;

    public function handle(array $inputs = []): mixed;

    // TODO inputSchema -> schema
    // TODO outputSchema -> schema
}
