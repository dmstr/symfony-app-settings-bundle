<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:45:00 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dmstr\AppSettings\Entity\ApplicationSetting;
use Dmstr\AppSettings\Registry\SectionRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Provides one settings section for `Get` and `Patch`.
 *
 * The id in the URI is the deterministic section id (UUID v5 of the section
 * key). Unknown ids yield null, which API Platform answers with 404. A
 * registered section without a stored row is returned as a fresh, unmanaged
 * entity with the section defaults, so it can be read and patched before its
 * first write; the default Doctrine persist processor persists it on the
 * first PATCH ("record created on first write").
 *
 * @implements ProviderInterface<ApplicationSetting>
 */
final class SectionItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly SectionRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ApplicationSetting
    {
        $id = $uriVariables['id'] ?? null;
        if (!$id instanceof Uuid && !\is_string($id)) {
            return null;
        }

        $definition = $this->registry->findById($id);
        if (null === $definition) {
            return null;
        }

        $setting = $this->entityManager->getRepository(ApplicationSetting::class)
            ->findOneBy(['sectionKey' => $definition->key])
            ?? new ApplicationSetting($definition->key, $definition->id);

        $setting->attachDefaults($definition->defaults);

        return $setting;
    }
}
