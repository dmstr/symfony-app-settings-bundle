<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Unit\Reader;

use Dmstr\AppSettings\Tests\Unit\Registry\SectionRegistryTest;
use Dmstr\AppSettings\Entity\ApplicationSetting;
use Dmstr\AppSettings\Exception\UnknownSectionException;
use Dmstr\AppSettings\Exception\UnknownSettingException;
use Dmstr\AppSettings\Reader\SettingsReader;
use Dmstr\AppSettings\Registry\SectionRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class SettingsReaderTest extends TestCase
{
    private const array SCHEMA = [
        'type' => 'object',
        'properties' => [
            'model' => ['type' => 'string', 'default' => 'small'],
            'temperature' => ['type' => 'number', 'default' => 0.5],
            'enabled' => ['type' => 'boolean', 'default' => true],
            'note' => ['type' => ['string', 'null']],
            'tags' => ['type' => 'array', 'default' => ['a', 'b']],
            'limits' => [
                'type' => 'object',
                'properties' => [
                    'max_results' => ['type' => 'integer', 'default' => 10],
                    'min_score' => ['type' => 'number'],
                ],
            ],
        ],
    ];

    /**
     * @param array<string, mixed>|null $stored stored values of the row; null: no row
     */
    private function reader(?array $stored): SettingsReader
    {
        $registry = new SectionRegistry([SectionRegistryTest::contributor('chat', self::SCHEMA)]);

        $row = null;
        if (null !== $stored) {
            $row = new ApplicationSetting('chat', $registry->get('chat')->id);
            $row->setStoredValues($stored);
        }

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?ApplicationSetting => ['sectionKey' => 'chat'] === $criteria ? $row : null,
        );

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => ApplicationSetting::class === $class ? $repository : self::fail('Unexpected repository '.$class),
        );

        return new SettingsReader($registry, $entityManager);
    }

    public function testDefaultsBeforeAnyRow(): void
    {
        self::assertSame([
            'model' => 'small',
            'temperature' => 0.5,
            'enabled' => true,
            'tags' => ['a', 'b'],
            'limits' => ['max_results' => 10],
            'section_key' => 'chat',
        ], $this->reader(null)->getSection('chat'));
    }

    public function testStoredValuesOverrideDefaults(): void
    {
        $reader = $this->reader([
            'model' => 'large',
            'tags' => ['x'],
            'limits' => ['min_score' => 0.7],
        ]);

        self::assertSame([
            'model' => 'large',
            'temperature' => 0.5,
            'enabled' => true,
            'tags' => ['x'],
            'limits' => ['max_results' => 10, 'min_score' => 0.7],
            'section_key' => 'chat',
        ], $reader->getSection('chat'));
    }

    public function testGetCastsPerSchemaType(): void
    {
        $reader = $this->reader([
            'temperature' => 1,
            'enabled' => 'false',
            'note' => 12,
            'limits' => ['max_results' => '25'],
        ]);

        self::assertSame('small', $reader->get('chat', 'model'));
        self::assertSame(1.0, $reader->get('chat', 'temperature'));
        self::assertFalse($reader->get('chat', 'enabled'));
        self::assertSame('12', $reader->get('chat', 'note'));
        self::assertSame(['a', 'b'], $reader->get('chat', 'tags'));
        self::assertSame(25, $reader->get('chat', 'limits.max_results'));
        self::assertSame(['max_results' => '25'], $reader->get('chat', 'limits'));
        self::assertSame('chat', $reader->get('chat', 'section_key'));
    }

    public function testTypedAccessors(): void
    {
        $reader = $this->reader(['limits' => ['min_score' => '0.25']]);

        self::assertSame(10, $reader->getInt('chat', 'limits.max_results'));
        self::assertSame(0.25, $reader->getFloat('chat', 'limits.min_score'));
        self::assertSame(0.5, $reader->getFloat('chat', 'temperature'));
        self::assertSame('small', $reader->getString('chat', 'model'));
        self::assertTrue($reader->getBool('chat', 'enabled'));
        self::assertSame(['a', 'b'], $reader->getList('chat', 'tags'));
    }

    public function testFallbackWhenValueIsNull(): void
    {
        $reader = $this->reader(null);

        self::assertNull($reader->get('chat', 'note'));
        self::assertNull($reader->getString('chat', 'note'));
        self::assertSame('none', $reader->getString('chat', 'note', 'none'));
        self::assertSame(0.3, $reader->getFloat('chat', 'limits.min_score', 0.3));
        self::assertSame(10, $reader->getInt('chat', 'limits.max_results', 99));
    }

    public function testUnknownPathThrows(): void
    {
        $this->expectException(UnknownSettingException::class);

        $this->reader(null)->get('chat', 'limits.unknown');
    }

    public function testPathIntoScalarThrows(): void
    {
        $this->expectException(UnknownSettingException::class);

        $this->reader(null)->get('chat', 'model.length');
    }

    public function testUnknownSectionThrows(): void
    {
        $this->expectException(UnknownSectionException::class);

        $this->reader(null)->getSection('other');
    }
}
