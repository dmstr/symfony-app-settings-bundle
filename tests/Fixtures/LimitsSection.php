<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Fixtures;

use Dmstr\AppSettings\Contract\SettingsContributorInterface;

/**
 * Test section with nullable integer settings whose default is null
 * ("unlimited").
 */
final class LimitsSection implements SettingsContributorInterface
{
    public const string KEY = 'limits';

    public function getSectionKey(): string
    {
        return self::KEY;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'title' => 'Limits',
            'description' => 'Optional upper bounds; empty means unlimited.',
            'properties' => [
                'max_requests' => [
                    'type' => ['integer', 'null'],
                    'title' => 'Maximum requests',
                    'minimum' => 1,
                    'default' => null,
                ],
                'max_rounds' => [
                    'type' => ['integer', 'null'],
                    'title' => 'Maximum rounds',
                    'minimum' => 1,
                    'default' => null,
                ],
            ],
        ];
    }
}
