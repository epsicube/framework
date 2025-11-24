<?php

declare(strict_types=1);

namespace UniGale\Foundation;

use Closure;
use LogicException;

class IntegrationsManager
{
    /**
     * Internal callback storage
     * [
     *   'sourceModuleIdentifier' => [
     *       'targetModuleIdentifier' => [
     *           'enabled' => [callable, ...],
     *           'disabled' => [callable, ...],
     *       ],
     *   ],
     * ]
     */
    protected array $registrations = [];

    protected ?string $currentSource = null;

    public function beginRecording(string $sourceIdentifier): void
    {
        $this->currentSource = $sourceIdentifier;
        $this->registrations[$sourceIdentifier] ??= [];
    }

    public function endRecording(): void
    {
        $this->currentSource = null;
    }

    /**
     * Register callbacks for a target module
     */
    public function forModule(
        string $identifier,
        Closure|callable|null $whenEnabled = null,
        Closure|callable|null $whenDisabled = null,
    ): static {
        if (! $this->currentSource) {
            throw new LogicException('No source is currently recording integrations.');
        }

        $source = $this->currentSource;

        $this->registrations[$source][$identifier]['enabled'] ??= [];
        $this->registrations[$source][$identifier]['disabled'] ??= [];

        if ($whenEnabled) {
            $this->registrations[$source][$identifier]['enabled'][] = $whenEnabled;
        }

        if ($whenDisabled) {
            $this->registrations[$source][$identifier]['disabled'][] = $whenDisabled;
        }

        return $this;
    }

    /**
     * Get all target modules registered by a source
     *
     * @return string[]
     */
    public function registeredModulesFor(string $sourceIdentifier): array
    {
        return array_keys($this->registrations[$sourceIdentifier] ?? []);
    }

    /**
     * Execute callbacks based on enabled modules
     *
     * - "enabled" callbacks run if the target module is enabled
     * - "disabled" callbacks run if the target module is disabled and the source is enabled
     *
     * @param  string[]  $enabledModules  List of enabled module identifiers
     */
    public function runCallbacks(array $enabledModules): void
    {
        $enabledModules = array_flip($enabledModules); // quick lookup

        foreach ($this->registrations as $source => $targets) {
            if (! isset($enabledModules[$source])) {
                continue; // skip disabled sources
            }

            foreach ($targets as $target => $branches) {
                if (isset($enabledModules[$target])) {
                    foreach ($branches['enabled'] as $cb) {
                        $cb();
                    }
                } else {
                    foreach ($branches['disabled'] as $cb) {
                        $cb();
                    }
                }
            }
        }
    }
}
