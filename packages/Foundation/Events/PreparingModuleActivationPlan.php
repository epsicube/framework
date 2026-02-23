<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Events;

use Closure;
use Epsicube\Support\Modules\Module;

class PreparingModuleActivationPlan
{
    /**
     * @var array<int, list<array{label: string, callback: Closure(): mixed, hidden: bool}>> Structure: [order => [ [label, callback, hidden], ... ]]
     */
    public array $tasks = [];

    public function __construct(public Module $module) {}

    public function addTask(string $label, Closure $callback, int $order = 0): self
    {
        $this->tasks[$order][] = [
            'label'    => $label,
            'callback' => $callback,
        ];

        return $this;
    }

    /**
     * @return list<array{label: string, callback: Closure(): mixed}>
     */
    public function getTasks(): array
    {
        if (empty($this->tasks)) {
            return [];
        }

        ksort($this->tasks);

        $allTasks = array_merge(...$this->tasks);

        return array_values($allTasks);
    }

    public function execute(): void
    {
        foreach ($this->getTasks() as $task) {
            app()->call($task['callback']);
        }
    }
}
