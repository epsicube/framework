<?php

declare(strict_types=1);

namespace UniGale\Support\Contracts;

use UniGale\Support\Dependencies;

/**
 * Optional contract for modules that declare dependencies on other modules.
 *
 * A module implementing this contract should return a list of module identifiers
 * that must be enabled (or marked as Must-Use) before this module can be enabled
 * or considered effectively enabled.
 */
interface HasDependencies
{
    public function dependencies(): Dependencies;
}
