<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

use Epsicube\Schemas\Exporters\JsonSchemaExporter;

interface JsonSchemaExportable
{
    public function toJsonSchema(JsonSchemaExporter $exporter): array;
}
