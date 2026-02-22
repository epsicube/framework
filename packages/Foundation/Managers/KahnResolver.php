<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Managers;

use Composer\Semver\Semver;
use Epsicube\Support\Exceptions\CircularDependencyException;
use Epsicube\Support\Modules\Module;

class KahnResolver
{
    /** @var array<string, Module> */
    private array $pool = [];

    public function __construct(Module ...$modules)
    {
        foreach ($modules as $m) {
            $this->pool[$m->identifier] = $m;
        }
    }

    /**
     * @return array<Module>
     */
    public function resolve(callable $log, Module ...$candidates): array
    {
        return $this->runKahn($log, $this->index($candidates));
    }

    public function resolveEnableChain(string $id): array
    {
        if (! isset($this->pool[$id])) {
            return [];
        }

        $subPool = [];
        $this->collect($this->pool[$id], $subPool, true);

        return $this->runKahn(fn () => null, $subPool);
    }

    public function resolveDisableChain(string $id): array
    {
        if (! isset($this->pool[$id])) {
            return [];
        }

        $subPool = [];
        $this->collect($this->pool[$id], $subPool, false);

        return array_reverse($this->runKahn(fn () => null, $subPool));
    }

    private function runKahn(callable $log, array $nodes): array
    {
        $graph = [];
        $inDegree = [];
        $eligible = [];

        foreach ($nodes as $id => $m) {
            $canBeResolved = true;

            foreach ($m->dependencies->modules as $depId => $constraint) {
                if (! isset($this->pool[$depId])) {
                    $log($this->pool[$id], "Dependency [{$depId}] is missing.");
                    $canBeResolved = false;
                    break;
                }

                if (! Semver::satisfies($this->pool[$depId]->version, $constraint)) {
                    $log($this->pool[$id], "Version mismatch: {$depId} ({$this->pool[$depId]->version}) does not match {$constraint}.");
                    $canBeResolved = false;
                    break;
                }

                if (isset($nodes[$depId])) {
                    $graph[$depId][] = $id;
                    $inDegree[$id] = ($inDegree[$id] ?? 0) + 1;
                } else {
                    $canBeResolved = false;
                    break;
                }
            }

            if ($canBeResolved) {
                $eligible[] = $id;
                $inDegree[$id] ??= 0;
            }
        }

        $queue = array_keys(array_filter($inDegree, fn ($d) => $d === 0));
        $queue = array_intersect($queue, $eligible);

        $resolved = [];

        while ($u = array_shift($queue)) {
            $resolved[] = $nodes[$u];
            foreach ($graph[$u] ?? [] as $v) {
                if (--$inDegree[$v] === 0 && in_array($v, $eligible)) {
                    $queue[] = $v;
                }
            }
        }

        if (count($resolved) !== count($eligible)) {
            throw new CircularDependencyException('Circular dependency detected among eligible modules.');
        }

        return $resolved;
    }

    private function collect(Module $m, array &$subPool, bool $upward): void
    {
        if (isset($subPool[$m->identifier])) {
            return;
        }
        $subPool[$m->identifier] = $m;

        if ($upward) {
            foreach ($m->dependencies->modules as $depId => $v) {
                if (isset($this->pool[$depId])) {
                    $this->collect($this->pool[$depId], $subPool, true);
                }
            }
        } else {
            foreach ($this->pool as $candidate) {
                if (isset($candidate->dependencies->modules[$m->identifier])) {
                    $this->collect($candidate, $subPool, false);
                }
            }
        }
    }

    private function index(array $modules): array
    {
        $indexed = [];
        foreach ($modules as $m) {
            $indexed[$m->identifier] = $m;
        }

        return $indexed;
    }
}
