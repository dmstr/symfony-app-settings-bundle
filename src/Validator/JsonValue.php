<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:45:00 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Validator;

/**
 * Converts decoded PHP values into the shape a JSON Schema validator
 * (opis/json-schema) expects: JSON objects as \stdClass, JSON arrays as PHP
 * lists.
 *
 * PHP cannot tell the JSON object `{}` from the JSON array `[]` once decoded,
 * and `json_decode(json_encode($value))` turns every empty array into a JSON
 * array, so an empty object in the stored values would fail `type: object`.
 * The conversion is therefore guided by the schema: an empty array becomes an
 * object where the schema expects an object and a list where it expects an
 * array.
 */
final class JsonValue
{
    /**
     * @param array<string, mixed>|null $schema schema of $value; null when unknown
     */
    public static function fromPhp(mixed $value, ?array $schema): mixed
    {
        return self::convert($value, $schema, true);
    }

    /**
     * @param array<string, mixed>|null $schema
     */
    private static function convert(mixed $value, ?array $schema, bool $isRoot): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        $type = self::typeOf($schema);

        if ([] === $value) {
            // Without a type, the root is an object (the stored values of a
            // section always are); nested values fall back to the list shape
            // json_encode() would produce.
            return match (true) {
                'object' === $type => new \stdClass(),
                'array' === $type => [],
                $isRoot && null === $type => new \stdClass(),
                default => [],
            };
        }

        if (array_is_list($value)) {
            $items = $schema['items'] ?? null;
            $converted = [];
            foreach ($value as $index => $item) {
                // `items` is either one schema for all items or, in older
                // drafts, a list of schemas per position (tuple validation).
                $itemSchema = \is_array($items) && array_is_list($items) && [] !== $items
                    ? ($items[$index] ?? null)
                    : $items;
                $converted[] = self::convert($item, \is_array($itemSchema) ? $itemSchema : null, false);
            }

            return $converted;
        }

        $properties = \is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $additional = \is_array($schema['additionalProperties'] ?? null) ? $schema['additionalProperties'] : null;

        $object = new \stdClass();
        foreach ($value as $name => $item) {
            $itemSchema = \is_array($properties[$name] ?? null) ? $properties[$name] : $additional;
            $object->{(string) $name} = self::convert($item, $itemSchema, false);
        }

        return $object;
    }

    /**
     * The schema type; for a list like `["object", "null"]` the first
     * non-null entry. Null when the schema declares no usable type.
     *
     * @param array<string, mixed>|null $schema
     */
    private static function typeOf(?array $schema): ?string
    {
        $type = $schema['type'] ?? null;
        if (\is_array($type)) {
            $type = array_values(array_filter($type, static fn (mixed $t): bool => 'null' !== $t))[0] ?? null;
        }

        return \is_string($type) ? $type : null;
    }
}
