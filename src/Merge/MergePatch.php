<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Merge;

/**
 * RFC 7396 JSON Merge Patch on decoded PHP arrays.
 */
final class MergePatch
{
    /**
     * Applies $patch to $target. For each key in $patch:
     *
     * - `null` removes the key from the target;
     * - an associative (non-list, non-empty) array is merged recursively; if
     *   the target value is not an associative array, the merge starts from `[]`;
     * - a list or a scalar replaces the target value.
     *
     * PHP cannot distinguish the JSON object `{}` from the JSON array `[]`
     * once decoded. An EMPTY array in the patch is therefore treated as an
     * empty object where that is harmless: it leaves an existing associative
     * target value unchanged (merging `{}` is a no-op) and otherwise sets the
     * key to `[]`.
     *
     * @param array<array-key, mixed> $target
     * @param array<array-key, mixed> $patch
     *
     * @return array<array-key, mixed>
     */
    public static function apply(array $target, array $patch): array
    {
        foreach ($patch as $key => $value) {
            if (null === $value) {
                unset($target[$key]);

                continue;
            }

            if ([] === $value) {
                if (!self::isAssociative($target[$key] ?? null)) {
                    $target[$key] = [];
                }

                continue;
            }

            if (\is_array($value) && !array_is_list($value)) {
                $current = $target[$key] ?? null;
                $target[$key] = self::apply(self::isAssociative($current) ? $current : [], $value);

                continue;
            }

            $target[$key] = $value;
        }

        return $target;
    }

    /**
     * @phpstan-assert-if-true array<array-key, mixed> $value
     */
    private static function isAssociative(mixed $value): bool
    {
        return \is_array($value) && [] !== $value && !array_is_list($value);
    }
}
