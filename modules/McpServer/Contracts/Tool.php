<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Contracts;

use Epsicube\Support\Contracts\HasLabel;
use Epsicube\Support\Contracts\Registrable;

interface Tool extends HasLabel, Registrable
{
    public function description(): string;

    public function inputSchema(): array;

    public function outputSchema(): array;

    public function handle(array $input = []): mixed;
}
