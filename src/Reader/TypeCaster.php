<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Reader;

/**
 * Casts a settings value to the PHP type matching its JSON Schema type.
 */
final class TypeCaster
{
    /**
     * `integer` -> int, `number` -> float, `boolean` -> bool,
     * `string` -> string, `array` -> list, `object` -> array. `null` stays
     * `null`; any other JSON type (or an empty one) returns the value
     * unchanged.
     *
     * @throws \UnexpectedValueException when the value cannot be cast sensibly
     */
    public static function cast(mixed $value, string $jsonType): mixed
    {
        if (null === $value) {
            return null;
        }

        return match ($jsonType) {
            'integer' => self::toInt($value),
            'number' => self::toFloat($value),
            'boolean' => self::toBool($value),
            'string' => self::toString($value),
            'array' => \is_array($value) ? array_values($value) : throw self::fail($value, $jsonType),
            'object' => \is_array($value) ? $value : throw self::fail($value, $jsonType),
            default => $value,
        };
    }

    private static function toInt(mixed $value): int
    {
        if (\is_int($value)) {
            return $value;
        }
        if (\is_float($value) && is_finite($value) && floor($value) === $value) {
            return (int) $value;
        }
        if (\is_string($value)) {
            $int = filter_var(trim($value), \FILTER_VALIDATE_INT);
            if (false !== $int) {
                return $int;
            }
        }

        throw self::fail($value, 'integer');
    }

    private static function toFloat(mixed $value): float
    {
        if (\is_int($value) || \is_float($value)) {
            return (float) $value;
        }
        if (\is_string($value) && is_numeric(trim($value))) {
            return (float) trim($value);
        }

        throw self::fail($value, 'number');
    }

    private static function toBool(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }
        if (\is_int($value) || \is_string($value)) {
            $bool = filter_var($value, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);
            if (null !== $bool) {
                return $bool;
            }
        }

        throw self::fail($value, 'boolean');
    }

    private static function toString(mixed $value): string
    {
        if (\is_string($value)) {
            return $value;
        }
        if (\is_int($value) || \is_float($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw self::fail($value, 'string');
    }

    private static function fail(mixed $value, string $jsonType): \UnexpectedValueException
    {
        return new \UnexpectedValueException(\sprintf('Cannot cast a value of type %s to JSON type "%s".', get_debug_type($value), $jsonType));
    }
}
