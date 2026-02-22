<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules;

use Closure;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Enums\ModuleStatus;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Module
{
    private ?Closure $identityCallback = null;

    private ?Closure $requirementsCallback = null;

    private ?Closure $dependenciesCallback = null;

    private ?Closure $supportsCallback = null;

    private ?Closure $optionsCallback = null;

    // --- Runtime properties ---

    public ModuleStatus $status;

    public bool $mustUse;

    // --- Core properties ---
    public Identity $identity {
        get {
            if (! isset($this->identity)) {
                $instance = new Identity;
                $instance->name = Str::headline($this->identifier);
                if ($this->identityCallback) {
                    ($this->identityCallback)($instance);
                }
                $this->identity = $instance;
            }

            return $this->identity;
        }
    }

    public Requirements $requirements {
        get {
            if (! isset($this->requirements)) {
                $instance = new Requirements;
                if ($this->requirementsCallback) {
                    ($this->requirementsCallback)($instance);
                }
                $this->requirements = $instance;
            }

            return $this->requirements;
        }
    }

    public Dependencies $dependencies {
        get {
            if (! isset($this->dependencies)) {
                $instance = new Dependencies;
                if ($this->dependenciesCallback) {
                    ($this->dependenciesCallback)($instance);
                }
                $this->dependencies = $instance;
            }

            return $this->dependencies;
        }
    }

    public Supports $supports {
        get {
            if (! isset($this->supports)) {
                $instance = new Supports;
                if ($this->supportsCallback) {
                    ($this->supportsCallback)($instance);
                }
                $this->supports = $instance;
            }

            return $this->supports;
        }
    }

    public Schema $options {
        get {
            if (! isset($this->options)) {
                $instance = Schema::create(
                    identifier: "{$this->identifier}",
                    title: "{$this->identity->name} options",
                    description: "Define options for '{$this->identity->name}' module.",
                );
                if ($this->optionsCallback) {
                    ($this->optionsCallback)($instance);
                }
                $this->options = $instance;
            }

            return $this->options;
        }
    }

    /** @var array<class-string<ServiceProvider>> */
    public array $providers = [];

    public function __construct(public readonly string $identifier, public readonly string $version) {}

    public static function make(string $identifier, string $version = '0.0.0'): static
    {
        return new static($identifier, $version);
    }

    public function identity(Closure $callback): static
    {
        $this->identityCallback = $callback;

        return $this;
    }

    public function requirements(Closure $callback): static
    {
        $this->requirementsCallback = $callback;

        return $this;
    }

    public function dependencies(Closure $callback): static
    {
        $this->dependenciesCallback = $callback;

        return $this;
    }

    public function supports(Closure $callback): static
    {
        $this->supportsCallback = $callback;

        return $this;
    }

    public function options(Closure $callback): static
    {
        $this->optionsCallback = $callback;

        return $this;
    }

    /**
     * @param  class-string<ServiceProvider>  ...$providers
     */
    public function providers(string ...$providers): static
    {
        foreach ($providers as $provider) {
            if (! is_a($provider, ServiceProvider::class, true)) {
                throw new InvalidArgumentException(sprintf(
                    'The class [%s] must be an instance of [%s].',
                    $provider,
                    ServiceProvider::class
                ));
            }
        }
        $this->providers = array_unique(array_merge($this->providers, $providers));

        return $this;
    }
}
