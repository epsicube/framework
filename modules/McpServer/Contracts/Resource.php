<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Contracts;

use Epsicube\Support\Contracts\HasLabel;
use Epsicube\Support\Contracts\Registrable;

interface Resource extends HasLabel, Registrable
{
    public function description(): string;

    public function content(): string;

    public function contentType(): string;
}
