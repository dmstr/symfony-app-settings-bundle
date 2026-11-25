<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Unit\Reader;

use Dmstr\AppSettings\Reader\TypeCaster;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TypeCasterTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, string, mixed}>
     */
    public static function castable(): iterable
    {
        yield 'null stays null' => [null, 'integer', null];
        yield 'int' => [5, 'integer', 5];
        yield 'integral float to int' => [5.0, 'integer', 5];
        yield 'numeric string to int' => [' 42 ', 'integer', 42];
        yield 'int to float' => [3, 'number', 3.0];
        yield 'numeric string to float' => ['1.5', 'number', 1.5];
        yield 'bool' => [false, 'boolean', false];
        yield 'int to bool' => [1, 'boolean', true];
        yield 'string to bool' => ['false', 'boolean', false];
        yield 'string' => ['x', 'string', 'x'];
        yield 'int to string' => [7, 'string', '7'];
        yield 'array to list' => [['a' => 1, 'b' => 2], 'array', [1, 2]];
        yield 'object stays array' => [['a' => 1], 'object', ['a' => 1]];
        yield 'unknown type passes through' => [['x'], '', ['x']];
    }

    #[DataProvider('castable')]
    public function testCast(mixed $value, string $type, mixed $expected): void
    {
        self::assertSame($expected, TypeCaster::cast($value, $type));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function notCastable(): iterable
    {
        yield 'non-numeric string to int' => ['abc', 'integer'];
        yield 'fractional float to int' => [1.5, 'integer'];
        yield 'bool to int' => [true, 'integer'];
        yield 'non-numeric string to float' => ['1.5x', 'number'];
        yield 'arbitrary string to bool' => ['maybe', 'boolean'];
        yield 'int 2 to bool' => [2, 'boolean'];
        yield 'array to string' => [['a'], 'string'];
        yield 'bool to string' => [true, 'string'];
        yield 'string to array' => ['a', 'array'];
        yield 'int to object' => [1, 'object'];
    }

    #[DataProvider('notCastable')]
    public function testCastFails(mixed $value, string $type): void
    {
        $this->expectException(\UnexpectedValueException::class);

        TypeCaster::cast($value, $type);
    }
}
