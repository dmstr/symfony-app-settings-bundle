<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:45:00 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\OpenApi;

use Dmstr\AppSettings\Registry\SectionRegistry;

/**
 * Static hand-over of the SectionRegistry to
 * {@see \Dmstr\AppSettings\Entity\ApplicationSetting::getJsonSchema()}.
 *
 * `JsonFieldSchemaDecorator` (dmstr/openapi-json-schema-bundle) calls the
 * entity's schema method statically, without service access. The registry
 * is primed by {@see SectionSchemaFactoryDecorator} when the schema factory
 * chain is constructed, i.e. before any schema is built.
 */
final class SectionSchemaHolder
{
    private static ?SectionRegistry $registry = null;

    public static function setRegistry(?SectionRegistry $registry): void
    {
        self::$registry = $registry;
    }

    public static function getRegistry(): ?SectionRegistry
    {
        return self::$registry;
    }
}
