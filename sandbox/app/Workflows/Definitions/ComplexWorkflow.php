<?php

declare(strict_types=1);

namespace App\Workflows\Definitions;

use App\Workflows\Activities\ChargeActivity;
use App\Workflows\Activities\FlakyActivity;
use App\Workflows\Activities\SimpleActivity;
use EpsicubeModules\ExecutionPlatform\Engine\RetryOptions;
use EpsicubeModules\ExecutionPlatform\Engine\Workflow;
use EpsicubeModules\ExecutionPlatform\Engine\WorkflowCancelException;
use Illuminate\Support\Str;
use RuntimeException;

class ComplexWorkflow extends Workflow
{
    public function run(array $input): mixed
    {
        // Exception failure simulation
        if (($input['id'] ?? '') === 'CASE-FAILURE') {
            throw new RuntimeException('Database disconnected (Simulated via exception)');
        }

        $results = [];

        try {
            // 1. Start with a SideEffect for a unique ID
            $internalId = $this->sideEffect(fn () => 'WF-'.mb_strtoupper(Str::random(4)));

            // 2. Wait for initialization
            $initData = $this->waitForSignal('init');
            $user = $initData['user'] ?? 'Unknown';

            // 3. Welcome activity
            $results['welcome'] = $this->executeActivity(SimpleActivity::class, ['msg' => "Welcome {$user} to workflow {$internalId}"]);

            // 4. Wait for branching configuration
            $config = $this->waitForSignal('config');
            $mode = $config['mode'] ?? 'standard';

            if ($mode === 'premium') {
                // Premium branch
                $results['mode_info'] = $this->executeActivity(SimpleActivity::class, ['msg' => "Mode PREMIUM activated for {$internalId}"]);

                // Several activities
                $results['charge'] = $this->executeActivity(ChargeActivity::class, ['amount' => 99]);

                // An activity that can fail (90% failure) - we set 10 attempts to be sure to pass
                $retryOptions = new RetryOptions(maxAttempts: 10);
                $results['flaky'] = $this->executeActivity(FlakyActivity::class, ['task' => 'Verification Premium'], $retryOptions);

            } else {
                // Standard branch
                $results['mode_info'] = $this->executeActivity(SimpleActivity::class, ['msg' => 'Mode STANDARD activated']);
                $results['charge'] = $this->executeActivity(ChargeActivity::class, ['amount' => 10]);

                // Wait for an additional confirmation signal
                $this->waitForSignal('confirm');
            }

            // 5. Final common step
            $results['final'] = $this->executeActivity(SimpleActivity::class, ['msg' => "Workflow finalization {$internalId}"]);

            // 6. Wait for completion
            $this->waitForSignal('finish');

            return [
                'id'         => $internalId,
                'status'     => 'SUCCESS',
                'mode'       => $mode,
                'activities' => $results,
            ];

        } catch (WorkflowCancelException $e) {
            // Cleanup in case of cancellation (can call a rollback activity)
            $reason = $e->getReason() ?? 'Unknown reason';
            $cleanupResult = $this->executeActivity(SimpleActivity::class, ['msg' => "Post-cancellation cleanup in progress... Reason: {$reason}"]);

            throw $e;
        }
    }
}
