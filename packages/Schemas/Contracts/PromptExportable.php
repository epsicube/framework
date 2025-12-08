<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;

interface PromptExportable
{
    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): mixed;
}
