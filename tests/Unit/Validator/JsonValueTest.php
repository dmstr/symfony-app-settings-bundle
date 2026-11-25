<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Unit\Validator;

use Dmstr\AppSettings\Validator\JsonValue;
use PHPUnit\Framework\TestCase;

final class JsonValueTest extends TestCase
{
    public function testEmptyArrayBecomesObjectForObjectSchema(): void
    {
        self::assertEquals(new \stdClass(), JsonValue::fromPhp([], ['type' => 'object']));
    }

    public function testEmptyArrayBecomesListForArraySchema(): void
    {
        self::assertSame([], JsonValue::fromPhp([], ['type' => 'array']));
    }

    public function testNullableTypeListUsesFirstNonNullType(): void
    {
        self::assertEquals(new \stdClass(), JsonValue::fromPhp([], ['type' => ['null', 'object']]));
        self::assertSame([], JsonValue::fromPhp([], ['type' => ['array', 'null']]));
    }

    public function testEmptyArrayAtUnknownRootSchemaBecomesObject(): void
    {
        self::assertEquals(new \stdClass(), JsonValue::fromPhp([], null));
        self::assertEquals(new \stdClass(), JsonValue::fromPhp([], []));
    }

    public function testScalarsPassThrough(): void
    {
        self::assertSame(42, JsonValue::fromPhp(42, ['type' => 'integer']));
        self::assertSame('abc', JsonValue::fromPhp('abc', null));
        self::assertTrue(JsonValue::fromPhp(true, ['type' => 'boolean']));
        self::assertNull(JsonValue::fromPhp(null, ['type' => 'object']));
        self::assertSame(1.5, JsonValue::fromPhp(1.5, ['type' => 'number']));
    }

    public function testAssociativeArrayBecomesObjectAndRecursesThroughProperties(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'limits' => ['type' => 'object'],
                'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                'filters' => ['type' => 'array', 'items' => ['type' => 'object']],
                'name' => ['type' => 'string'],
            ],
        ];

        $result = JsonValue::fromPhp([
            'limits' => [],
            'tags' => [],
            'filters' => [[], ['field' => 'a']],
            'name' => 'x',
        ], $schema);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertEquals(new \stdClass(), $result->limits);
        self::assertSame([], $result->tags);
        self::assertIsArray($result->filters);
        self::assertEquals(new \stdClass(), $result->filters[0]);
        self::assertEquals((object) ['field' => 'a'], $result->filters[1]);
        self::assertSame('x', $result->name);
    }

    public function testNonEmptyListStaysListEvenForObjectSchema(): void
    {
        // The validator reports the type error; the conversion keeps the shape.
        self::assertSame([1, 2], JsonValue::fromPhp([1, 2], ['type' => 'object']));
    }

    public function testAdditionalPropertiesSchemaIsUsedForUnlistedKeys(): void
    {
        $result = JsonValue::fromPhp(['a' => []], ['type' => 'object', 'additionalProperties' => ['type' => 'object']]);

        self::assertEquals(new \stdClass(), $result->a);
    }

    public function testNestedEmptyArrayWithoutSchemaBecomesList(): void
    {
        $result = JsonValue::fromPhp(['unknown' => []], ['type' => 'object', 'properties' => []]);

        self::assertSame([], $result->unknown);
    }
}
