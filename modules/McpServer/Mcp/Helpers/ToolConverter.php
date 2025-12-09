<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Mcp\Helpers;

use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelValidationExporter;
use Epsicube\Schemas\Schema;
use EpsicubeModules\McpServer\Contracts\Tool as ToolContract;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Override;

class ToolConverter extends Tool
{
    public function __construct(protected string $identifier, protected ToolContract $tool) {}

    public function name(): string
    {
        return str($this->identifier)->replace(':', '-')->slug()->toString();
    }

    public function title(): string
    {
        return $this->tool->label();
    }

    public function description(): string
    {
        return $this->tool->description();
    }

    public function handle(Request $request): ResponseFactory
    {
        $inputs = $request->all();

        // TODO keep json schema in cache, or pre-computed
        $schema = Schema::create('_');
        $this->tool->inputSchema($schema);
        $rules = $schema->export(new LaravelValidationExporter($inputs));
        dd($rules);
        $request->validate($rules);

        Log::debug('calling', $inputs);
        $result = $this->tool->handle($inputs);
        Log::debug('result', $result);

        return Response::structured($result);
    }

    #[Override]
    public function toArray(): array
    {
        $annotations = $this->annotations();

        // Overrides schema resolving
        $schema = Schema::create($this->tool->identifier().'-input');
        $this->tool->inputSchema($schema);
        $schema = $schema->export(new JsonSchemaExporter);

        $outputSchema = Schema::create($this->tool->identifier().'-output');
        $this->tool->outputSchema($outputSchema);
        $outputSchema = $outputSchema->export(new JsonSchemaExporter);

        $schema['properties'] ??= (object) [];

        $result = [
            'name'        => $this->name(),
            'title'       => $this->title(),
            'description' => $this->description(),
            'inputSchema' => $schema,
            'annotations' => $annotations === [] ? (object) [] : $annotations,
        ];

        if (isset($outputSchema['properties'])) {
            $result['outputSchema'] = $outputSchema;
        }

        // @phpstan-ignore return.type
        return $this->mergeMeta($result);
    }
}
