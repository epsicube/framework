<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\McpServer\Fixtures;

use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\McpServer\Contracts\Tool;

class TestTool implements Tool
{
    public function identifier(): string
    {
        return 'tests::server-health';
    }

    public function label(): string
    {
        return 'Server Health';
    }

    public function description(): string
    {
        return 'Returns the current server health summary.';
    }

    public function inputSchema(Schema $schema): void
    {
        $schema->append([
            'scope' => StringProperty::make()
                ->title('Scope')
                ->optional()
                ->default('global'),
        ]);
    }

    public function outputSchema(Schema $schema): void
    {
        $schema->append([
            'status' => StringProperty::make()->title('Status'),
            'scope'  => StringProperty::make()->title('Scope'),
        ]);
    }

    public function handle(array $input = []): mixed
    {
        return [
            'status' => 'ok',
            'scope'  => $input['scope'] ?? 'global',
        ];
    }
}
