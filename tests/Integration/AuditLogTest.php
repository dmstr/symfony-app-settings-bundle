<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dmstr\AppSettings\Entity\ApplicationSetting;
use Dmstr\DoctrineAuditLog\Entity\LogEntry;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Settings writes are audited (Gedmo Loggable on ApplicationSetting,
 * `storedValues` versioned) and the entries carry the acting user, which
 * the host application wires through its actor provider (the test kernel
 * uses TestActorProvider). Needs the PostgreSQL database from
 * `DATABASE_URL`.
 */
#[Group('database')]
final class AuditLogTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private const string SECTION = 'retention';

    protected function setUp(): void
    {
        ApplicationSettingResourceTest::cleanUp();
    }

    protected function tearDown(): void
    {
        ApplicationSettingResourceTest::cleanUp();
        parent::tearDown();
    }

    public function testFirstWriteIsLoggedAsCreateByTheActingUser(): void
    {
        ApplicationSettingResourceTest::patch(static::createClient(), self::SECTION, ['retention_days' => 45]);
        self::assertResponseStatusCodeSame(200);

        $entries = $this->entries();
        self::assertCount(1, $entries);
        self::assertSame(ApplicationSetting::class, $entries[0]->getObjectClass());
        self::assertSame(ApplicationSettingResourceTest::sectionId(self::SECTION)->toRfc4122(), $entries[0]->getObjectId());
        self::assertSame('test-admin', $entries[0]->getUsername());
        self::assertSame('create', $entries[0]->getAction());
        self::assertSame(1, $entries[0]->getVersion());
        self::assertSame(['storedValues' => ['retention_days' => 45]], $entries[0]->getData());
    }

    public function testSecondWriteIsLoggedAsUpdate(): void
    {
        $client = static::createClient();
        ApplicationSettingResourceTest::patch($client, self::SECTION, ['retention_days' => 45]);
        self::assertResponseStatusCodeSame(200);
        ApplicationSettingResourceTest::patch($client, self::SECTION, ['max_items' => 12]);
        self::assertResponseStatusCodeSame(200);

        $entries = $this->entries();
        self::assertCount(2, $entries);
        self::assertSame(['create', 'update'], array_map(static fn (LogEntry $entry): string => $entry->getAction(), $entries));
        self::assertSame(['test-admin', 'test-admin'], array_map(static fn (LogEntry $entry): ?string => $entry->getUsername(), $entries));

        $latest = $entries[1]->getData();
        self::assertIsArray($latest);
        self::assertArrayHasKey('storedValues', $latest);
        self::assertSame(12, $latest['storedValues']['max_items']);
        self::assertSame(45, $latest['storedValues']['retention_days']);
    }

    public function testRejectedWriteIsNotLogged(): void
    {
        ApplicationSettingResourceTest::patch(static::createClient(), self::SECTION, ['retention_days' => 0]);
        self::assertResponseStatusCodeSame(422);

        self::assertSame([], $this->entries());
    }

    /**
     * Log entries of the section, oldest first.
     *
     * @return list<LogEntry>
     */
    private function entries(): array
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->clear();

        return array_values($entityManager->getRepository(LogEntry::class)->findBy(
            [
                'objectClass' => ApplicationSetting::class,
                'objectId' => ApplicationSettingResourceTest::sectionId(self::SECTION)->toRfc4122(),
            ],
            ['version' => 'ASC'],
        ));
    }
}
