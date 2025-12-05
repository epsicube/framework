<?php

declare(strict_types=1);

namespace Epsicube\Support\Contracts;

use Epsicube\Support\Integrations;

/**
 * Optional contract for modules that define integrations with other modules.
 *
 * A module implementing this contract may register callbacks that are executed
 * when specific target modules are enabled or disabled. This allows a module
 * to extend, customize, or interact with other modules safely, without causing
 * errors when the target modules are unavailable.
 *
 * Implementations must return an IntegrationsDefinition instance describing
 * all declared integrations.
 */
interface HasIntegrations
{
    public function integrations(): Integrations;
}
