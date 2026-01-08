<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Contracts;

use Closure;
use Epsicube\Schemas\Properties\IntegerProperty;
use Epsicube\Schemas\Schema;
use Event;

abstract class Trigger
{
    public $type = 'hydro_trigger';

    public function __construct(protected \UniDeal\ModuleHydro\Models\Trigger $trigger) {}

    public function listen(Closure $resolve): void
    {
        // Or use polling
        Event::listen("hydro-trigger-created::{$this->trigger->id}", function ($event) use (&$resolve) {
            $resolve($event->data);
        });
    }

    public function close(): void
    {
        // Remove event listener
    }

    // For manual starting
    public function schema(Schema $schema): void
    {
        $schema->append([
            'trigger_id' => IntegerProperty::make(),
        ]);
    }
}
