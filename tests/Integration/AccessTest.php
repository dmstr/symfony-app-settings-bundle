<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dmstr\AppSettings\Tests\Kernel\TestHeaderAuthenticator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Every operation of the settings resource checks the permission
 * `appsettings:setting:manage`: anonymous requests get 401, users whose
 * roles are not mapped to the permission 403 (the test voter maps it to
 * ROLE_ADMIN). Uses the headers of the test authenticator. Needs the
 * PostgreSQL database from `DATABASE_URL`.
 */
#[Group('database')]
final class AccessTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function setUp(): void
    {
        ApplicationSettingResourceTest::cleanUp();
    }

    protected function tearDown(): void
    {
        ApplicationSettingResourceTest::cleanUp();
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
    public function testAnonymousIsUnauthorized(string $method, string $uri): void
    {
        $this->send($method, $uri, [TestHeaderAuthenticator::HEADER_AUTH => TestHeaderAuthenticator::ANONYMOUS]);

        self::assertResponseStatusCodeSame(401);
        self::assertSame(0, ApplicationSettingResourceTest::rowCount());
    }

    #[DataProvider('operations')]
    public function testUserWithoutAdminRoleIsForbidden(string $method, string $uri): void
    {
        $this->send($method, $uri, [TestHeaderAuthenticator::HEADER_ROLES => 'ROLE_USER']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, ApplicationSettingResourceTest::rowCount());
    }

    public function testAdminCanList(): void
    {
        $this->send('GET', ApplicationSettingResourceTest::COLLECTION);

        self::assertResponseStatusCodeSame(200);
    }

    /**
     * @param array<string, string> $headers
     */
    private function send(string $method, string $uri, array $headers = []): void
    {
        $options = ['headers' => ['Accept' => 'application/ld+json'] + $headers];
        if ('PATCH' === $method) {
            $options['headers']['Content-Type'] = 'application/merge-patch+json';
            $options['json'] = ['values' => ['name' => 'changed']];
        }

        static::createClient()->request($method, $uri, $options);
    }
}
