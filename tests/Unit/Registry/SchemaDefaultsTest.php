<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Unit\Registry;

use Dmstr\AppSettings\Registry\SchemaDefaults;
use PHPUnit\Framework\TestCase;

class SchemaDefaultsTest extends TestCase
{
    public function testCollectsPropertyDefaults(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'enabled' => ['type' => 'boolean', 'default' => true],
                'name' => ['type' => 'string'],
                'tags' => ['type' => 'array', 'default' => ['a']],
                'nothing' => ['type' => 'string', 'default' => null],
            ],
        ];

        self::assertSame(['enabled' => true, 'tags' => ['a'], 'nothing' => null], SchemaDefaults::extract($schema));
    }

    public function testRecursesIntoNestedObjectsWithoutOwnDefault(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'limits' => [
                    'type' => 'object',
                    'properties' => [
                        'max' => ['type' => 'integer', 'default' => 10],
                        'deep' => [
                            'type' => 'object',
                            'properties' => ['x' => ['type' => 'number', 'default' => 1.5]],
                        ],
                    ],
                ],
                'empty' => [
                    'type' => 'object',
                    'properties' => ['y' => ['type' => 'string']],
                ],
                'own' => [
                    'type' => 'object',
                    'default' => ['z' => 1],
                    'properties' => ['z' => ['type' => 'integer', 'default' => 2]],
                ],
            ],
        ];

        self::assertSame([
            'limits' => ['max' => 10, 'deep' => ['x' => 1.5]],
            'own' => ['z' => 1],
        ], SchemaDefaults::extract($schema));
    }

    public function testSchemaWithoutPropertiesHasNoDefaults(): void
    {
        self::assertSame([], SchemaDefaults::extract(['type' => 'object']));
    }
}
