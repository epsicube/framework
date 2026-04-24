# Epsicube Architecture Map

## Package Responsibilities

### `packages/Support`

Use for reusable framework primitives:

- `Contracts/`: core interfaces such as `IsModule`
- `Modules/`: metadata and lifecycle objects like `Module`, `Identity`, `Requirements`, `Dependencies`, `Support`, `Supports`
- `Facades/`: `Epsicube`, `Modules`, `Options`, `Manifest`
- enums, exceptions, registries, shared concerns

This package defines what a module is and the common language used across the framework.

### `packages/Foundation`

Use for application wiring and runtime behavior:

- bootstrapping
- service provider integration
- managers
- activation driver and options store implementations
- console commands such as `make:module`, `modules:*`, `options:*`

If the change is about how Epsicube boots, activates, caches, clears, or exposes Artisan tooling, it likely belongs here.

### `packages/Schemas`

Use for declarative schema and option definition:

- `Schema`
- property classes such as `BooleanProperty`, `StringProperty`, `ObjectProperty`
- exporters for validation, JSON schema, Filament, prompts

If the task changes how options are described or exported, start here.

## Module Responsibilities

### `modules/`

Built-in Epsicube modules live here. Use this area for reusable productized framework modules such as:

- Administration
- ExecutionPlatform
- Hypercore
- MailingSystem
- JsonRpcServer
- McpServer

Before implementing a new integration point, inspect an existing built-in module with the same kind of hook.

### `sandbox/app/Modules`

Use this area for application-level or example modules tied to the local sandbox app. This is the right target for experimentation, demos, and consumer-side usage of the framework.

### `docs/src/content/docs`

This is the canonical writing surface for user-facing framework documentation. When behavior changes materially, update docs close to the relevant section:

- `writing-module/`
- `usage/`
- `advanced/`
- `built-in/`
- `schemas/`

## Placement Heuristics

- New framework contract or lifecycle primitive: `packages/Support`
- New activation/boot/console behavior: `packages/Foundation`
- New option property or exporter behavior: `packages/Schemas`
- New reusable built-in feature module: `modules/`
- Local consumer module example: `sandbox/app/Modules`
- End-user explanation or workflow update: `docs/src/content/docs`
