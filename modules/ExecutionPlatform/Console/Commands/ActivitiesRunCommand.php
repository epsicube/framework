<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Console\Commands;

use Carbon\CarbonInterval;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

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
        $input = $inputSchema->export(new LaravelPromptsFormExporter);

        $startedAt = microtime(true);
        $result = spin(
            fn () => Activities::run($identifier, $input),
            sprintf("Running activity '%s'", $activity->label())
        );

        $duration = microtime(true) - $startedAt;
        $interval = CarbonInterval::microseconds($duration * 1_000_000)->cascade();

        info(sprintf('Activity completed in %s.', $interval->forHumans([
            'minimumUnit' => 'millisecond',
            'maximumUnit' => 'minute',
            'short'       => true,
        ])));

        note("Raw JSON Output\n\n".json_encode($result, JSON_PRETTY_PRINT));

        return self::SUCCESS;
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
