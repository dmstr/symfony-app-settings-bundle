<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Kernel;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Dmstr\AppSettings\DmstrAppSettingsBundle;
use Dmstr\AppSettings\Tests\Fixtures\GeneralSection;
use Dmstr\AppSettings\Tests\Fixtures\LimitsSection;
use Dmstr\AppSettings\Tests\Fixtures\RetentionSection;
use Dmstr\DoctrineAuditLog\DoctrineAuditLogBundle;
use Dmstr\DoctrineAuditLog\Entity\LogEntry;
use Dmstr\OpenApiJsonSchema\OpenApiJsonSchemaBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

/**
 * Minimal host application for the integration tests of the bundle.
 *
 * - PostgreSQL from the `DATABASE_URL` environment variable (default in
 *   `phpunit.dist.xml`). The schema is created by running the migrations,
 *   the bundle's own one included ({@see TestDatabase}); the container
 *   itself never connects, so tests without the `database` group run
 *   without a server.
 * - Three neutral test sections ({@see GeneralSection}, {@see LimitsSection},
 *   {@see RetentionSection}), registered through autoconfiguration only.
 * - A stateless firewall over every path, authenticated from request
 *   headers by {@see TestHeaderAuthenticator}.
 * - The audit log: dmstr/doctrine-audit-log-bundle provides the Gedmo
 *   loggable listener and the log entry entity; the kernel hands the
 *   listener {@see TestActorProvider} (see {@see self::process()}).
 * - The permission contract of a host application: {@see self::process()}
 *   collects the `za7_core.permission` tags into the parameter
 *   `za7_core.permissions.registered`, {@see self::PERMISSIONS} is the map
 *   parameter `za7_core.permissions`, and {@see TestPermissionVoter} decides
 *   with both. Two variants, selected by the kernel environment:
 *   {@see self::ENV_WITH_VOTER} registers the voter, {@see self::ENV_NO_VOTER}
 *   registers no custom voter at all.
 *
 * Cache and logs live under the system temp directory, one directory per
 * variant, so a test run leaves no files in the repository.
 */
final class TestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public const string ENV_WITH_VOTER = 'test';
    public const string ENV_NO_VOTER = 'test_no_voter';

    public const string API_PREFIX = '/api';

    public const string PERMISSION_TAG = 'za7_core.permission';
    public const string PERMISSION_MAP_PARAMETER = 'za7_core.permissions';
    public const string PERMISSIONS_REGISTERED_PARAMETER = 'za7_core.permissions.registered';

    /** Permission => roles that hold it, as a host application would map it */
    public const array PERMISSIONS = [
        'appsettings:setting:manage' => ['ROLE_ADMIN'],
    ];

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SecurityBundle();
        yield new DoctrineBundle();
        yield new DoctrineMigrationsBundle();
        yield new ApiPlatformBundle();
        yield new OpenApiJsonSchemaBundle();
        yield new DoctrineAuditLogBundle();
        yield new DmstrAppSettingsBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return $this->tmpDir().'/cache';
    }

    public function getLogDir(): string
    {
        return $this->tmpDir().'/log';
    }

    public function hasPermissionVoter(): bool
    {
        return self::ENV_NO_VOTER !== $this->environment;
    }

    /**
     * Host-application wiring that needs the complete container: the list of
     * registered permissions and the actor of audit-log entries.
     */
    public function process(ContainerBuilder $container): void
    {
        $registered = [];
        foreach ($container->findTaggedServiceIds(self::PERMISSION_TAG) as $tags) {
            foreach ($tags as $attributes) {
                if (\is_string($attributes['permission'] ?? null)) {
                    $registered[$attributes['permission']] = true;
                }
            }
        }
        $registered = array_keys($registered);
        sort($registered);
        $container->setParameter(self::PERMISSIONS_REGISTERED_PARAMETER, $registered);

        if ($container->hasDefinition('gedmo.listener.loggable')) {
            $container->getDefinition('gedmo.listener.loggable')
                ->addMethodCall('setActorProvider', [new Reference(TestActorProvider::class)]);
        }
    }

    /**
     * Not the repository's `config/` (the bundle's service definitions): in
     * debug mode FrameworkBundle dumps `reference.php` into an existing
     * config directory. This one does not exist, so nothing is written.
     */
    private function getConfigDir(): string
    {
        return $this->tmpDir().'/config';
    }

    private function tmpDir(): string
    {
        return sys_get_temp_dir().'/dmstr-app-settings-bundle-tests/'.$this->environment;
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $container->parameters()
            ->set(self::PERMISSION_MAP_PARAMETER, self::PERMISSIONS);

        $container->extension('framework', [
            'test' => true,
            'secret' => 'test-secret',
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'cache' => ['app' => 'cache.adapter.array'],
        ]);

        $container->extension('security', [
            'providers' => [
                'test_users' => ['memory' => ['users' => []]],
            ],
            'firewalls' => [
                'main' => [
                    'pattern' => '^/',
                    'stateless' => true,
                    'provider' => 'test_users',
                    'custom_authenticators' => [TestHeaderAuthenticator::class],
                ],
            ],
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'url' => '%env(resolve:DATABASE_URL)%',
            ],
            'orm' => [
                'enable_native_lazy_objects' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                // The bundle maps its own entity (prepended configuration);
                // only the audit-log entity and its Gedmo superclass are
                // mapped here, as a host application would.
                'mappings' => [
                    'DoctrineAuditLog' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => \dirname((string) (new \ReflectionClass(LogEntry::class))->getFileName()),
                        'prefix' => 'Dmstr\\DoctrineAuditLog\\Entity',
                    ],
                    'GedmoLoggable' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => \dirname((string) (new \ReflectionClass(AbstractLogEntry::class))->getFileName()),
                        'prefix' => 'Gedmo\\Loggable\\Entity\\MappedSuperclass',
                    ],
                ],
            ],
        ]);

        $container->extension('doctrine_migrations', [
            'storage' => [
                'table_storage' => ['table_name' => 'doctrine_migration_versions'],
            ],
        ]);

        $container->extension('api_platform', [
            'title' => 'app-settings-bundle tests',
            'formats' => [
                'jsonld' => ['application/ld+json'],
                'json' => ['application/json'],
            ],
            'docs_formats' => [
                'jsonld' => ['application/ld+json'],
                'jsonopenapi' => ['application/vnd.openapi+json'],
            ],
            'patch_formats' => [
                'json' => ['application/merge-patch+json'],
            ],
            'serializer' => ['hydra_prefix' => true],
            'defaults' => ['stateless' => true],
            'enable_swagger_ui' => false,
            'enable_re_doc' => false,
        ]);

        $services = $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure();

        // Keep the test output free of framework log lines
        $services->set('logger', NullLogger::class);

        // Autoconfiguration tags them as settings contributors
        $services->set(GeneralSection::class);
        $services->set(LimitsSection::class);
        $services->set(RetentionSection::class);

        $services->set(TestHeaderAuthenticator::class);
        $services->set(TestActorProvider::class);

        if ($this->hasPermissionVoter()) {
            // Autoconfiguration adds the `security.voter` tag; public so that
            // tests can tell the two variants apart
            $services->set(TestPermissionVoter::class)
                ->args([param(self::PERMISSION_MAP_PARAMETER), param(self::PERMISSIONS_REGISTERED_PARAMETER)])
                ->public();
        }
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('.', 'api_platform')->prefix(self::API_PREFIX);
    }
}
