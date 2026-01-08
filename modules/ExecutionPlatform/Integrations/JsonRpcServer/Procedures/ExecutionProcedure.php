<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\JsonRpcServer\Procedures;

use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\JsonRpcServer\Concerns\Procedure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExecutionProcedure extends Procedure
{
    public static string $name = 'execution';

    public function activity(Request $request): array
    {
        $validated = $request->validate([
            'identifier' => ['string', 'required', Rule::in(array_keys(Activities::all()))],
        ]);

        // Validating input schema
        $inputSchema = Schema::create('input', properties: [
            'params' => ObjectProperty::make()->optional()->properties(Activities::inputSchema($validated['identifier'])->properties()),
        ]);
        $validatedParams = data_get($inputSchema->validated($request->only('params')), 'params', []);
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
