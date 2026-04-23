# Epsicube Command Policy

## Required PHP Execution Wrapper

When a task requires `php`, `php artisan`, PHPUnit, Pest, or any PHP entrypoint, execute it in Docker from the `sandbox` directory.

Required pattern:

```bash
docker compose run --rm web 'php ...'
```

Working directory:

```text
sandbox
```

## Examples

```bash
docker compose run --rm web 'php artisan modules:status'
docker compose run --rm web 'php artisan make:module MyCustomModule'
docker compose run --rm web 'php artisan modules:enable custom::mycustommodule'
docker compose run --rm web 'php artisan options:list'
docker compose run --rm web 'php artisan options:set core::administration brand-name Epsicube'
docker compose run --rm web 'php artisan test --compact'
```

## Rules

- Do not run host-side `php` commands.
- Do not switch to `php artisan ...` directly from the repository root.
- If a command mutates the Laravel app, assume it must be run with `workdir=sandbox`.
- When presenting commands to the user, show the full Dockerized form rather than a shortened host form.

## Validation Strategy

- Prefer the smallest command that proves the change.
- For module work, start with `modules:status`, `modules:enable`, `modules:disable`, or `options:list`.
- If the feature touches migrations, remember module enable/disable does not automatically install or roll back migrations according to the current docs.
