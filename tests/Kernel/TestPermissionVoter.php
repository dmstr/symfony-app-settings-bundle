<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Kernel;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Example permission voter, as a host application provides it.
 *
 * It supports exactly the permissions registered at the `za7_core.permission`
 * tag (parameter `za7_core.permissions.registered`) and abstains on every
 * other attribute. A supported permission is granted when one of the user's
 * roles is mapped to it in `za7_core.permissions` (permission => roles) and
 * denied otherwise. The subject is ignored.
 */
final class TestPermissionVoter implements VoterInterface
{
    /**
     * @param array<string, list<string>> $map        permission => roles
     * @param list<string>                $registered every registered permission
     */
    public function __construct(
        private readonly array $map,
        private readonly array $registered,
    ) {
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        $result = self::ACCESS_ABSTAIN;
        $roles = $token->getRoleNames();

        foreach ($attributes as $attribute) {
            if (!\is_string($attribute) || !\in_array($attribute, $this->registered, true)) {
                continue;
            }
            if ([] !== array_intersect($roles, $this->map[$attribute] ?? [])) {
                return self::ACCESS_GRANTED;
            }
            $result = self::ACCESS_DENIED;
        }

        return $result;
    }
}
