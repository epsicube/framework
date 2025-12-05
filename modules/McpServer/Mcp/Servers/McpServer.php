<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Mcp\Servers;

use Epsicube\Support\Facades\Options;
use EpsicubeModules\McpServer\Contracts\Resource;
use EpsicubeModules\McpServer\Contracts\Tool;
use EpsicubeModules\McpServer\Facades\Resources;
use EpsicubeModules\McpServer\Facades\Tools;
use EpsicubeModules\McpServer\Mcp\Helpers\ResourceConverter;
use EpsicubeModules\McpServer\Mcp\Helpers\ToolConverter;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;
use Laravel\Mcp\Server\Prompt;

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
