<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

use Epsicube\Schemas\Exporters\LaravelValidatorExporter;

interface LaravelRulesExportable
{
    public function resolveValidationRules(mixed $value, LaravelValidatorExporter $exporter): array;
}
