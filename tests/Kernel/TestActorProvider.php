<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Kernel;

use Gedmo\Tool\ActorProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Actor of audit-log entries: the identifier of the authenticated user, null
 * without one. A host application wires the same kind of provider into the
 * Gedmo loggable listener ({@see TestKernel::process()}).
 */
final class TestActorProvider implements ActorProviderInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * @return string|null
     */
    public function getActor(): ?string
    {
        return $this->tokenStorage->getToken()?->getUser()?->getUserIdentifier();
    }
}
