<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Unit\Merge;

use Dmstr\AppSettings\Merge\DeepMerge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeepMergeTest extends TestCase
{
    /**
     * @return iterable<string, array{array<mixed>, array<mixed>, array<mixed>}>
     */
    public static function cases(): iterable
    {
        yield 'empty override keeps base' => [['a' => 1], [], ['a' => 1]];
        yield 'scalar replaces' => [['a' => 1, 'b' => 2], ['a' => 3], ['a' => 3, 'b' => 2]];
        yield 'new key is added' => [['a' => 1], ['b' => 2], ['a' => 1, 'b' => 2]];
        yield 'nested objects merge' => [
            ['limits' => ['max' => 10, 'min' => 1]],
            ['limits' => ['max' => 20]],
            ['limits' => ['max' => 20, 'min' => 1]],
        ];
        yield 'list replaces list' => [['tags' => ['a', 'b', 'c']], ['tags' => ['x']], ['tags' => ['x']]];
        yield 'empty list replaces list' => [['tags' => ['a']], ['tags' => []], ['tags' => []]];
        yield 'object replaces scalar' => [['a' => 1], ['a' => ['b' => 2]], ['a' => ['b' => 2]]];
        yield 'scalar replaces object' => [['a' => ['b' => 2]], ['a' => 'x'], ['a' => 'x']];
        yield 'null in override is kept as value' => [['a' => 1], ['a' => null], ['a' => null]];
    }

    /**
     * @param array<mixed> $base
     * @param array<mixed> $override
     * @param array<mixed> $expected
     */
    #[DataProvider('cases')]
    public function testMerge(array $base, array $override, array $expected): void
    {
        self::assertSame($expected, DeepMerge::merge($base, $override));
    }
}
