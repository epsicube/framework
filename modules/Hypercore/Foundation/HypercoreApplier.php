<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Foundation;

use Closure;
use UniGale\Foundation\Contracts\ActivationDriver;
use UniGale\Foundation\Contracts\Module;
use UniGale\Foundation\Registries\ModulesRegistry;

class HypercoreApplier
{
    public function __construct(
        protected ModulesRegistry $registry,
        protected Module $recordingModule,
        protected ActivationDriver $activationDriver
    ) {}

    public function removeModules(array $identifiers): void
    {
        $this->registry->modifyItemsUsing(function (array $modules) use ($identifiers) {
            return array_diff_key($modules, array_flip($identifiers));
        });
    }

    public function injectModules(Module ...$modules): void
    {
        $this->registry->register(...$modules);
    }

    public function markAsMustUse(string ...$moduleIdentifiers): void
    {
        /**
         * Don't use $this->registry->markAsMustUse because driver can be different
         */
        foreach ($moduleIdentifiers as $moduleIdentifier) {
            $module = $this->registry->get($moduleIdentifier);
            $this->activationDriver->markAsMustUse($module);
        }
    }

    public function injectIntegrations(
        string $identifier,
        Closure|callable|null $whenEnabled = null,
        Closure|callable|null $whenDisabled = null,
    ): void {
        $integrations = $this->registry->integrations();
        $integrations->beginRecording($this->recordingModule->identifier());
        $integrations->forModule($identifier, $whenEnabled, $whenDisabled);
        $integrations->endRecording();
    }

    public function getModule(string $identifier): ?Module
    {
        return $this->registry->safeGet($identifier);
    }
}
