<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Unit\Registry;

use Dmstr\AppSettings\Contract\SettingsContributorInterface;
use Dmstr\AppSettings\Exception\DuplicateSectionKeyException;
use Dmstr\AppSettings\Exception\InvalidSectionException;
use Dmstr\AppSettings\Exception\UnknownSectionException;
use Dmstr\AppSettings\Registry\SectionRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class SectionRegistryTest extends TestCase
{
    /**
     * @param array<string, mixed> $schema
     */
    public static function contributor(string $key, array $schema = []): SettingsContributorInterface
    {
        return new class($key, $schema) implements SettingsContributorInterface {
            /**
             * @param array<string, mixed> $schema
             */
            public function __construct(private readonly string $key, private readonly array $schema)
            {
            }

            public function getSectionKey(): string
            {
                return $this->key;
            }

            public function getSchema(): array
            {
                return $this->schema;
            }
        };
    }

    public function testAllIsSortedByKey(): void
    {
        $registry = new SectionRegistry([
            self::contributor('search'),
            self::contributor('chat'),
            self::contributor('ingest'),
        ]);

        self::assertSame(['chat', 'ingest', 'search'], array_keys($registry->all()));
        self::assertTrue($registry->has('chat'));
        self::assertFalse($registry->has('other'));
    }

    public function testIdIsDeterministicAcrossRegistries(): void
    {
        $first = new SectionRegistry([self::contributor('chat')]);
        $second = new SectionRegistry([self::contributor('search'), self::contributor('chat')]);

        $id = $first->get('chat')->id;
        self::assertTrue($id->equals($second->get('chat')->id));
        self::assertTrue($id->equals(Uuid::v5(Uuid::fromString(SectionRegistry::NAMESPACE), 'chat')));
        self::assertFalse($id->equals($second->get('search')->id));
    }

    public function testSectionSchemaIsCompleted(): void
    {
        $registry = new SectionRegistry([self::contributor('chat', [
            'type' => 'array',
            'properties' => ['model' => ['type' => 'string', 'default' => 'small']],
        ])]);

        $section = $registry->get('chat');

        self::assertSame('chat', $section->key);
        self::assertSame('object', $section->schema['type']);
        self::assertFalse($section->schema['additionalProperties']);
        self::assertSame('chat', $section->schema['title']);
        self::assertSame('chat', $section->title);
        self::assertSame([
            'type' => 'string',
            'const' => 'chat',
            'default' => 'chat',
            'title' => 'Section key',
            'description' => 'Discriminates the section schema; always equals the section key.',
        ], $section->schema['properties']['section_key']);
        self::assertSame(['model' => 'small', 'section_key' => 'chat'], $section->defaults);
    }

    public function testExplicitTitleAndAdditionalPropertiesAreKept(): void
    {
        $registry = new SectionRegistry([self::contributor('chat', [
            'title' => 'Chat',
            'additionalProperties' => true,
        ])]);

        $section = $registry->get('chat');

        self::assertSame('Chat', $section->title);
        self::assertTrue($section->schema['additionalProperties']);
    }

    public function testDuplicateKeyThrowsWithBothContributorClasses(): void
    {
        $first = self::contributor('chat');
        $second = new class implements SettingsContributorInterface {
            public function getSectionKey(): string
            {
                return 'chat';
            }

            public function getSchema(): array
            {
                return [];
            }
        };

        try {
            new SectionRegistry([$first, $second]);
            self::fail('Expected DuplicateSectionKeyException.');
        } catch (DuplicateSectionKeyException $e) {
            self::assertStringContainsString($first::class, $e->getMessage());
            self::assertStringContainsString($second::class, $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidKeys(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Chat'];
        yield 'leading digit' => ['1chat'];
        yield 'leading underscore' => ['_chat'];
        yield 'dash' => ['chat-model'];
        yield 'too long' => [str_repeat('a', 101)];
    }

    #[DataProvider('invalidKeys')]
    public function testInvalidKeyThrows(string $key): void
    {
        $this->expectException(InvalidSectionException::class);

        new SectionRegistry([self::contributor($key)]);
    }

    public function testKeyOfMaximumLengthIsAccepted(): void
    {
        $key = str_repeat('a', 100);

        self::assertTrue((new SectionRegistry([self::contributor($key)]))->has($key));
    }

    public function testGetUnknownSectionThrows(): void
    {
        $this->expectException(UnknownSectionException::class);

        (new SectionRegistry([]))->get('chat');
    }

    public function testFindById(): void
    {
        $registry = new SectionRegistry([self::contributor('chat'), self::contributor('search')]);
        $id = $registry->get('search')->id;

        self::assertSame('search', $registry->findById($id)?->key);
        self::assertSame('search', $registry->findById($id->toRfc4122())?->key);
        self::assertNull($registry->findById(Uuid::v4()));
        self::assertNull($registry->findById('not-a-uuid'));
    }
}
