<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Security;

/**
 * The permission this bundle checks. It is registered at the container tag
 * "za7_core.permission" (config/services.yaml), the permission tag the host
 * application's voter reads, and decided by that voter; which roles hold it
 * is the application's map (`za7_core.permissions`), not the bundle's
 * business.
 */
final class AppSettingsPermissions
{
    /** Listing, reading and patching the application settings. */
    public const string SETTING_MANAGE = 'appsettings:setting:manage';
}
