<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Integration;

use Dmstr\AppSettings\Tests\Kernel\TestDatabase;
use Dmstr\AppSettings\Tests\Kernel\TestKernel;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The schema the bundle's migration creates. Existing databases store the
 * migration's class name, so it must not change. Needs the PostgreSQL
 * database from `DATABASE_URL`.
 */
#[Group('database')]
final class MigrationTest extends KernelTestCase
{
    private const string TABLE = 'dmstr_app_setting';
    private const string MIGRATION = 'Dmstr\\AppSettings\\Migrations\\Version20260930163000';

    private Connection $connection;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        TestDatabase::migrate(self::bootKernel());
        $this->connection = self::getContainer()->get('doctrine.dbal.default_connection');
    }

    public function testBundleMigrationIsExecuted(): void
    {
        $executed = $this->connection->fetchFirstColumn('SELECT version FROM doctrine_migration_versions');

        self::assertContains(self::MIGRATION, $executed);
    }

    public function testTableHasExpectedColumns(): void
    {
        $columns = $this->connection->fetchAllKeyValue(
            "SELECT column_name, data_type FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = ?",
            [self::TABLE],
        );

        self::assertSame([
            'created_at' => 'timestamp with time zone',
            'id' => 'uuid',
            'section_key' => 'character varying',
            'updated_at' => 'timestamp with time zone',
            'values_json' => 'jsonb',
        ], self::sorted($columns));
    }

    public function testSectionKeyIsUnique(): void
    {
        $definition = $this->connection->fetchOne(
            "SELECT indexdef FROM pg_indexes
             WHERE schemaname = current_schema() AND tablename = ? AND indexname = 'uniq_dmstr_app_setting_section_key'",
            [self::TABLE],
        );

        self::assertIsString($definition);
        self::assertStringContainsString('UNIQUE INDEX', $definition);
        self::assertStringContainsString('(section_key)', $definition);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function sorted(array $values): array
    {
        ksort($values);

        return $values;
    }
}
