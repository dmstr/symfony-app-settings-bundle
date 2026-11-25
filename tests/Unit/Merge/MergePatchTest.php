<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Unit\Merge;

use Dmstr\AppSettings\Merge\MergePatch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MergePatchTest extends TestCase
{
    /**
     * @return iterable<string, array{array<mixed>, array<mixed>, array<mixed>}>
     */
    public static function cases(): iterable
    {
        yield 'scalar replaces' => [['a' => 'b'], ['a' => 'c'], ['a' => 'c']];
        yield 'new key is added' => [['a' => 'b'], ['b' => 'c'], ['a' => 'b', 'b' => 'c']];
        yield 'null removes' => [['a' => 'b', 'b' => 'c'], ['a' => null], ['b' => 'c']];
        yield 'null on missing key is a no-op' => [['a' => 1], ['x' => null], ['a' => 1]];
        yield 'nested merge' => [
            ['limits' => ['max' => 10, 'min' => 1]],
            ['limits' => ['max' => 20, 'min' => null, 'step' => 2]],
            ['limits' => ['max' => 20, 'step' => 2]],
        ];
        yield 'list replaced' => [['tags' => ['a', 'b']], ['tags' => ['c']], ['tags' => ['c']]];
        yield 'object over scalar starts from empty' => [['a' => 'x'], ['a' => ['b' => 'c']], ['a' => ['b' => 'c']]];
        yield 'object over list starts from empty' => [['a' => [1, 2]], ['a' => ['b' => null, 'c' => 1]], ['a' => ['c' => 1]]];
        yield 'object replaced by list' => [['a' => ['b' => 'c']], ['a' => [1]], ['a' => [1]]];
        yield 'empty array keeps associative value' => [['a' => ['b' => 'c']], ['a' => []], ['a' => ['b' => 'c']]];
        yield 'empty array clears list' => [['a' => [1, 2]], ['a' => []], ['a' => []]];
        yield 'empty array replaces scalar' => [['a' => 'x'], ['a' => []], ['a' => []]];
        yield 'empty array sets missing key' => [[], ['a' => []], ['a' => []]];
        yield 'empty patch is a no-op' => [['a' => 1], [], ['a' => 1]];
    }

    /**
     * @param array<mixed> $target
     * @param array<mixed> $patch
     * @param array<mixed> $expected
     */
    #[DataProvider('cases')]
    public function testApply(array $target, array $patch, array $expected): void
    {
        self::assertSame($expected, MergePatch::apply($target, $patch));
    }
}
