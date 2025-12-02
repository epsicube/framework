<?php

declare(strict_types=1);

namespace UniGaleModules\McpServer\Mcp\Helpers;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;
use UniGaleModules\McpServer\Contracts\Resource as ResourceContract;

class ResourceConverter extends Resource
{
    public function __construct(protected string $identifier, protected ResourceContract $resource) {}

    public function name(): string
    {
        return str($this->identifier)->replace(':', '-')->slug()->toString();
    }

    public function title(): string
    {
        return $this->resource->label();
    }

    public function description(): string
    {
        return $this->resource->description();
    }

    public function uri(): string
    {
        return 'file://resources/'.$this->identifier;
    }

    public function handle(): Response
    {
        $result = $this->resource->content();

        return Response::text($result);
    }

    public function mimeType(): string
    {
        return $this->resource->contentType();
    }
}
