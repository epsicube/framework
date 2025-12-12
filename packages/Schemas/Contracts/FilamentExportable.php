<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

use Epsicube\Schemas\Exporters\FilamentComponentsExporter;
use Filament\Schemas\Components\Component;

interface FilamentExportable
{
    public function toFilamentComponent(string $name, FilamentComponentsExporter $exporter): Component;
}
