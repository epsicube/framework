<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Foundation\Events\PreparingModuleActivationPlan;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Module;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

class ModulesEnableCommand extends Command implements PromptsForMissingInput
{
    protected $signature = '
        modules:enable
        {identifier* : The identifier of the module to enable}
        {--force : Run without asking for confirmation}
    ';

    protected $aliases = ['m:e'];

    protected $description = 'Enable a module by its identifier';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'identifier' => fn () => multiselect(
                label: 'Which modules would you like to enable?',
                options: array_map(
                    fn (Module $module) => $module->identity->name,
                    array_filter(Modules::disabled(), fn (Module $module) => Modules::canBeEnabled($module))
                ),
                required: 'You must select at least one module'
            ),
        ];
    }

    public function handle(): int
    {
        $identifiers = (array) $this->argument('identifier');

        if (empty($identifiers)) {
            error('No module identifier provided.');

            return self::FAILURE;
        }

        /** @var PreparingModuleActivationPlan[] $plans */
        $plans = [];
        foreach ($identifiers as $identifier) {
            $module = Modules::get($identifier);
            $plans[$identifier] = Modules::activationPlan($module);
        }

        // Show plans
        $this->line('');
        $this->line('<fg=yellow;options=bold>Plan:</>');

        foreach ($plans as $id => $plan) {
            $tasks = $plan->getTasks();
            $this->line(" <fg=cyan;options=bold>[{$id}]</>");

            if (empty($tasks)) {
                $this->line('   <fg=gray>• No visible tasks</>');
            } else {
                foreach ($tasks as $task) {
                    $this->line("   <fg=gray>•</> {$task['label']}");
                }
            }
        }
        $this->line('');

        // Ask for confirmation
        if (! $this->option('force')) {
            if (! confirm('Proceed with these activation plans?', default: true)) {
                warning('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        // Execute
        foreach ($plans as $id => $plan) {
            try {
                $plan->execute();
                info("Module [{$id}] enabled.");
            } catch (Throwable $e) {
                error("Failed to enable [{$id}]: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
