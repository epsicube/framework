<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

use Epsicube\Schemas\Exporters\LaravelValidationExporter;

interface LaravelRulesExportable
{
    public function resolveValidationRules(mixed $value, LaravelValidationExporter $exporter): array;
}
