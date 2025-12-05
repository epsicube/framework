<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Mcp\Helpers;

use EpsicubeModules\McpServer\Contracts\Tool as ToolContract;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

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

    public function schema(JsonSchema $schema): array
    {
        // TODO don't use provided (rewrite toArray)
        return $this->tool->inputSchema();
    }

    public function handle(Request $request): ResponseFactory
    {
        $inputs = $request->all();

        // TODO input validation using schema
        //        $validated = $request->validate([
        //            'location' => 'required|string|max:100',
        //            'units'    => 'in:celsius,fahrenheit',
        //        ]);
        Log::debug('calling', $inputs);
        $result = $this->tool->handle($inputs);
        Log::debug('result', $result);

        return Response::structured($result);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        // TODO don't use provided (rewrite toArray)
        return $this->tool->outputSchema();
    }
}
