<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Registries;

use Epsicube\Schemas\Schema;
use Epsicube\Support\Registry;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;

/**
 * @extends Registry<Activity>
 */
class ActivitiesRegistry extends Registry
{
    public function getRegistrableType(): string
    {
        return Activity::class;
    }

    public function inputSchema(string $identifier)
    {
        $activity = $this->get($identifier);
        $schema = Schema::create($identifier.'-input', 'Input Schema for: '.$activity->label());
        $activity->inputSchema($schema);

        return $schema;
    }

    public function outputSchema(string $identifier)
    {
        $activity = $this->get($identifier);
        $schema = Schema::create($identifier.'-output', 'Output Schema for: '.$activity->label());
        $activity->inputSchema($schema);

        return $schema;
    }

    public function run(string $identifier, array $input = []): ?array
    {
        $activity = $this->get($identifier);
        $schema = $this->inputSchema($identifier);
        $validated = $schema->validated($input);
        return $activity->handle($validated);
    }
}
