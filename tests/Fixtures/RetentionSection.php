<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Fixtures;

use Dmstr\AppSettings\Contract\SettingsContributorInterface;

/**
 * Test section with bounded integers, a nullable string and a list.
 */
final class RetentionSection implements SettingsContributorInterface
{
    public const string KEY = 'retention';

    public function getSectionKey(): string
    {
        return self::KEY;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'title' => 'Retention',
            'description' => 'How long items are kept and how many are processed.',
            'properties' => [
                'tags' => [
                    'type' => 'array',
                    'title' => 'Tags',
                    'items' => ['type' => 'string', 'minLength' => 1],
                    'default' => [],
                ],
                'owner' => [
                    'type' => ['string', 'null'],
                    'title' => 'Owner',
                    'default' => null,
                ],
                'retention_days' => [
                    'type' => 'integer',
                    'title' => 'Retention (days)',
                    'minimum' => 1,
                    'default' => 30,
                ],
                'batch_size' => [
                    'type' => 'integer',
                    'title' => 'Batch size',
                    'minimum' => 0,
                    'default' => 20,
                ],
                'max_items' => [
                    'type' => 'integer',
                    'title' => 'Maximum items',
                    'minimum' => 1,
                    'default' => 10,
                ],
                'max_tokens' => [
                    'type' => 'integer',
                    'title' => 'Maximum tokens',
                    'minimum' => 1,
                    'default' => 100000,
                ],
                'deletion_delay_days' => [
                    'type' => 'integer',
                    'title' => 'Deletion delay (days)',
                    'minimum' => 0,
                    'default' => 7,
                ],
            ],
        ];
    }
}
