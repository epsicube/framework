<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Support\Facades\Epsicube;
use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Process\Process;

class WorkCommand extends Command
{
    protected $signature = 'epsicube:work';

    protected $aliases = ['ec:w'];

    protected $description = 'Run and supervise all module work commands';

    /** @var array<string, Process> */
    protected array $processes = [];

    protected bool $shouldKeepRunning = true;

    public function __construct(protected Cache $cache)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $subCommands = Epsicube::workCommands();
        if (empty($subCommands)) {
            $this->log('No work commands registered. Supervisor cannot start.', 'warn');

            return;
        }

        $this->log('Starting sub-processes…');
        foreach ($subCommands as $key => $command) {
            $this->processes[$key] = $this->startProcess($key, $command);
        }

        $this->trap([SIGINT, SIGTERM], function (int $signal) {
            $this->log('Received termination signal, stopping all sub-processes…', 'warn');
            $this->stopRunningProcesses(10, $signal);
            $this->shouldKeepRunning = false;
        });

        $lastReload = $this->cache->get('epsicube:work:reload', 0);

        while ($this->shouldKeepRunning) {
            $this->checkProcesses($subCommands);
            $lastReload = $this->checkReload($subCommands, $lastReload);
            usleep(500_000);
        }

        $this->log('All sub-processes stopped, exiting.');
    }

    protected function startProcess(string $key, string $commandString): Process
    {
        $command = Application::formatCommandString($commandString);

        $this->log("Starting '{$key}'…");

        $process = Process::fromShellCommandline($command, $this->laravel->basePath());
        $process->start(function (string $type, string $output) use ($key) {
            $level = $type === 'stderr' ? 'warn' : 'line';
            foreach (preg_split('/\R/', $output) as $line) {
                if ($line !== '') {
                    $this->log($line, $level, $key);
                }
            }
        });

        return $process;
    }

    protected function checkProcesses(array $subCommands): void
    {
        foreach ($this->processes as $key => $process) {
            if (! $process->isRunning()) {
                $this->log('Process stopped. Restarting…', 'warn', $key);
                $this->processes[$key] = $this->startProcess($key, $subCommands[$key]);
            }
        }
    }

    protected function checkReload(array $subCommands, int $lastReload): int
    {
        $reloadTimestamp = $this->cache->get('epsicube:work:reload', 0);
        if ($reloadTimestamp > $lastReload) {
            $this->log('Reload signal detected, restarting all sub-processes…', 'info');
            foreach ($this->processes as $key => $process) {
                $this->log('Stopping process for reload, timeout 10s…', 'warn', $key);
                $process->stop(10);
                $this->processes[$key] = $this->startProcess($key, $subCommands[$key]);
            }

            return $reloadTimestamp;
        }

        return $lastReload;
    }

    protected function stopRunningProcesses(float $timeout = 10, ?int $signal = null): void
    {
        foreach ($this->processes as $key => $process) {
            if ($process->isRunning()) {
                $signalText = $signal ? " with signal {$signal}" : '';
                $this->log("Stopping process, timeout {$timeout}s{$signalText}…", 'warn', $key);
                $process->stop($timeout, $signal);
            }
        }
    }

    /**
     * Unified log method with timestamp and optional key.
     *
     * @param  string  $type  'info'|'warn'|'line'
     * @param  string  $key  Optional key for prefix, default 'supervisor'
     */
    protected function log(string $message, string $type = 'info', string $key = 'supervisor'): void
    {
        $time = date('H:i:s');
        $formatted = "[{$time}] [{$key}] {$message}";

        match ($type) {
            'info'  => $this->info($formatted),
            'warn'  => $this->warn($formatted),
            default => $this->line($formatted),
        };
    }
}
