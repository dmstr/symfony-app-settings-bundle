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
 * The bundle ships no voter. In a host application without a voter for its
 * permission (kernel variant {@see TestKernel::ENV_NO_VOTER}) every voter
 * abstains on `appsettings:setting:manage`, and Symfony's default access
 * decision strategy (affirmative, `allow_if_all_abstain: false`) denies it,
 * whatever roles the user has. Boots the kernel but touches no database;
 * the HTTP side is {@see NoVoterAccessTest}.
 */
final class NoVoterAuthorizationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testNoPermissionVoterIsRegistered(): void
    {
        self::bootKernel(['environment' => TestKernel::ENV_NO_VOTER]);

        self::assertFalse(static::getContainer()->has(TestPermissionVoter::class));
        // The tag is still collected: only the voter is missing
        self::assertContains(
            AppSettingsPermissions::SETTING_MANAGE,
            static::getContainer()->getParameter(TestKernel::PERMISSIONS_REGISTERED_PARAMETER),
        );
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function roles(): iterable
    {
        yield 'mapped role' => [['ROLE_ADMIN']];
        yield 'other role' => [['ROLE_USER']];
        yield 'no role' => [[]];
    }

    /**
     * @param list<string> $roles
     */
    #[DataProvider('roles')]
    public function testPermissionIsDeniedForEveryUser(array $roles): void
    {
        self::bootKernel(['environment' => TestKernel::ENV_NO_VOTER]);
        $container = static::getContainer();
        $container->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken(new InMemoryUser('alice', null, $roles), 'main', $roles),
        );

        self::assertFalse($container->get(AuthorizationCheckerInterface::class)->isGranted(AppSettingsPermissions::SETTING_MANAGE));
    }
}
