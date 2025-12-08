<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

use Epsicube\Schemas\Schema;

interface SchemaExporter
{
    public function exportSchema(Schema $schema): mixed;
}
