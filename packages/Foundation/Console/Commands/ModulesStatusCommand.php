<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;

use function Termwind\parse;

class ModulesStatusCommand extends Command
{
    protected $signature = 'modules:status';

    protected $aliases = ['m:s'];

    protected $description = 'Displays all available modules with their metadata and activation status.';

    public function handle(): int
    {
        $html = Blade::render(file_get_contents(__DIR__.'/ui/modules-status.blade.php'), [
            'modules' => Modules::all(),
        ]);

        $this->output->write($this->stripAnsi(parse($html)));

        return static::SUCCESS;
    }

    protected function stripAnsi(string $string): string
    {
        if (! $this->option('no-ansi')) {
            return $string;
        }

        return preg_replace('/(\x1b\[[0-9;]*[mK]|\x1b]8;;.*?\x1b\|\x1b]8;;.*?\x07)/', '', $string);
    }

    protected function gatherRequirements(Module $module): array
    {
        $results = $module->requirements->check();
        $conditions = $results['conditions']; // C'est un array d'objets Condition

        $groups = [];

        foreach ($conditions as $condition) {
            // On exécute la condition (ce qui remplit resultState et resultMessage)
            $state = $condition->run();
            $groupName = $condition->group();

            if (! isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'name'   => $groupName,
                    'checks' => [],
                ];
            }

            $groups[$groupName]['checks'][] = [
                'label'   => $condition->name(),
                'state'   => $state->value, // Utilise la valeur de l'Enum ConditionState
                'message' => $condition->getMessage(),
            ];
        }

        return array_values($groups);
    }
}
