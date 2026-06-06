# Epsicube Framework

- This repository is a meta-framework. Keep changes aligned with the existing split:
  - `packages/Support`: contracts, enums, facades, module metadata
  - `packages/Foundation`: bootstrap, managers, activation, runtime wiring
  - `packages/Schemas`: schema types, exporters, validation/export behavior
  - `modules/`: built-in modules and integrations
  - `sandbox/`: example consumer application

- For module work, treat `module(): Module` as the source of truth for identifier, version, requirements, dependencies, supports, providers, and options.

- Prefer narrow, local changes. Follow nearby package/module patterns before introducing a new structure.

- Prefer test-driven development for new work and behavioral changes.
  - For every new feature or breaking change, write or update tests as part of the change.
  - Do not treat tests as optional for behavior changes unless the user explicitly asks to skip them.

- All PHP tests in this repository must be written in Pest format.
  - Use Pest syntax for new tests and when updating existing tests.
  - Do not add PHPUnit class-style test files unless the user explicitly asks for that format.
  - Do not use anonymous built-in test classes when a reusable test double or fixture is needed; create a dedicated fixture class instead.
  - Module-specific tests should live under `tests/Modules/<ModuleName>/`.
  - Module-specific test fixtures should live under `tests/Modules/<ModuleName>/Fixtures/`.
  - Package and framework tests should live under `tests/Unit/<AreaName>/` and stay close to the package being exercised.
  - When changing a built-in module under `modules/<ModuleName>/`, add or update at least one focused test under `tests/Modules/<ModuleName>/`.

- After modifying PHP code or tests, run the smallest relevant verification command when feasible.
  - In this repository, use `./scripts/run-tests.sh ...`
  - Examples:
    - `./scripts/run-tests.sh tests/Unit/Foundation`
    - `./scripts/run-tests.sh tests/Unit/Foundation/ApplicationBootstrapTest.php -p`

- If you skip test execution or cannot run it, state that explicitly in the final response.
