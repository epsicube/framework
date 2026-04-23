# Epsicube Module Workflow

## Core Shape

An Epsicube module is a Laravel `ServiceProvider` implementing `Epsicube\Support\Contracts\IsModule`.

Minimal structure:

```php
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Illuminate\Support\ServiceProvider;

class MyModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make('custom::my-module', '0.0.1')
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name('My Module')
                ->description('')
                ->author('Internal dev')
            );
    }
}
```

## Registration

- Sandbox application modules are registered in `sandbox/bootstrap/modules.php`.
- `make:module` creates the class under `sandbox/app/Modules` and adds it to `sandbox/bootstrap/modules.php`.
- Built-in framework modules live in `modules/<ModuleName>`.

## Metadata

Use `identity()` to define:

- human name
- description
- author

The module identifier must stay unique across the application. Existing examples use identifiers like:

- `custom::mycustommodule`
- `core::administration`
- `inventory-manager`

Follow the surrounding convention rather than normalizing old code.

## Requirements

Use `requirements()` for hard runtime prerequisites. Failed requirements should block activation.

Typical conditions:

- `Condition::phpVersion('>= 8.2')`
- `Condition::phpExtensions('curl')`
- `Condition::epsicubeVersion('^1.0')`
- `Condition::all(...)`
- `Condition::any(...)`
- `...Condition::when($condition, [...])`

Use requirements for environment guarantees, not optional integrations.

## Dependencies

Use `dependencies()` when the module needs other modules to be installed and enabled.

Example:

```php
->dependencies(fn (Dependencies $dependencies) => $dependencies
    ->module('core::administration', '*')
    ->module('core::execution-platform', '^1.0')
)
```

Version constraints follow Composer-style semver constraints.

## Supports

Use `supports()` for optional behavior. Supports are for adapting to the environment, not for blocking the module.

Patterns:

- `Support::forModule(...)` for module-to-module integrations
- `Support::for(...)` for PHP/environment conditions

Common condition states for `Support::forModule()`:

- `ModuleCondition::PRESENT`
- `ModuleCondition::ENABLED`
- `ModuleCondition::DISABLED`
- `ModuleCondition::ABSENT`
- `ModuleCondition::INACTIVE`

Keep support callbacks lightweight because they run during module bootstrapping.

## Options

Use `options()` with `Epsicube\Schemas\Schema`.

Example:

```php
use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Schemas\Schema;

->options(fn (Schema $options) => $options->append([
    'your-option' => BooleanProperty::make()
        ->title('Your Option')
        ->optional()
        ->default(true),
]))
```

Use the `Options` facade only after registration/bootstrap where lifecycle allows it. Avoid using `Options` during registration when the docs warn against it.

## Long-Running Work

If a module exposes a worker command, register it in `boot()` via:

```php
Epsicube::addWorkCommand('your-unique-key', 'your-module:work');
```

## Activation Notes

- Enabling and disabling modules is not installation or uninstallation.
- Per the docs, migrations are not automatically installed or rolled back on module enable/disable.
- If a module introduces migrations, activation flows may also require a manual migration step.

## Preferred Workflow

1. Inspect a sibling built-in or sandbox module with similar behavior.
2. Implement or modify `module(): Module` first.
3. Add or adjust `register()` and `boot()` only after metadata and lifecycle conditions are clear.
4. Register sandbox modules in `sandbox/bootstrap/modules.php` when needed.
5. Validate with Dockerized Artisan commands only.
