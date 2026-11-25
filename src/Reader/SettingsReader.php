<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Reader;

use Dmstr\AppSettings\Entity\ApplicationSetting;
use Dmstr\AppSettings\Exception\UnknownSectionException;
use Dmstr\AppSettings\Exception\UnknownSettingException;
use Dmstr\AppSettings\Merge\DeepMerge;
use Dmstr\AppSettings\Registry\SectionRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Reads effective settings values: the stored values of a section merged
 * over the defaults of its schema. Paths are dotted property names within a
 * section, e.g. `limits.max_results`.
 */
final class SettingsReader
{
    public function __construct(
        private readonly SectionRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Effective values of a section: the stored values merged over the
     * section defaults; only the defaults while no row is stored.
     *
     * @return array<string, mixed>
     *
     * @throws UnknownSectionException
     */
    public function getSection(string $section): array
    {
        $definition = $this->registry->get($section);

        $row = $this->entityManager->getRepository(ApplicationSetting::class)
            ->findOneBy(['sectionKey' => $section]);

        return DeepMerge::merge($definition->defaults, $row?->getStoredValues() ?? []);
    }

    /**
     * Effective value at $path, cast to the type the schema declares there.
     *
     * @throws UnknownSectionException
     * @throws UnknownSettingException    when the schema has no property at $path
     * @throws \UnexpectedValueException when the value cannot be cast
     */
    public function get(string $section, string $path): mixed
    {
        [$value, $type] = $this->resolve($section, $path);

        return TypeCaster::cast($value, $type);
    }

    public function getInt(string $section, string $path, ?int $fallback = null): ?int
    {
        $value = $this->resolve($section, $path)[0];

        return null === $value ? $fallback : TypeCaster::cast($value, 'integer');
    }

    public function getFloat(string $section, string $path, ?float $fallback = null): ?float
    {
        $value = $this->resolve($section, $path)[0];

        return null === $value ? $fallback : TypeCaster::cast($value, 'number');
    }

    public function getString(string $section, string $path, ?string $fallback = null): ?string
    {
        $value = $this->resolve($section, $path)[0];

        return null === $value ? $fallback : TypeCaster::cast($value, 'string');
    }

    public function getBool(string $section, string $path, ?bool $fallback = null): ?bool
    {
        $value = $this->resolve($section, $path)[0];

        return null === $value ? $fallback : TypeCaster::cast($value, 'boolean');
    }

    /**
     * @param list<mixed>|null $fallback
     *
     * @return list<mixed>|null
     */
    public function getList(string $section, string $path, ?array $fallback = null): ?array
    {
        $value = $this->resolve($section, $path)[0];

        return null === $value ? $fallback : TypeCaster::cast($value, 'array');
    }

    /**
     * Walks the section schema and the effective values along $path.
     *
     * @return array{0: mixed, 1: string} effective value (null when unset) and schema type
     */
    private function resolve(string $section, string $path): array
    {
        $node = $this->registry->get($section)->schema;
        $value = $this->getSection($section);

        foreach (explode('.', $path) as $segment) {
            $properties = $node['properties'] ?? null;
            if ('' === $segment || !\is_array($properties) || !\is_array($properties[$segment] ?? null)) {
                throw new UnknownSettingException(\sprintf('Unknown setting "%s" in section "%s".', $path, $section));
            }
            $node = $properties[$segment];
            $value = \is_array($value) && \array_key_exists($segment, $value) ? $value[$segment] : null;
        }

        return [$value, self::typeOf($node)];
    }

    /**
     * @param array<string, mixed> $node
     */
    private static function typeOf(array $node): string
    {
        $type = $node['type'] ?? '';
        if (\is_array($type)) {
            // e.g. ["string", "null"]: the first non-null type wins
            $type = array_values(array_filter($type, static fn (mixed $t): bool => 'null' !== $t))[0] ?? '';
        }

        return \is_string($type) ? $type : '';
    }
}
