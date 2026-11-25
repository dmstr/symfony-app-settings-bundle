<!-- file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC -->

# Changelog

All notable changes to this bundle. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/) and come from the Git tags.

## [0.1.0] - 2026-11-25

Initial public release.

### Added

- Settings sections contributed through `SettingsContributorInterface` (autoconfigured), one JSON schema each, with defaults taken from the schema's `default` keywords.
- The admin-only API Platform resource `ApplicationSetting` (`GET` collection, `GET` item, `PATCH` with JSON Merge Patch), validated against the section schema, with one OpenAPI schema branch per section.
- `SettingsReader` for typed reads with schema defaults.
- The permission `appsettings:setting:manage`, announced at the container tag `za7_core.permission`; the host application provides the voter.
- The migration `Dmstr\AppSettings\Migrations\Version20260930163000` (table `dmstr_app_setting`, PostgreSQL).
- PHPUnit suites `unit` and `integration` (test kernel, PostgreSQL) and a GitHub Action.

### Requirements

- Licence: MIT.
- PHP >= 8.5, Symfony 7.4, API Platform 4.4, Doctrine ORM 3, Doctrine DBAL 3, PostgreSQL.

[0.1.0]: https://github.com/dmstr/symfony-app-settings-bundle/releases/tag/0.1.0
