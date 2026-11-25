<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:45:00 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dmstr\AppSettings\Entity\ApplicationSetting;
use Dmstr\AppSettings\Registry\SectionRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides all registered settings sections for `GetCollection`, in key
 * order, one entry per section whether or not it has been stored yet.
 *
 * The stored rows are loaded in one query; sections without a row appear as
 * fresh, unmanaged entities. All entries carry their section defaults, so
 * the list shows the effective values. Rows of sections that are no longer
 * registered are not listed.
 *
 * @implements ProviderInterface<ApplicationSetting>
 */
final class SectionCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly SectionRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<ApplicationSetting>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $definitions = $this->registry->all();
        if ([] === $definitions) {
            return [];
        }

        $stored = [];
        $rows = $this->entityManager->getRepository(ApplicationSetting::class)
            ->findBy(['sectionKey' => array_keys($definitions)]);
        foreach ($rows as $row) {
            $stored[$row->getSectionKey()] = $row;
        }

        $settings = [];
        foreach ($definitions as $key => $definition) {
            $setting = $stored[$key] ?? new ApplicationSetting($key, $definition->id);
            $setting->attachDefaults($definition->defaults);
            $settings[] = $setting;
        }

        return $settings;
    }
}
