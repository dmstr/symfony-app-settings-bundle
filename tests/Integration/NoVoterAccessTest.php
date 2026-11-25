<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dmstr\AppSettings\Tests\Kernel\TestHeaderAuthenticator;
use Dmstr\AppSettings\Tests\Kernel\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Without a voter for `appsettings:setting:manage` (kernel variant
 * {@see TestKernel::ENV_NO_VOTER}) every operation of the settings resource
 * answers 403 to an authenticated user, even one with the role the test
 * voter would map, and nothing is stored; anonymous requests still get 401.
 * Needs the PostgreSQL database from `DATABASE_URL`.
 */
#[Group('database')]
final class NoVoterAccessTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private const array KERNEL_OPTIONS = ['environment' => TestKernel::ENV_NO_VOTER];

    protected function setUp(): void
    {
        ApplicationSettingResourceTest::cleanUp(self::KERNEL_OPTIONS);
    }

    protected function tearDown(): void
    {
        ApplicationSettingResourceTest::cleanUp(self::KERNEL_OPTIONS);
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function operations(): iterable
    {
        yield 'list' => ['GET', ApplicationSettingResourceTest::COLLECTION];
        yield 'item' => ['GET', ApplicationSettingResourceTest::iri('general')];
        yield 'patch' => ['PATCH', ApplicationSettingResourceTest::iri('general')];
    }

    #[DataProvider('operations')]
    public function testMappedRoleIsForbidden(string $method, string $uri): void
    {
        $this->send($method, $uri, [TestHeaderAuthenticator::HEADER_ROLES => 'ROLE_ADMIN, ROLE_USER']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, ApplicationSettingResourceTest::rowCount());
    }

    #[DataProvider('operations')]
    public function testAnonymousIsUnauthorized(string $method, string $uri): void
    {
        $this->send($method, $uri, [TestHeaderAuthenticator::HEADER_AUTH => TestHeaderAuthenticator::ANONYMOUS]);

        self::assertResponseStatusCodeSame(401);
        self::assertSame(0, ApplicationSettingResourceTest::rowCount());
    }

    /**
     * @param array<string, string> $headers
     */
    private function send(string $method, string $uri, array $headers): void
    {
        $options = ['headers' => ['Accept' => 'application/ld+json'] + $headers];
        if ('PATCH' === $method) {
            $options['headers']['Content-Type'] = 'application/merge-patch+json';
            $options['json'] = ['values' => ['name' => 'changed']];
        }

        static::createClient(self::KERNEL_OPTIONS)->request($method, $uri, $options);
    }
}
