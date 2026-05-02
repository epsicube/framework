<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\ExecutionPlatform\Fixtures;

use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;

final class ProbeActivity implements Activity
{
    public function identifier(): string
    {
        return 'tests::probe-activity';
    }

    public function label(): string
    {
        return 'Probe Activity';
    }

    public function description(): string
    {
        return 'Exercises Execution Platform registry and sync execution flow.';
    }

    public function inputSchema(Schema $schema): void
    {
        $schema->append([
            'payload' => StringProperty::make()
                ->title('Payload')
                ->optional()
                ->default('default-payload'),
        ]);
    }

    public function outputSchema(Schema $schema): void
    {
        $schema->append([
            'message' => StringProperty::make()->title('Message'),
        ]);
    }

    public function handle(array $inputs = []): ?array
    {
        return [
            'message' => mb_strtoupper((string) ($inputs['payload'] ?? '')),
        ];
    }
}
