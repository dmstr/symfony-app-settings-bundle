<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Contract;

/**
 * Contributes one settings section. Implementations are tagged
 * automatically (autoconfiguration) and collected by the SectionRegistry.
 *
 * There is deliberately no separate method for defaults: the defaults of a
 * section are derived from the property-level `default` keywords of its
 * schema. The schema is the single source, so the values an admin panel
 * prefills from the schema and the defaults the server applies always agree.
 */
interface SettingsContributorInterface
{
    /**
     * Section key: snake_case, ^[a-z][a-z0-9_]*$, at most 100 characters,
     * unique across the application.
     */
    public function getSectionKey(): string;

    /**
     * JSON Schema of the section as a PHP array: `type: object`,
     * `properties`, property-level `default` values, optional
     * `title`/`description`. `additionalProperties: false` is enforced by
     * the registry.
     *
     * @return array<string, mixed>
     */
    public function getSchema(): array;
}
