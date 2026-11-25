<?php

// file generated with AI assistance: Claude Code - 2026-11-25 15:36:23 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Creates the schema of the test database by running all registered
 * migrations (`doctrine:migrations:migrate`), the bundle's own
 * `Dmstr\AppSettings\Migrations\Version20260930163000` included, once per
 * PHPUnit process. Already executed migrations are skipped, so a database
 * that is reused between runs stays valid.
 */
final class TestDatabase
{
    private static bool $migrated = false;

    public static function migrate(KernelInterface $kernel): void
    {
        if (self::$migrated) {
            return;
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);
        $output = new BufferedOutput();

        $exitCode = $application->run(new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
            '--allow-no-migration' => true,
        ]), $output);

        if (0 !== $exitCode) {
            throw new \RuntimeException('Running the migrations failed: '.$output->fetch());
        }

        self::$migrated = true;
    }
}
