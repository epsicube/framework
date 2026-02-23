<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Listeners;

use Epsicube\Foundation\Events\PreparingModuleActivationPlan;
use Epsicube\Foundation\Events\PreparingModuleDeactivationPlan;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

use function Illuminate\Support\artisan_binary;
use function Illuminate\Support\php_binary;

class FoundationSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(PreparingModuleActivationPlan::class, function (PreparingModuleActivationPlan $plan) {

            $plan->addTask(__('Clear cache'), $this->clearCache(...));
            $plan->addTask(__('Run migrations'), $this->runMigrations(...));
            if (app()->isProduction() || app()->routesAreCached()) {
                $plan->addTask(__('Generate cache'), $this->generateCache(...));
            }
            $plan->addTask(__('Terminate worker'), $this->terminateWorker(...));

            // TODO per-module migrations
        });

        $events->listen(PreparingModuleDeactivationPlan::class, function (PreparingModuleDeactivationPlan $plan) {
            $plan->addTask(__('Clear cache'), $this->clearCache(...));
            if (app()->isProduction() || app()->routesAreCached()) {
                $plan->addTask(__('Generate cache'), $this->generateCache(...));
            }
            $plan->addTask(__('Terminate worker'), $this->terminateWorker(...));

            // TODO rollback migration module
        });

    }

    protected function clearCache(): void
    {
        $process = $this->callArtisanCommand('optimize:clear');
        if (! $process->successful()) {
            Log::error('Failed to clear cache', ['output' => $process->errorOutput()]);

        }
    }

    protected function runMigrations(): void
    {
        $process = $this->callArtisanCommand('migrate --force');
        if (! $process->successful()) {
            Log::error('Failed to run migrations', ['output' => $process->errorOutput()]);
        }
    }

    protected function generateCache(): void
    {
        $process = $this->callArtisanCommand('optimize');
        if (! $process->successful()) {
            Log::error('Failed to generate cache', ['output' => $process->errorOutput()]);

        }

    }

    protected function terminateWorker(): void
    {
        $process = $this->callArtisanCommand('epsicube:terminate');
        if (! $process->successful()) {
            Log::error('Failed to send terminate signal', ['output' => $process->errorOutput()]);
        }
    }

    protected function callArtisanCommand(string $command): ProcessResult
    {
        return Process::command([php_binary(), artisan_binary(), ...explode(' ', $command)])
            ->path(base_path())
            ->run();
    }
}
