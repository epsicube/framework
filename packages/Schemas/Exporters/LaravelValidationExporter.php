<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Exporters;

use Epsicube\Schemas\Contracts\SchemaExporter;
use Epsicube\Schemas\Schema;

class LaravelValidationExporter implements SchemaExporter
{
    public function __construct(protected ?array $data = null) {}

    public function exportSchema(Schema $schema): mixed
    {
        // TODO: Implement exportSchema() method.
    }
}
