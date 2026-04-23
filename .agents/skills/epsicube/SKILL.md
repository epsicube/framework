---
name: epsicube
description: "Use this skill whenever working on the Epsicube framework, its built-in modules, or application modules built on top of it. Trigger for tasks involving module creation, module registration, activation, options schemas, requirements, supports, dependencies, built-in Epsicube packages (`packages/Support`, `packages/Foundation`, `packages/Schemas`), or built-in modules in `modules/`. Also use when the user asks how Epsicube is structured or how to implement a new module, command, option, support, or integration in this repository."
---

# Epsicube

Use this skill for framework-level and module-level work in this repository.

## First Pass

1. Determine whether the task targets:
   - framework internals in `packages/`
   - built-in modules in `modules/`
   - sandbox application modules in `sandbox/app/Modules/`
   - user documentation in `docs/src/content/docs/`
2. Inspect sibling modules before changing patterns. Epsicube allows several extension points, but consistency with nearby modules is the default.
3. If the task concerns module authoring or behavior, read [module-workflow.md](references/module-workflow.md).
4. If the task concerns package responsibilities or where to place code, read [architecture-map.md](references/architecture-map.md).
5. If the task involves Artisan or PHP execution, follow [command-policy.md](references/command-policy.md) exactly.

## Non-Negotiable Command Policy

All PHP commands must run in Docker, from `sandbox`, with this shape:

```bash
docker compose run --rm web 'php ...'
```

Do not run `php`, `php artisan`, `composer test`, `vendor/bin/pest`, or similar directly on the host.

## Repository Structure

- `packages/Support`: contracts, enums, facades, registries, core module metadata objects such as `Module`, `Identity`, `Requirements`, `Dependencies`, `Support`, `Supports`
- `packages/Foundation`: bootstrap, managers, activation/options infrastructure, Artisan commands, service provider wiring
- `packages/Schemas`: option/schema definition system and exporters
- `modules/`: built-in Epsicube modules such as Administration, MailingSystem, Hypercore, JsonRpcServer, McpServer
- `sandbox/app/Modules`: application modules created by consumers of the framework
- `sandbox/bootstrap/modules.php`: local module registration list
- `docs/src/content/docs`: source documentation for module authoring and framework usage

## Module Rules

When writing or editing a module:

- A module is a Laravel `ServiceProvider` implementing `Epsicube\Support\Contracts\IsModule`.
- The `module(): Module` method is the source of truth for identifier, version, identity, requirements, dependencies, supports, providers, and options.
- Prefer `Module::make('vendor-or-scope::identifier', 'x.y.z')`.
- Register the provider with `->providers(static::class)` unless the existing module clearly uses a different pattern.
- Keep `register()` and `boot()` lightweight; heavy environment branching belongs in `requirements()` or `supports()`.
- Use `requirements()` for hard blockers.
- Use `dependencies()` for other modules that must be installed/enabled with a compatible version.
- Use `supports()` for optional integrations and adaptive behavior.
- Use `options()` with `Epsicube\Schemas\Schema` and schema property objects.

## Implementation Heuristics

- Follow existing naming and identifier patterns in neighboring modules before inventing a new structure.
- If a feature belongs to framework runtime or lifecycle, prefer `packages/Foundation`.
- If it defines reusable contracts, facades, registries, enums, or metadata objects, prefer `packages/Support`.
- If it models option fields, validation/export behavior, or schema composition, prefer `packages/Schemas`.
- If it is an end-user feature that plugs into the framework, prefer `modules/<ModuleName>`.
- If the user is only experimenting locally, prefer `sandbox/app/Modules`.

## Validation

- Prefer the smallest relevant Dockerized Artisan command.
- Validate changed module files by running targeted commands when useful, for example:
  - status checks
  - enable/disable flows
  - options list/set/unset
  - targeted test runs if the repo already has tests for the change
- If validation requires PHP, always use the Docker wrapper from [command-policy.md](references/command-policy.md).

## References

- [module-workflow.md](references/module-workflow.md): authoring, lifecycle, requirements, dependencies, supports, options
- [architecture-map.md](references/architecture-map.md): package/module responsibility map
- [command-policy.md](references/command-policy.md): required Docker command wrapper for PHP and Artisan
