<!-- file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC -->

# dmstr/symfony-app-settings-bundle

Symfony bundle for runtime application settings. The application and other bundles contribute settings *sections*, each described by one JSON schema. The values of a section are stored as JSON in one row per section, validated against the schema on every write, exposed as an admin-only API Platform resource and read back in code as typed values with the schema defaults applied.

Because every section publishes its schema in the OpenAPI document, an admin panel can discover the sections and render one form per section without knowing them in advance.

## Requirements

- PHP >= 8.5
- Symfony 7.4 (FrameworkBundle, SecurityBundle)
- API Platform 4.4 with Doctrine ORM 3 and DoctrineMigrationsBundle
- PostgreSQL: the migration creates `UUID`, `JSONB` and `TIMESTAMP WITH TIME ZONE` columns
- [`dmstr/openapi-json-schema-bundle`](https://github.com/dmstr/openapi-json-schema-bundle) `^0.5` (installed as a dependency), which puts the per-section schemas into the OpenAPI document
- A security voter for the bundle's permission, see [Permissions](#permissions)

## Installation

```bash
composer require dmstr/symfony-app-settings-bundle
```

Register the bundle and the bundles it builds on in `config/bundles.php` (Symfony Flex adds most of them):

```php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class => ['all' => true],
    Dmstr\OpenApiJsonSchema\OpenApiJsonSchemaBundle::class => ['all' => true],
    Dmstr\AppSettings\DmstrAppSettingsBundle::class => ['all' => true],
];
```

Then run the migrations, see [Migrations](#migrations).

## Configuration

The bundle has no configuration of its own (`dmstr_app_settings` has an empty tree). It prepends the following configuration, which the application's own configuration overrides:

- the Doctrine ORM mapping of its entity (mapping name and alias `DmstrAppSettings`, prefix `Dmstr\AppSettings\Entity`);
- the API Platform resource path of its entity;
- its migrations path (namespace `Dmstr\AppSettings\Migrations`).

The application provides:

- one or more settings sections (services implementing `SettingsContributorInterface`, see below);
- a voter for the permission `appsettings:setting:manage` ([Permissions](#permissions));
- optionally a Gedmo loggable listener with an actor provider ([Audit log](#audit-log)).

## Contributing a section

A section is a service implementing `Dmstr\AppSettings\Contract\SettingsContributorInterface`. Services implementing the interface are tagged automatically (autoconfiguration, tag `dmstr_app_settings.contributor`) and collected by the `SectionRegistry`.

```php
use Dmstr\AppSettings\Contract\SettingsContributorInterface;

final class MailSettingsContributor implements SettingsContributorInterface
{
    public function getSectionKey(): string
    {
        return 'mail';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'title' => 'Mail',
            'properties' => [
                'sender' => ['type' => 'string', 'format' => 'email', 'default' => 'noreply@example.com'],
                'daily_limit' => ['type' => 'integer', 'minimum' => 0, 'default' => 500],
            ],
        ];
    }
}
```

Rules for a section:

- The key is snake_case (`^[a-z][a-z0-9_]*$`, at most 100 characters) and unique across the application. A duplicate or invalid key fails as soon as the registry is built, i.e. on the first request or command that uses it.
- The defaults are the property-level `default` keywords of the schema. There is no separate defaults method: the schema is the single source, so the values an admin panel prefills and the defaults the server applies always agree.
- The registry completes the schema: `type: object`, `additionalProperties: false` (unless the contributor sets it), `title` (the key, unless set) and a `section_key` property with `const` and `default` equal to the key. `section_key` discriminates the section schemas in the OpenAPI document.

## The API

The resource `ApplicationSetting` offers three operations, all restricted to the permission `appsettings:setting:manage`:

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/api/admin/application_settings` | All registered sections, sorted by key |
| `GET` | `/api/admin/application_settings/{id}` | One section |
| `PATCH` | `/api/admin/application_settings/{id}` | Change values of one section |

The paths assume that API Platform's routes are imported below `/api`. `POST`, `PUT` and `DELETE` are not offered: the set of sections is defined by the contributors, not by API clients.

Identifiers are deterministic: the id of a section is the UUID v5 of the section key in the namespace `SectionRegistry::NAMESPACE` (`b7f3c2a1-5e4d-4c6b-9a8f-1e2d3c4b5a69`). A section therefore has the same id on every installation and before and after its first write.

Every registered section is listed and readable, even if nothing has been stored for it yet; `values` then holds the defaults. The row is created on the first write (`PATCH`). Rows of sections that are no longer registered are ignored.

`values` always carries the effective values: the stored values merged over the section defaults. Only the stored values are persisted, so a changed default takes effect wherever the value was not set explicitly.

`PATCH` uses JSON Merge Patch (RFC 7396, `Content-Type: application/merge-patch+json`) on `values`:

- objects merge recursively, lists and scalars replace the stored value;
- `null` removes the stored value, so the default applies again;
- PHP cannot tell `{}` from `[]` once decoded, so an empty array in the patch is treated as an empty object where that is harmless: it leaves a stored object unchanged and otherwise stores `[]`.

```http
PATCH /api/admin/application_settings/{id}
Content-Type: application/merge-patch+json

{"values": {"daily_limit": 1000, "sender": null}}
```

After the patch, the stored values are validated against the section schema (opis/json-schema). Every schema error becomes one violation in the `422` response, with `propertyPath` set to `values` followed by the JSON pointer of the offending value, for example `values/daily_limit`; an undeclared property is reported at its own path (`values/<name>`). An empty set of stored values is valid.

## Permissions

The bundle checks exactly one permission, `appsettings:setting:manage` (constant `Dmstr\AppSettings\Security\AppSettingsPermissions::SETTING_MANAGE`), with `is_granted('appsettings:setting:manage')` and no subject, on all three operations. It knows no role names and ships no voter.

The permission is announced with a container tag in `./config/services.yaml`:

```yaml
Dmstr\AppSettings\Security\AppSettingsPermissions:
    tags:
        - { name: za7_core.permission, permission: 'appsettings:setting:manage' }
```

The tag is a plain string: the bundle depends on no permission package. The contract with the host application uses three identifiers, which stay stable:

| Identifier | Kind | Provided by |
| --- | --- | --- |
| `za7_core.permission` | container tag, attribute `permission` | this bundle (one tag per permission) |
| `za7_core.permissions.registered` | container parameter, list of all tagged permissions | the host application (a compiler pass that collects the tags) |
| `za7_core.permissions` | container parameter, map permission => list of roles | the host application |

The host application must provide a Symfony security voter that supports the attribute `appsettings:setting:manage` and decides who is granted it, for example by mapping roles in `za7_core.permissions`. A minimal voter:

```php
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class PermissionVoter implements VoterInterface
{
    /**
     * @param array<string, list<string>> $map permission => roles (`%za7_core.permissions%`)
     */
    public function __construct(private readonly array $map)
    {
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        $result = self::ACCESS_ABSTAIN;
        foreach ($attributes as $attribute) {
            if (!\is_string($attribute) || !isset($this->map[$attribute])) {
                continue;
            }
            if ([] !== array_intersect($token->getRoleNames(), $this->map[$attribute])) {
                return self::ACCESS_GRANTED;
            }
            $result = self::ACCESS_DENIED;
        }

        return $result;
    }
}
```

```yaml
parameters:
    za7_core.permissions:
        'appsettings:setting:manage': ['ROLE_ADMIN']
```

**Without such a voter, every request is denied.** No voter supports the attribute, so all voters abstain, and Symfony's default access decision strategy (`affirmative` with `allow_if_all_abstain: false`) denies access: an authenticated user gets `403`, administrators included, and an anonymous request gets `401` from the firewall's entry point. This follows from Symfony's default strategy and is not a guarantee of the bundle: an application that sets `allow_if_all_abstain: true` grants the permission to everyone who passes the firewall.

## Admin panel discovery

In the OpenAPI document (`/api/docs.jsonopenapi`), the `values` property of every `ApplicationSetting` schema is an `anyOf` with one branch per section. Each branch is the complete section schema, with `additionalProperties: false`, the property defaults and the `section_key` const, so a client picks the form for a resource by its `sectionKey` (or `values.section_key`) and prefills it from the `default` keywords. The Hydra entrypoint lists the collection as `applicationSetting`.

Production caches the OpenAPI and Hydra documentation. A newly contributed section appears in the documentation after `bin/console cache:clear`; the API itself lists it immediately after deployment.

## Typed reads

Code reads settings through `Dmstr\AppSettings\Reader\SettingsReader`. Paths are dotted property names within a section; values are cast to the type the schema declares, and the defaults apply while nothing is stored.

```php
use Dmstr\AppSettings\Reader\SettingsReader;

final class MailLimiter
{
    public function __construct(private readonly SettingsReader $settings)
    {
    }

    public function dailyLimit(): int
    {
        // The schema default applies while nothing is stored; the fallback
        // only covers a value that is unset in both.
        return $this->settings->getInt('mail', 'daily_limit') ?? 500;
    }
}
```

Available methods: `getSection($section)` (all effective values), `get($section, $path)` (cast by schema type), `getInt()`, `getFloat()`, `getString()`, `getBool()` and `getList()` with an optional fallback for unset values. An unknown section throws `UnknownSectionException`, a path the schema does not declare throws `UnknownSettingException`.

## Audit log

The entity is `#[Gedmo\Loggable]` and its stored values (`storedValues`) are `#[Gedmo\Versioned]`. When the application registers a Gedmo loggable listener, every write adds a log entry with the action (`create` on the first write of a section, `update` afterwards), the new stored values and the acting user; a rejected write (`422`) adds none. Without a listener the attributes have no effect.

[`dmstr/doctrine-audit-log-bundle`](https://github.com/dmstr/doctrine-audit-log-bundle) provides such a listener and a log entry entity. The acting user comes from the application: it gives the listener (`gedmo.listener.loggable`) a `Gedmo\Tool\ActorProviderInterface` implementation, typically one that returns the identifier of the user in the security token, for example with a compiler pass that adds a `setActorProvider` call.

## Migrations

The bundle ships one migration, `Dmstr\AppSettings\Migrations\Version20260930163000`, which creates the table `dmstr_app_setting` (PostgreSQL). Run it with the application's migrations:

```bash
bin/console doctrine:migrations:migrate --no-interaction
```

The migration's class name is stored in the application's migrations table, so it will not be renamed.

## Testing

The test suite needs PHP 8.5 and Composer; the integration tests also need a PostgreSQL server.

```bash
composer install
vendor/bin/phpunit --testsuite unit
DATABASE_URL="postgresql://app:app@127.0.0.1:5432/app_settings_test?serverVersion=18&charset=utf8" \
    vendor/bin/phpunit --testsuite integration
```

- `./tests/Unit` needs neither a kernel nor a database.
- `./tests/Integration` boots the test kernel `./tests/Kernel/TestKernel.php`, a minimal host application with three test sections (`./tests/Fixtures`), a header-based test authenticator, a test permission voter and the audit-log bundle. The schema is created by running the migrations, this bundle's own one included. Tests in the group `database` need the server from `DATABASE_URL` (default in `./phpunit.dist.xml`); the others run without one: `vendor/bin/phpunit --testsuite integration --exclude-group database`.

CI runs both suites against a PostgreSQL service container (`./.github/workflows/tests.yml`).

## License

MIT, see [LICENSE](./LICENSE).
