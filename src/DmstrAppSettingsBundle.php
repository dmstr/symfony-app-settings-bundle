<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings;

use Dmstr\AppSettings\Contract\SettingsContributorInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Runtime application settings: the app and other bundles contribute
 * settings sections (one JSON schema each), the values are stored as JSON
 * per section and read back as typed values with schema defaults.
 */
final class DmstrAppSettingsBundle extends AbstractBundle
{
    public const string CONTRIBUTOR_TAG = 'dmstr_app_settings.contributor';

    protected string $extensionAlias = 'dmstr_app_settings';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Every service implementing SettingsContributorInterface is tagged
        // automatically and collected by the SectionRegistry.
        $container->registerForAutoconfiguration(SettingsContributorInterface::class)
            ->addTag(self::CONTRIBUTOR_TAG);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        // Empty configuration tree for now: the bundle is config-less.
    }

    /**
     * Registers the bundle's entities (ORM + API Platform) and migrations.
     * Prepended config has lower priority than the app's own config.
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'DmstrAppSettings' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => __DIR__.'/Entity',
                        'prefix' => 'Dmstr\\AppSettings\\Entity',
                        'alias' => 'DmstrAppSettings',
                    ],
                ],
            ],
        ]);

        $builder->prependExtensionConfig('api_platform', [
            'mapping' => [
                'paths' => [__DIR__.'/Entity'],
            ],
        ]);

        $builder->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => [
                'Dmstr\\AppSettings\\Migrations' => __DIR__.'/../migrations',
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__.'/../config/services.yaml');
    }
}
