<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Traits;

use Closure;

/**
 * @mixin \Illuminate\Foundation\Application
 */
trait CleanupProvider
{
    /**
     * Register a provider and return a cleanup closure that removes
     * any bindings added and unregisters the provider itself.
     */
    public function registerProviderWithCleanup(string $provider): Closure
    {
        $initialBindings = array_keys($this->getBindings());
        $this->register($provider);
        $addedBindings = array_diff(array_keys($this->getBindings()), $initialBindings);

        return function () use ($provider, $addedBindings) {
            foreach ($addedBindings as $binding) {
                $this->offsetUnset($binding);
            }
            unset(
                $this->serviceProviders[$provider],
                $this->loadedProviders[$provider]
            );
        };
    }
}
