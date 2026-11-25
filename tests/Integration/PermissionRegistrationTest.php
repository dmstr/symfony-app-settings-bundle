<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Integration;

use Dmstr\AppSettings\Security\AppSettingsPermissions;
use Dmstr\AppSettings\Tests\Kernel\TestKernel;
use Dmstr\AppSettings\Tests\Kernel\TestPermissionVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * The permission contract: the bundle registers `appsettings:setting:manage`
 * at the `za7_core.permission` tag, the host application collects it into
 * `za7_core.permissions.registered` and its voter decides with the map
 * `za7_core.permissions`. Boots the kernel but touches no database.
 */
final class PermissionRegistrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testPermissionIsRegisteredAtTheTag(): void
    {
        self::bootKernel();

        self::assertContains(
            AppSettingsPermissions::SETTING_MANAGE,
            static::getContainer()->getParameter(TestKernel::PERMISSIONS_REGISTERED_PARAMETER),
        );
        self::assertSame(
            TestKernel::PERMISSIONS,
            static::getContainer()->getParameter(TestKernel::PERMISSION_MAP_PARAMETER),
        );
    }

    public function testTheTestVoterIsRegistered(): void
    {
        self::bootKernel();

        self::assertTrue(static::getContainer()->has(TestPermissionVoter::class));
    }

    /**
     * @return iterable<string, array{list<string>, bool}>
     */
    public static function decisions(): iterable
    {
        yield 'mapped role' => [['ROLE_ADMIN'], true];
        yield 'mapped role among others' => [['ROLE_USER', 'ROLE_ADMIN'], true];
        yield 'unmapped role' => [['ROLE_USER'], false];
        yield 'no role' => [[], false];
    }

    /**
     * @param list<string> $roles
     */
    #[DataProvider('decisions')]
    public function testDecision(array $roles, bool $expected): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $container->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken(new InMemoryUser('alice', null, $roles), 'main', $roles),
        );

        self::assertSame($expected, $container->get(AuthorizationCheckerInterface::class)->isGranted(AppSettingsPermissions::SETTING_MANAGE));
    }
}
