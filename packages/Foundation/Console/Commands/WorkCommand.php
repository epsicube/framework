<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Support\Facades\Epsicube;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Process\Process;

use function Illuminate\Support\artisan_binary;
use function Illuminate\Support\php_binary;

class WorkCommand extends Command
{
    protected $signature = 'epsicube:work';

    protected $aliases = ['ec:w'];

    protected $description = 'Run and supervise all module work commands';

    /**
     * @var array<string,Process>
     */
    protected array $processes = [];

    protected bool $shouldKeepRunning = true;

    protected array $colors = ['cyan', 'magenta', 'yellow', 'blue', 'red'];

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
            $this->log('Termination signal received, stopping sub-processes…', 'warn');
            $this->stopRunningProcesses();
            $this->shouldKeepRunning = false;
        });

        $lastReload = $this->cache->get('epsicube:work:reload', 0);

        while ($this->shouldKeepRunning) {
            $this->checkProcesses($subCommands);
            $lastReload = $this->checkReload($subCommands, (int) $lastReload);
            usleep(500_000);
        }

        $this->log('All sub-processes stopped, exiting.');
    }

    protected function startProcess(string $key, string $commandString): Process
    {

        $args = [php_binary(), artisan_binary(), ...explode(' ', $commandString)];
        $isDecorated = $this->output->isDecorated();

        $env = $isDecorated
            ? ['FORCE_COLOR' => '1', 'TERM' => 'xterm-256color']
            : ['NO_COLOR' => '1', 'FORCE_COLOR' => '0'];

        if (! $isDecorated) {
            $args[] = '--no-ansi';
        }
        $process = new Process($args, $this->laravel->basePath(), $env);
        $process->setTimeout(null);
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
            if ($process->isRunning()) {
                continue;
            }

            $this->log('Process stopped unexpectedly. Restarting…', 'warn', $key);
            $this->processes[$key] = $this->startProcess($key, $subCommands[$key]);
        }
    }

    protected function checkReload(array $subCommands, int $lastReload): int
    {
        $reloadTimestamp = (int) $this->cache->get('epsicube:work:reload', 0);

        if ($reloadTimestamp <= $lastReload) {
            return $lastReload;
        }

        $this->log('Reload signal detected, restarting all sub-processes…', 'info');
        $this->stopRunningProcesses();

        foreach ($subCommands as $key => $command) {
            $this->processes[$key] = $this->startProcess($key, $command);
        }

        return $reloadTimestamp;
    }

    protected function stopRunningProcesses(float $timeout = 10): void
    {
        foreach ($this->processes as $key => $process) {
            if (! $process->isRunning()) {
                continue;
            }

            $this->log("Stopping process (timeout {$timeout}s)…", 'warn', $key);

            $process->stop($timeout);
        }
    }

    protected function log(string $message, string $type = 'info', string $key = 'supervisor'): void
    {
        $color = $key === 'supervisor' ? 'green' : $this->colors[abs(crc32($key)) % count($this->colors)];

        $paddedKey = mb_str_pad($key, 12, ' ', STR_PAD_BOTH);

        $this->output->writeln(sprintf(
            '<fg=gray>[%s]</> <fg=%s>[%s]</> %s%s',
            now()->toDateTimeString(),
            $color,
            $paddedKey,
            $type === 'warn' ? '<fg=red>[ERR]</> ' : '',
            $message
        ));
    }
}
