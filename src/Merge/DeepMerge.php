<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Merge;

/**
 * Deep merge of settings values, used to lay stored values over defaults.
 */
final class DeepMerge
{
    /**
     * Merges $override over $base: where both sides hold associative arrays
     * the merge recurses; lists (`array_is_list`) and scalars in $override
     * replace the base value; keys present only in $base are kept.
     *
     * @param array<array-key, mixed> $base
     * @param array<array-key, mixed> $override
     *
     * @return array<array-key, mixed>
     */
    public static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                \is_array($value) && !array_is_list($value)
                && \array_key_exists($key, $base)
                && \is_array($base[$key]) && !array_is_list($base[$key])
            ) {
                $base[$key] = self::merge($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
