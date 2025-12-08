<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

use Epsicube\Schemas\Exporters\FilamentExporter;
use Filament\Schemas\Components\Component;

interface FilamentExportable
{
    public function toFilamentComponent(string $name, FilamentExporter $exporter): Component;
}
