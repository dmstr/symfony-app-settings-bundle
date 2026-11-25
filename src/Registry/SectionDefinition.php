<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Registry;

use Symfony\Component\Uid\Uuid;

/**
 * A registered settings section as built by the SectionRegistry.
 */
final readonly class SectionDefinition
{
    /**
     * @param array<string, mixed> $schema   complete section schema (incl. the `section_key` discriminator)
     * @param array<string, mixed> $defaults defaults derived from the schema's `default` keywords
     */
    public function __construct(
        public string $key,
        public Uuid $id,
        public array $schema,
        public array $defaults,
        public string $title,
    ) {
    }
}
