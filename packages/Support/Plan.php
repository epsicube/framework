<?php

declare(strict_types=1);

namespace Epsicube\Support;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use RuntimeException;

use function Illuminate\Support\artisan_binary;
use function Illuminate\Support\php_binary;

/**
 * @template T
 */
abstract class Plan
{
    /**
     * Determine if the plan can be extended via configureUsing.
     */
    protected static bool $extendable = true;

    private bool $booted = false;

    /**
     * @var array<int, list<array{label: string, callback: callable(T):void, hidden: bool}>>
     */
    private array $tasks = [];

    /**
     * Plan constructor.
     * Automatically dispatches the instance to allow external configuration.
     */
    public function __construct()
    {
        if (static::$extendable) {
            $this->booted = true;
            $this->setUp();
            Event::dispatch($this);
        }
    }

    protected function setUp(): void {}

    /**
     * Adds a task to the plan.
     *
     * @param  callable(T): void  $callback
     * @return $this
     */
    public function addTask(string $label, callable $callback, int $order = 0, bool $hidden = false): self
    {
        $this->ensureIsBooted();
        $this->tasks[$order][] = [
            'label'    => $label,
            'callback' => $callback,
            'hidden'   => $hidden,
        ];

        return $this;
    }

    /**
     * Get the compiled list of tasks.
     *
     * @return list<array{label: string, callback: callable(T):void, hidden: bool}>
     */
    public function getTasks(): array
    {
        $this->ensureIsBooted();
        if (empty($this->tasks)) {
            return [];
        }

        ksort($this->tasks);

        /** @var list<array{label: string, callback: callable(T):void, hidden: bool}> */
        return array_merge(...$this->tasks);
    }

    /**
     * Execute the plan.
     *
     * @param  T  ...$args
     */
    public function __invoke(...$args): void
    {
        $this->ensureIsBooted();

        foreach ($this->getTasks() as $task) {
            ($task['callback'])(...$args);
        }
    }

    /**
     * Configure the plan instance after it has been resolved.
     *
     * @param  (callable(static): void)  $callback
     *
     * @throws RuntimeException
     */
    public static function configureUsing(callable $callback): void
    {
        if (! static::$extendable) {
            throw new RuntimeException(sprintf(
                'The plan [%s] is marked as not extendable and cannot be configured.',
                static::class
            ));
        }

        if (static::class === self::class) {
            throw new RuntimeException(sprintf(
                'The "configureUsing" method must be called on a concrete child class of [%s], not on the abstract class itself.',
                self::class
            ));
        }

        Event::listen(static::class, $callback);
    }

    public function callArtisanCommand(string $command): ProcessResult
    {
        return Process::command([php_binary(), artisan_binary(), ...explode(' ', $command)])
            ->path(base_path())
            ->run();
    }

    /**
     * Ensures the plan has been properly booted before any interaction.
     *
     * @throws RuntimeException
     */
    protected function ensureIsBooted(): void
    {
        if (static::$extendable && ! $this->booted) {
            throw new RuntimeException(sprintf(
                'The plan [%s] is not booted. It must be instantiated correctly to trigger its internal lifecycle.',
                static::class
            ));
        }
    }
}
