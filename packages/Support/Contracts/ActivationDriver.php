<?php

declare(strict_types=1);

namespace Epsicube\Support\Contracts;

/**
 * Contract responsible for persisting and resolving modules activation state.
 *
 * Implementations may use different strategies (central/global scope, tenant scope, etc.).
 */
interface ActivationDriver
{
    public function enable(Module $module): void;

    public function disable(Module $module): void;

    public function isEnabled(Module $module): bool;

    public function isMustUse(Module $module): bool;

    public function markAsMustUse(Module $module): void;
}
