<?php

declare(strict_types=1);

namespace UniGaleModules\McpServer\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;
use Laravel\Mcp\Server\Prompt;
use UniGale\Support\Facades\Options;
use UniGaleModules\McpServer\Contracts\Resource;
use UniGaleModules\McpServer\Contracts\Tool;
use UniGaleModules\McpServer\Facades\Resources;
use UniGaleModules\McpServer\Facades\Tools;
use UniGaleModules\McpServer\Mcp\Helpers\ResourceConverter;
use UniGaleModules\McpServer\Mcp\Helpers\ToolConverter;

class McpServer extends Server
{
    public function __construct(Transport $transport)
    {
        parent::__construct($transport);

        $this->tools = collect(Tools::all())
            ->map(fn (Tool $tool, string $identifier) => new ToolConverter($identifier, $tool))
            ->values()->all();
        $this->resources = collect(Resources::all())
            ->map(fn (Resource $resource, string $identifier) => new ResourceConverter($identifier, $resource))
            ->values()->all();

        $this->name = Options::get('core::mcp-server', 'name');
        $this->version = Options::get('core::mcp-server', 'version');
        $this->instructions = Options::get('core::mcp-server', 'instructions');
    }

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
