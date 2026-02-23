<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Listeners;

use Epsicube\Foundation\Events\ModuleDisabled;
use Epsicube\Foundation\Events\ModuleEnabled;
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
        $events->listen([ModuleEnabled::class, ModuleDisabled::class], function () {
            $this->refreshOptimizations();
            $this->terminateWorker();
        });

        // TODO re-run migration module
        // TODO rollback migration module
    }

    protected function refreshOptimizations(): void
    {
        $commands = ['optimize:clear', 'optimize'];
        foreach ($commands as $command) {
            $process = $this->callArtisanCommand($command);
            if (! $process->successful()) {
                Log::error("Failed to refresh optimization: {$command}", ['output' => $process->errorOutput()]);
                break;
            }
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
