<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Fixtures;

use Dmstr\AppSettings\Contract\SettingsContributorInterface;

/**
 * Test section with plain scalar settings (string, boolean, email).
 */
final class GeneralSection implements SettingsContributorInterface
{
    public const string KEY = 'general';

    public const string DEFAULT_NAME = 'Example application';

    public function getSectionKey(): string
    {
        return self::KEY;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'title' => 'General',
            'description' => 'Global settings of the installation.',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'title' => 'Name',
                    'description' => 'Display name of the installation',
                    'default' => self::DEFAULT_NAME,
                ],
                'maintenance_mode' => [
                    'type' => 'boolean',
                    'title' => 'Maintenance mode',
                    'description' => 'Marks the installation as being under maintenance',
                    'default' => false,
                ],
                'contact_email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'title' => 'Contact email',
                    'description' => 'Contact address shown to users; empty for none',
                    'default' => '',
                ],
            ],
        ];
    }
}
