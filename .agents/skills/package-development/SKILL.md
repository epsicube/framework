---
name: package-development
description: "Use this skill when developing or refactoring code in this repository's framework packages or built-in modules. Trigger for new features, breaking changes, package-level refactors, module behavior changes, and any work where tests must be added or updated. Enforces the user's preference for test-driven development, Pest-only tests, and the repository's test layout for packages and modules."
---

# Package Development

Use this skill for day-to-day development in this repository when the task changes behavior in `packages/` or `modules/`.

## Core expectations

- Prefer test-driven development.
- For every new feature or breaking change, write or update tests as part of the change.
- Treat missing tests for behavior changes as a gap to close, not as optional cleanup.
- Keep tests focused on behavior and public contracts.

## Test format

- All PHP tests must use Pest.
- Do not introduce PHPUnit class-style tests unless the user explicitly asks for that format.
- Do not use anonymous classes as reusable test doubles; create dedicated fixture classes instead.

## Test placement

- For framework and package code in `packages/`, place tests under `tests/Unit/<AreaName>/`.
    - Example: changes in `packages/Foundation` belong in `tests/Unit/Foundation/`.
    - Example: changes in `packages/Support` belong in `tests/Unit/Support/` when that area exists.
- For built-in modules in `modules/<ModuleName>/`, place tests under `tests/Modules/<ModuleName>/`.
- Place module-specific test fixtures under `tests/Modules/<ModuleName>/Fixtures/`.
- When a module changes behavior, add or update at least one focused test in that module test directory.

## TDD workflow

1. Identify the behavioral surface being changed.
2. Write or update the smallest failing Pest test that expresses that behavior.
3. Implement the code change with narrow scope.
4. Run the smallest relevant test target with `./scripts/run-tests.sh ...`.
5. Expand coverage only where the change affects adjacent contracts, integration points, or regressions.

## Design preference

- Favor a domain-driven style.
- Name code around domain concepts, not infrastructure accidents.
- Keep domain rules explicit and local instead of spreading them through controllers, providers, or UI glue.
- Prefer small collaborators with clear responsibilities over large procedural classes.
- Preserve the repository split:
    - `packages/Support`: contracts, enums, facades, module metadata
    - `packages/Foundation`: bootstrap, managers, activation, runtime wiring
    - `packages/Schemas`: schema types, exporters, validation/export behavior
    - `modules/`: built-in modules and integrations

## Validation

- After changing PHP code or tests, run the smallest relevant verification command when feasible.
- In this repository, use `./scripts/run-tests.sh ...`.
- If tests are skipped or cannot run, state that explicitly in the final response.
