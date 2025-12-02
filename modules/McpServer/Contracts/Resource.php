<?php

declare(strict_types=1);

namespace UniGaleModules\McpServer\Contracts;

use UniGale\Support\Contracts\HasLabel;
use UniGale\Support\Contracts\Registrable;

interface Resource extends HasLabel, Registrable
{
    public function description(): string;

    public function content(): mixed;

    public function contentType(): string;
}
