<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Workflows\Definitions\ComplexWorkflow;
use EpsicubeModules\ExecutionPlatform\Engine\WorkflowEngine;
use EpsicubeModules\ExecutionPlatform\Engine\WorkflowStatus;
use EpsicubeModules\ExecutionPlatform\Engine\WorkflowStub;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestAsync extends Command
{
    protected $signature = 'app:test-async';

    protected $description = 'Multi-file Workflow Engine with deterministic replay';

    public function handle(): void
    {
        $engine = app(WorkflowEngine::class);
        $engine->setOutput($this->output);
        $engine->cleanup();

        $this->info('🚀 Launching Workflow Engine (Queue & DB Architecture)');
        $this->info('Scenarios : PREMIUM, STANDARD, CANCEL, FAILURE');
        $this->info('NOTE: Make sure to run "php artisan queue:listen" in another terminal.');

        $ids = ['CASE-PREMIUM', 'CASE-STANDARD', 'CASE-CANCEL', 'CASE-FAILURE'];
        $stubs = [];

        // 1. START
        foreach ($ids as $id) {
            $stubs[$id] = WorkflowStub::start(ComplexWorkflow::class, ['id' => $id], $id);
        }

        // 2. SIGNAL : INITIALIZATION
        $this->warn("\n📢 ACTIONS : Initialization");
        $stubs['CASE-PREMIUM']->signal('init', ['user' => 'Alan (Admin)']);
        $stubs['CASE-STANDARD']->signal('init', ['user' => 'Bob (Free)']);
        $stubs['CASE-CANCEL']->signal('init', ['user' => 'Charlie (Cancelled)']);
        $stubs['CASE-FAILURE']->signal('init', ['user' => 'Dave (Failure)']);

        // 2.5 EARLY CANCELLATION FOR TESTING
        $this->error("\n📢 ACTIONS : Preventive cancellation of CASE-CANCEL");
        $stubs['CASE-CANCEL']->cancel('Cancellation reason test');

        // 3. SIGNAL : CONFIGURATION
        $this->warn("\n📢 ACTIONS : Configuration");
        $stubs['CASE-PREMIUM']->signal('config', ['mode' => 'premium']);
        $stubs['CASE-STANDARD']->signal('config', ['mode' => 'standard']);
        $stubs['CASE-CANCEL']->signal('config', ['mode' => 'premium']);
        $stubs['CASE-FAILURE']->signal('config', ['mode' => 'standard']);

        // 4. DIVERGENCE
        $this->warn("\n📢 ACTIONS : Divergence");
        $this->info(' -> Confirmation of CASE-STANDARD');
        $stubs['CASE-STANDARD']->signal('confirm');

        // 5. TERMINATION
        $this->warn("\n📢 ACTIONS : Termination");
        $stubs['CASE-PREMIUM']->signal('finish');
        $stubs['CASE-STANDARD']->signal('finish');

        $this->info("\n⏳ Waiting for workflows to complete...");

        $anyRunning = true;
        while ($anyRunning) {
            $anyRunning = false;
            foreach ($ids as $id) {
                $stub = WorkflowStub::load($id);
                if ($stub && $stub->processing()) {
                    $anyRunning = true;
                    break;
                }
            }
            if ($anyRunning) {
                usleep(100000); // 100ms
            }
        }

        $this->info("\n✨ Simulation finished.");
        $this->displayTable($ids);
    }

    private function displayTable(array $ids): void
    {
        $rows = [];
        foreach ($ids as $id) {
            $w = WorkflowStub::load($id);
            if ($w) {
                $rows[] = [
                    $id,
                    $this->formatStatus($w->status),
                    count($w->history),
                    Str::limit(is_array($w->result) ? json_encode($w->result) : (string) $w->result, 60),
                ];
            }
        }
        $this->table(['ID', 'Status', 'Events', 'Result'], $rows);
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            WorkflowStatus::COMPLETED => '<fg=green>COMPLETED</>',
            WorkflowStatus::FAILED    => '<fg=red>FAILED</>',
            WorkflowStatus::CANCELLED => '<fg=yellow>CANCELLED</>',
            WorkflowStatus::RUNNING   => '<fg=blue>RUNNING</>',
            default                   => $status
        };
    }
}
