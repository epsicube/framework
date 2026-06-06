<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\McpServer\Fixtures;

use EpsicubeModules\McpServer\Contracts\Resource;

class TestResource implements Resource
{
    public function identifier(): string
    {
        return 'tests::ops-handbook';
    }

    public function label(): string
    {
        return 'Ops Handbook';
    }

    public function description(): string
    {
        return 'Operational instructions for internal agents.';
    }

    public function content(): string
    {
        return "# Ops Handbook\n\nUse the internal runbooks first.";
    }

    public function contentType(): string
    {
        return 'text/markdown';
    }
}
