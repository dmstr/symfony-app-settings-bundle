<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:45:00 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Class-level constraint for {@see \Dmstr\AppSettings\Entity\ApplicationSetting}:
 * the stored values must match the JSON schema of the section. Every schema
 * error becomes one violation at `values/<json pointer>`.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class MatchesSectionSchema extends Constraint
{
    public const SCHEMA_MISMATCH_ERROR = '6f0f2d7e-3c41-4b8e-9d0a-5a1e7c2b9f01';
    public const UNKNOWN_SECTION_ERROR = '6f0f2d7e-3c41-4b8e-9d0a-5a1e7c2b9f02';

    protected const ERROR_NAMES = [
        self::SCHEMA_MISMATCH_ERROR => 'SCHEMA_MISMATCH_ERROR',
        self::UNKNOWN_SECTION_ERROR => 'UNKNOWN_SECTION_ERROR',
    ];

    public string $message = 'The value at "{{ path }}" is invalid: {{ error }}';

    public string $unknownSectionMessage = 'The settings section {{ section }} is not registered.';

    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        ?string $message = null,
        ?string $unknownSectionMessage = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->unknownSectionMessage = $unknownSectionMessage ?? $this->unknownSectionMessage;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
