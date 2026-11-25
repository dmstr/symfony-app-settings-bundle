<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Registry;

use Dmstr\AppSettings\Contract\SettingsContributorInterface;
use Dmstr\AppSettings\Exception\DuplicateSectionKeyException;
use Dmstr\AppSettings\Exception\InvalidSectionException;
use Dmstr\AppSettings\Exception\UnknownSectionException;
use Symfony\Component\Uid\Uuid;

/**
 * Collects the settings sections of all contributors and builds their
 * definitions. Validation happens eagerly in the constructor, so an invalid
 * or duplicate section fails as soon as the registry is built.
 */
final class SectionRegistry
{
    /** Namespace of the deterministic (UUID v5) section ids. */
    public const string NAMESPACE = 'b7f3c2a1-5e4d-4c6b-9a8f-1e2d3c4b5a69';

    public const string KEY_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public const int KEY_MAX_LENGTH = 100;

    /** Name of the discriminator property added to every section schema. */
    public const string DISCRIMINATOR = 'section_key';

    /** @var array<string, SectionDefinition> sorted by key */
    private array $sections = [];

    /**
     * @param iterable<SettingsContributorInterface> $contributors
     */
    public function __construct(iterable $contributors)
    {
        $namespace = Uuid::fromString(self::NAMESPACE);
        /** @var array<string, class-string> $origins */
        $origins = [];

        foreach ($contributors as $contributor) {
            $key = $contributor->getSectionKey();

            if (\strlen($key) > self::KEY_MAX_LENGTH || 1 !== preg_match(self::KEY_PATTERN, $key)) {
                throw new InvalidSectionException(\sprintf('Invalid settings section key "%s" in %s: expected snake_case matching %s with at most %d characters.', $key, $contributor::class, self::KEY_PATTERN, self::KEY_MAX_LENGTH));
            }

            if (isset($origins[$key])) {
                throw new DuplicateSectionKeyException(\sprintf('Settings section key "%s" is contributed twice: by %s and by %s.', $key, $origins[$key], $contributor::class));
            }
            $origins[$key] = $contributor::class;

            $schema = $this->buildSchema($key, $contributor->getSchema());

            $this->sections[$key] = new SectionDefinition(
                key: $key,
                id: Uuid::v5($namespace, $key),
                schema: $schema,
                defaults: SchemaDefaults::extract($schema),
                title: (string) $schema['title'],
            );
        }

        ksort($this->sections, \SORT_STRING);
    }

    /**
     * @return array<string, SectionDefinition> sorted by key
     */
    public function all(): array
    {
        return $this->sections;
    }

    public function has(string $key): bool
    {
        return isset($this->sections[$key]);
    }

    public function get(string $key): SectionDefinition
    {
        return $this->sections[$key] ?? throw new UnknownSectionException(\sprintf('Unknown settings section "%s".', $key));
    }

    public function findById(Uuid|string $id): ?SectionDefinition
    {
        if (\is_string($id)) {
            if (!Uuid::isValid($id)) {
                return null;
            }
            $id = Uuid::fromString($id);
        }

        foreach ($this->sections as $section) {
            if ($section->id->equals($id)) {
                return $section;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function buildSchema(string $key, array $schema): array
    {
        $schema['type'] = 'object';
        $schema['additionalProperties'] ??= false;
        $schema['title'] ??= $key;

        $properties = \is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $properties[self::DISCRIMINATOR] = [
            'type' => 'string',
            'const' => $key,
            'default' => $key,
            'title' => 'Section key',
            'description' => 'Discriminates the section schema; always equals the section key.',
        ];
        $schema['properties'] = $properties;

        return $schema;
    }
}
