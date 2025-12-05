<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Foundation;

use Closure;
use Epsicube\Foundation\Managers\ModulesManager;
use Epsicube\Support\Contracts\ActivationDriver;
use Epsicube\Support\Contracts\HasIntegrations;
use Epsicube\Support\Contracts\Module;
use InvalidArgumentException;

class HypercoreApplier
{
    public function __construct(
        protected ModulesManager $registry,
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

        $module = $this->registry->get($identifier);
        if (! ($module instanceof HasIntegrations)) {
            throw new InvalidArgumentException(sprintf("Module '%s' does not implement HasIntegrations", $identifier));
        }

        $module->integrations()->forModule($identifier, $whenEnabled, $whenDisabled);
    }

    public function getModule(string $identifier): ?Module
    {
        return $this->registry->safeGet($identifier);
    }
}
