# Epsicube Schema Workflow

## When to use `packages/Schemas`

Use `packages/Schemas` when the task is about:

- defining structured options or payloads
- adding a new property type
- changing validation/export behavior
- generating JSON Schema, Filament components, or CLI prompts
- composing or filtering schemas at runtime

Do not place schema behavior in `packages/Foundation` or `packages/Support` unless the change is about framework wiring rather than the schema model itself.

## Core APIs to know

### `Schema`

The main entrypoint is `Epsicube\Schemas\Schema`.

Important methods:

- `Schema::create(string $identifier, ?string $title = null, ?string $description = null, array $properties = [])`
- `->append(array $properties)`
- `->property(string $name)`
- `->properties()`
- `->only(string ...$properties)`
- `->except(string ...$properties)`
- `->withDefaults(array $data)`
- `->validated(array $data, bool $bail = false)`
- `->toValidator(...)`
- `->toJsonSchema()`
- `->toFilamentComponents(...)`
- `->toExecutedPrompts(...)`
- `->cacheExport()`

### Property semantics

Every built-in property inherits:

- `title()`
- `description()`
- `optional()`
- `nullable()`
- a typed `default()` method

Important rules:

- a property with a default must also be optional
- `optional` means the key may be omitted
- `nullable` means the value may be `null`
- these are independent concepts and must not be conflated

## Built-in property classes

Current built-ins in `packages/Schemas/Properties`:

- `StringProperty`
- `BooleanProperty`
- `IntegerProperty`
- `FloatProperty`
- `EnumProperty`
- `ObjectProperty`
- `ArrayProperty`

Before adding a new property type, inspect the existing classes and keep the same pattern:

1. type-specific state and fluent setters
2. `toJsonSchema(...)`
3. `toFilamentComponent(...)`
4. `askPrompt(...)`
5. `resolveValidationRules(...)`

## Export behavior expectations

When changing schema behavior, verify effects across all exporters:

- `JsonSchemaExporter`
- `FilamentComponentsExporter`
- `LaravelPromptsFormExporter`
- `LaravelValidatorExporter`

Do not implement a feature in only one exporter unless the asymmetry is intentional and documented.

## Existing framework patterns

Common patterns already used in the repo:

- module options are declared in `Module::options(fn (Schema $schema) => ...)`
- commands can scope one field with `->only($key)`
- runtime payloads can be wrapped with `ObjectProperty` for nested validation
- activity and procedure schemas may be created dynamically with `Schema::create(...)`

Relevant examples:

- `modules/JsonRpcServer/JsonRpcServerModule.php`
- `packages/Foundation/Console/Commands/OptionsSetCommand.php`
- `modules/ExecutionPlatform/Registries/ActivitiesRegistry.php`
- `modules/ExecutionPlatform/Integrations/JsonRpcServer/Procedures/ExecutionProcedure.php`

## Editing guidance

When documenting or modifying schemas:

- inspect current property implementations before describing behavior
- verify whether behavior is schema-level or exporter-specific
- document real constraints, not intended ones
- call out current limitations when visible in code comments or implementation gaps

## Validation guidance

If PHP validation is needed, follow the Epsicube Docker command policy from `command-policy.md`.

Prefer targeted validation around:

- schema export commands or examples
- option flows using schemas
- any changed runtime integration that depends on `validated()` or `toValidator()`
