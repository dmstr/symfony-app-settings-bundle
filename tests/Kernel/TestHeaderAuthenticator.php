<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Kernel;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authenticates test requests from plain headers, no credentials involved.
 *
 * - Without any of the headers below, the request is authenticated as
 *   {@see self::DEFAULT_USER} with {@see self::DEFAULT_ROLES}.
 * - `X-Test-Roles: ROLE_A, ROLE_B` replaces the roles (comma-separated,
 *   trimmed, empty entries ignored).
 * - `X-Test-User: alice` sets the user identifier.
 * - `X-Test-Auth: anonymous` skips authentication, whatever else is sent.
 *
 * As entry point it answers 401 for anonymous requests that need a user.
 */
final class TestHeaderAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public const string HEADER_ROLES = 'X-Test-Roles';
    public const string HEADER_USER = 'X-Test-User';
    public const string HEADER_AUTH = 'X-Test-Auth';
    public const string ANONYMOUS = 'anonymous';

    public const string DEFAULT_USER = 'test-admin';
    public const array DEFAULT_ROLES = ['ROLE_ADMIN'];

    public function supports(Request $request): ?bool
    {
        return self::ANONYMOUS !== strtolower(trim((string) $request->headers->get(self::HEADER_AUTH, '')));
    }

    public function authenticate(Request $request): Passport
    {
        $identifier = trim((string) $request->headers->get(self::HEADER_USER, ''));
        if ('' === $identifier) {
            $identifier = self::DEFAULT_USER;
        }
        $roles = $request->headers->has(self::HEADER_ROLES)
            ? self::parseRoles((string) $request->headers->get(self::HEADER_ROLES, ''))
            : self::DEFAULT_ROLES;

        return new SelfValidatingPassport(new UserBadge(
            $identifier,
            static fn (string $identifier): InMemoryUser => new InMemoryUser($identifier, null, $roles),
        ));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessageKey()], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return list<string>
     */
    public static function parseRoles(string $header): array
    {
        return array_values(array_unique(array_filter(
            array_map('trim', explode(',', $header)),
            static fn (string $role): bool => '' !== $role,
        )));
    }
}
