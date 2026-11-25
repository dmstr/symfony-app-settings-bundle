<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Dmstr\AppSettings\Entity\ApplicationSetting;
use Dmstr\AppSettings\Registry\SectionRegistry;
use Dmstr\AppSettings\Tests\Fixtures\GeneralSection;
use Dmstr\AppSettings\Tests\Kernel\TestDatabase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Functional test of the ApplicationSetting resource under
 * /api/admin/application_settings (the test firewall authenticates as admin
 * by default). Needs the PostgreSQL database from `DATABASE_URL`.
 *
 * The static helpers are shared with the other functional settings tests.
 */
#[Group('database')]
final class ApplicationSettingResourceTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public const string COLLECTION = '/api/admin/application_settings';

    protected function setUp(): void
    {
        self::cleanUp();
    }

    protected function tearDown(): void
    {
        self::cleanUp();
        parent::tearDown();
    }

    public function testListShowsEverySectionWithDefaults(): void
    {
        $response = static::createClient()->request('GET', self::COLLECTION, ['headers' => ['Accept' => 'application/ld+json']]);

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['hydra:member'];
        self::assertSame(['general', 'limits', 'retention'], array_column($members, 'sectionKey'));

        foreach ($members as $member) {
            self::assertSame(self::iri($member['sectionKey']), $member['@id']);
            self::assertSame($member['sectionKey'], $member['values']['section_key']);
            self::assertArrayNotHasKey('storedValues', $member);
        }

        $bySection = array_column($members, null, 'sectionKey');
        self::assertNull($bySection['limits']['values']['max_requests']);
        self::assertNull($bySection['limits']['values']['max_rounds']);
        self::assertSame(30, $bySection['retention']['values']['retention_days']);
        self::assertSame(20, $bySection['retention']['values']['batch_size']);
        self::assertSame(GeneralSection::DEFAULT_NAME, $bySection['general']['values']['name']);
        self::assertFalse($bySection['general']['values']['maintenance_mode']);
    }

    public function testUnsavedLimitsReturnNullDefaults(): void
    {
        $response = static::createClient()->request('GET', self::iri('limits'), ['headers' => ['Accept' => 'application/ld+json']]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame('limits', $data['sectionKey']);
        self::assertSame([
            'max_requests' => null,
            'max_rounds' => null,
            'section_key' => 'limits',
        ], self::sorted($data['values']));
        self::assertSame(0, self::rowCount());
    }

    public function testLimitsStorePositiveIntegers(): void
    {
        $data = self::patch(static::createClient(), 'limits', ['max_requests' => 4, 'max_rounds' => 6])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame(4, $data['values']['max_requests']);
        self::assertSame(6, $data['values']['max_rounds']);
        self::assertSame(['max_requests' => 4, 'max_rounds' => 6], self::sorted(self::storedValues('limits')));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function invalidLimits(): iterable
    {
        yield 'max_requests 0' => ['max_requests', 0];
        yield 'max_rounds 0' => ['max_rounds', 0];
        yield 'max_requests string' => ['max_requests', 'abc'];
    }

    #[DataProvider('invalidLimits')]
    public function testInvalidLimitIsRejectedAtThePropertyPath(string $key, mixed $value): void
    {
        $response = self::patch(static::createClient(), 'limits', [$key => $value]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('values/'.$key, self::violationPaths($response));
        self::assertSame(0, self::rowCount());
    }

    public function testUnsavedSectionReturnsDefaults(): void
    {
        $response = static::createClient()->request('GET', self::iri('retention'), ['headers' => ['Accept' => 'application/ld+json']]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame('retention', $data['sectionKey']);
        self::assertSame([
            'batch_size' => 20,
            'deletion_delay_days' => 7,
            'max_items' => 10,
            'max_tokens' => 100000,
            'owner' => null,
            'retention_days' => 30,
            'section_key' => 'retention',
            'tags' => [],
        ], self::sorted($data['values']));
        self::assertArrayNotHasKey('storedValues', $data);
        self::assertSame(0, self::rowCount());
    }

    public function testPatchStoresOnlyTheChangedValues(): void
    {
        $client = static::createClient();

        $data = self::patch($client, 'retention', ['retention_days' => 45])->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertSame(45, $data['values']['retention_days']);
        self::assertSame(20, $data['values']['batch_size']);
        self::assertSame(10, $data['values']['max_items']);
        self::assertSame(100000, $data['values']['max_tokens']);
        self::assertSame(7, $data['values']['deletion_delay_days']);
        self::assertNull($data['values']['owner']);
        self::assertSame(1, self::rowCount());
        self::assertSame(['retention_days' => 45], self::storedValues('retention'));

        $data = self::patch($client, 'retention', ['max_items' => 12])->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertSame(45, $data['values']['retention_days']);
        self::assertSame(12, $data['values']['max_items']);
        self::assertSame(1, self::rowCount());
        self::assertSame(['max_items' => 12, 'retention_days' => 45], self::sorted(self::storedValues('retention')));

        $read = $client->request('GET', self::iri('retention'), ['headers' => ['Accept' => 'application/ld+json']])->toArray();
        self::assertSame(45, $read['values']['retention_days']);
        self::assertSame(12, $read['values']['max_items']);
    }

    public function testNullRemovesTheStoredValue(): void
    {
        $client = static::createClient();
        self::patch($client, 'retention', ['retention_days' => 45, 'max_items' => 12]);
        self::assertResponseStatusCodeSame(200);

        $data = self::patch($client, 'retention', ['retention_days' => null])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame(30, $data['values']['retention_days']);
        self::assertSame(12, $data['values']['max_items']);
        self::assertSame(['max_items' => 12], self::storedValues('retention'));
    }

    public function testWrongTypeIsRejectedAtThePropertyPath(): void
    {
        $response = self::patch(static::createClient(), 'retention', ['retention_days' => 'abc']);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('values/retention_days', self::violationPaths($response));
        self::assertSame(0, self::rowCount());
    }

    public function testValueBelowMinimumIsRejectedAtThePropertyPath(): void
    {
        $response = self::patch(static::createClient(), 'retention', ['retention_days' => 0]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('values/retention_days', self::violationPaths($response));
    }

    public function testUnknownPropertyIsRejected(): void
    {
        $response = self::patch(static::createClient(), 'retention', ['foo' => 1]);

        self::assertResponseStatusCodeSame(422);
        $paths = self::violationPaths($response);
        self::assertNotEmpty($paths);
        foreach ($paths as $path) {
            self::assertStringStartsWith('values', $path);
        }
    }

    public function testInvalidPatchKeepsTheStoredValues(): void
    {
        $client = static::createClient();
        self::patch($client, 'retention', ['retention_days' => 45]);
        self::assertResponseStatusCodeSame(200);

        self::patch($client, 'retention', ['retention_days' => 0]);
        self::assertResponseStatusCodeSame(422);

        self::assertSame(['retention_days' => 45], self::storedValues('retention'));
    }

    public function testEmptyPatchOnUnsavedSectionCreatesTheRecord(): void
    {
        $response = static::createClient()->request('PATCH', self::iri('general'), [
            'headers' => ['Content-Type' => 'application/merge-patch+json', 'Accept' => 'application/ld+json'],
            'json' => ['values' => new \stdClass()],
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertSame(GeneralSection::DEFAULT_NAME, $response->toArray()['values']['name']);
        self::assertSame(1, self::rowCount());
        self::assertSame([], self::storedValues('general'));
    }

    public function testPostToTheCollectionIsNotAllowed(): void
    {
        static::createClient()->request('POST', self::COLLECTION, [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['values' => ['name' => 'x']],
        ]);

        self::assertResponseStatusCodeSame(405);
    }

    public function testPutIsNotAllowed(): void
    {
        static::createClient()->request('PUT', self::iri('general'), [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['values' => ['name' => 'x']],
        ]);

        self::assertResponseStatusCodeSame(405);
    }

    public function testDeleteIsNotAllowed(): void
    {
        static::createClient()->request('DELETE', self::iri('general'));

        self::assertResponseStatusCodeSame(405);
    }

    public function testUnknownIdIsNotFound(): void
    {
        static::createClient()->request('GET', self::COLLECTION.'/'.Uuid::v4()->toRfc4122(), ['headers' => ['Accept' => 'application/ld+json']]);

        self::assertResponseStatusCodeSame(404);
    }

    public static function sectionId(string $sectionKey): Uuid
    {
        return Uuid::v5(Uuid::fromString(SectionRegistry::NAMESPACE), $sectionKey);
    }

    public static function iri(string $sectionKey): string
    {
        return self::COLLECTION.'/'.self::sectionId($sectionKey)->toRfc4122();
    }

    /**
     * @param array<string, mixed>  $values
     * @param array<string, string> $headers
     */
    public static function patch(Client $client, string $sectionKey, array $values, array $headers = []): ResponseInterface
    {
        return $client->request('PATCH', self::iri($sectionKey), [
            'headers' => ['Content-Type' => 'application/merge-patch+json', 'Accept' => 'application/ld+json'] + $headers,
            'json' => ['values' => $values],
        ]);
    }

    public static function connection(): Connection
    {
        return self::getContainer()->get('doctrine.dbal.default_connection');
    }

    /**
     * Migrates the database (once per process) and removes all settings
     * rows and their audit-log entries.
     *
     * @param array<string, mixed> $kernelOptions
     */
    public static function cleanUp(array $kernelOptions = []): void
    {
        $kernel = static::bootKernel($kernelOptions);
        TestDatabase::migrate($kernel);
        $connection = self::connection();
        $connection->executeStatement('TRUNCATE dmstr_app_setting');
        $connection->executeStatement('DELETE FROM dmstr_log_entries WHERE object_class = :class', ['class' => ApplicationSetting::class]);
        static::ensureKernelShutdown();
    }

    public static function rowCount(): int
    {
        return (int) self::connection()->fetchOne('SELECT COUNT(*) FROM dmstr_app_setting');
    }

    /**
     * @return array<string, mixed>|null the decoded `values_json` column; null without a row
     */
    public static function storedValues(string $sectionKey): ?array
    {
        $json = self::connection()->fetchOne('SELECT values_json FROM dmstr_app_setting WHERE section_key = ?', [$sectionKey]);

        return false === $json ? null : json_decode((string) $json, true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    public static function violationPaths(ResponseInterface $response): array
    {
        return array_column($response->toArray(false)['violations'] ?? [], 'propertyPath');
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
