<!-- file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC -->

# CLAUDE.md

This is a public open-source package maintained under github.com/dmstr.

Symfony bundle for runtime application settings: sections contributed by the application or other bundles, one JSON schema each, stored as JSON per section in PostgreSQL, validated on write, exposed as the admin-only API Platform resource `ApplicationSetting` under `/api/admin/application_settings` and readable as typed values through `SettingsReader`. See `README.md` for the concepts.

## Language policy

**Everything in this repository is in English**: commit messages, code, comments, documentation, test names and test data, and all communication about the repository (pull request titles and descriptions, code review, issues, and chat with the coding assistant). These packages are public and English-only; do not reference private projects, internal trackers or company internals.

## Requirements and tests

PHP >= 8.5 (see `composer.json`). The PHPUnit suite (configuration `phpunit.dist.xml`) has two test suites:

- `unit` (`tests/Unit`): no kernel, no database. Run it with `vendor/bin/phpunit --testsuite unit`.
- `integration` (`tests/Integration`): boots the test kernel `tests/Kernel/TestKernel.php` (test sections in `tests/Fixtures`, header-based authentication `X-Test-Roles` / `X-Test-User` / `X-Test-Auth`, a test permission voter, the audit-log bundle). Tests in the group `database` need a PostgreSQL server from `DATABASE_URL`; the schema is created by running the migrations, the bundle's own one included. Without a server, run `vendor/bin/phpunit --testsuite integration --exclude-group database`.

CI is the GitHub Action `.github/workflows/tests.yml` (PHP 8.5, PostgreSQL service container), which runs both suites. Test helpers live only under `tests/`.

## Invariants

- The migration class `Dmstr\AppSettings\Migrations\Version20260930163000` and the migrations path `__DIR__.'/../migrations'` in `DmstrAppSettingsBundle` never change: applications store the class name.
- The bundle checks only the permission `appsettings:setting:manage` (`Dmstr\AppSettings\Security\AppSettingsPermissions`, tagged `za7_core.permission` in `config/services.yaml`) with `is_granted()` and subject `null`. No `ROLE_*` name appears in `src/`, `config/` or the docs; the host application maps permissions to roles through its own voter. A new permission gets a constant, a tag, a test and a README update in the same change.
