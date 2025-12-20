<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Console\Commands;

use Carbon\CarbonInterval;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class ActivitiesRunCommand extends Command implements PromptsForMissingInput
{
    protected $signature = 'activities:run {identifier}';

    protected $aliases = ['a:r'];

    protected $description = 'Run specific activity registered in Execution Platform';

    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $activity = Activities::safeGet($identifier);
        if (! $activity) {
            error(sprintf("Activity '%s' not found.", $identifier));

            return self::FAILURE;
        }

        $inputSchema = Activities::inputSchema($identifier);
        $input = $inputSchema->toExecutedPrompts();

        try {
            /** @var Execution $execution */
            $execution = spin(
                fn () => Activities::run($identifier, $input),
                sprintf("Running activity '%s'", $activity->label())
            );

            info(sprintf('Activity completed in %s.', CarbonInterval::microsecond($execution->execution_time_ns / 1_000)->cascade()->forHumans([
                'minimumUnit' => 'microsecond',
                'maximumUnit' => 'hour',
                'short'       => true,
                'parts'       => 2,
            ])));

            note("Raw JSON Output\n\n".json_encode($execution->output ?? [], JSON_PRETTY_PRINT));

            return self::SUCCESS;

        } catch (Throwable $e) {
            report($e);
            error('Activity failed.');
            note("Error Details:\n<fg=red>{$e->getMessage()}</>");

            return self::FAILURE;
        }
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'identifier' => fn () => select(
                label: 'Which activity would you like to run?',
                options: array_map(fn (Activity $activity) => $activity->label(), Activities::all()),
                required: 'You must select at least one activity.'
            ),
        ];
    }
}
