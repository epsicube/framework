<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Registries;

use Epsicube\Schemas\Schema;
use Epsicube\Support\Registry;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Enum\EventStatus;
use EpsicubeModules\ExecutionPlatform\Enum\EventType;
use EpsicubeModules\ExecutionPlatform\Models\ExecutionEvent;
use Throwable;

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
        $schema = $this->inputSchema($identifier);
        $validated = $schema->validated($input);

        $event = new ExecutionEvent([
            'activity_type' => $identifier,
            'event_type'    => EventType::ACTIVITY,
            'tries'         => 1,
            'input'         => $validated,
            'status'        => EventStatus::QUEUED,
        ]);
        $event->save();

        try {
            $event->run();
        } catch (Throwable $e) {
            report($e);
            throw $e;
        }

        $event->refresh();

        return $event->output;
    }
}
