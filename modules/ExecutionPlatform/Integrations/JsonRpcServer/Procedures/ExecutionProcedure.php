<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\JsonRpcServer\Procedures;

use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\JsonRpcServer\Concerns\Procedure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExecutionProcedure extends Procedure
{
    public static string $name = 'execution';

    public function listActivities(): array
    {
        return collect(Activities::all())->map(fn (Activity $activity, string $identifier) => [
            'identifier'    => $identifier,
            'name'          => $activity->label(),
            'description'   => $activity->description(),
            'input_schema'  => Activities::inputSchema($identifier)->toJsonSchema(),
            'output_schema' => Activities::outputSchema($identifier)->toJsonSchema(),
        ])->values()->all();
    }

    public function callActivity(Request $request): array
    {
        $validated = $request->validate([
            'identifier' => ['string', 'required', Rule::in(array_keys(Activities::all()))],
        ]);

        // Validating input schema
        $inputSchema = Schema::create('input', properties: [
            'input' => ObjectProperty::make()->optional()->properties(Activities::inputSchema($validated['identifier'])->properties()),
        ]);
        $validatedParams = data_get($inputSchema->validated($request->only('input')), 'input', []);
        $execution = Activities::run($validated['identifier'], $validatedParams);

        return $execution->only([
            'execution_type',
            'target',
            'started_at',
            'completed_at',
            'memory_used_bytes',
            'execution_time_ns',
            'output',
        ]);

    }
}
