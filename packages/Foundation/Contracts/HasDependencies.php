<?php

declare(strict_types=1);

namespace UniGale\Foundation\Contracts;

/**
 * Optional contract for modules that declare dependencies on other modules.
 *
 * A module implementing this contract should return a list of module identifiers
 * that must be enabled (or marked as Must-Use) before this module can be enabled
 * or considered effectively enabled.
 */
interface HasDependencies
{
    /**
     * Return the list of required module identifiers.
     *
     * @return string[]
     */
    public function dependencies(): array;
}
