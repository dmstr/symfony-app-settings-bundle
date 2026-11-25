<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Registry;

/**
 * Derives default values from the property-level `default` keywords of an
 * object schema.
 */
final class SchemaDefaults
{
    /**
     * Collects `properties.*.default`. A nested property of `type: object`
     * without its own `default` is recursed into; its result is included
     * only when it is non-empty.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    public static function extract(array $schema): array
    {
        $properties = $schema['properties'] ?? null;
        if (!\is_array($properties)) {
            return [];
        }

        $defaults = [];
        foreach ($properties as $name => $property) {
            if (!\is_array($property)) {
                continue;
            }

            if (\array_key_exists('default', $property)) {
                $defaults[$name] = $property['default'];

                continue;
            }

            if (($property['type'] ?? null) === 'object') {
                $nested = self::extract($property);
                if ([] !== $nested) {
                    $defaults[$name] = $nested;
                }
            }
        }

        return $defaults;
    }
}
