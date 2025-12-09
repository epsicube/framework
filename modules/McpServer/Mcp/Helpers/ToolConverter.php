<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Mcp\Helpers;

use Epsicube\Schemas\Schema;
use EpsicubeModules\McpServer\Contracts\Tool as ToolContract;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Override;
use Throwable;

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

    public function handle(Request $request): ResponseFactory|Response
    {
        $schema = Schema::create('_');
        $this->tool->inputSchema($schema);
        $validated = $schema->validated($request->all());

        try {
            return Response::structured($this->tool->handle($validated));
        } catch (Throwable $e) {
            report($e);

            return Response::error($e->getMessage());
        }
    }

    #[Override]
    public function toArray(): array
    {
        $annotations = $this->annotations();

        // Overrides schema resolving
        $schema = Schema::create($this->tool->identifier().'-input');
        $this->tool->inputSchema($schema);

        $outputSchema = Schema::create($this->tool->identifier().'-output');
        $this->tool->outputSchema($outputSchema);

        $schema['properties'] ??= (object) [];

        $result = [
            'name'         => $this->name(),
            'title'        => $this->title(),
            'description'  => $this->description(),
            'inputSchema'  => $schema->toJsonSchema(),
            'outputSchema' => $schema->toJsonSchema(),
            'annotations'  => $annotations === [] ? (object) [] : $annotations,
        ];

        return $this->mergeMeta($result);
    }
}
