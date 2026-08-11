<?php

declare(strict_types=1);

namespace Codeception\ProgressReporter\Tests\Unit;

use Codeception\Test\Unit;

/**
 * End-to-end test: runs Codeception against a fixture suite (with passing,
 * failing, erroring, skipped and incomplete tests) through the ProgressReporter
 * extension and asserts the produced console output.
 */
final class ReporterOutputTest extends Unit
{
    private const FIXTURE = __DIR__ . '/../_data/reporter-fixture';

    /** Output with ANSI escape sequences preserved. */
    private static string $raw;

    /** Output with ANSI escape sequences stripped. */
    private static string $plain;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 2);
        $codecept = $root . '/vendor/bin/codecept';
        $php = escapeshellarg(PHP_BINARY);
        $config = escapeshellarg(self::FIXTURE);

        $prefix = $php . ' -d register_argc_argv=On -d memory_limit=512M ' . escapeshellarg($codecept);

        // Make sure the fixture actor classes exist, then run it with colors
        // forced on so the progress-bar coloring can be asserted too.
        exec($prefix . ' build -c ' . $config . ' 2>&1');
        exec($prefix . ' run -c ' . $config . ' --colors 2>&1', $lines);

        self::$raw = implode("\n", $lines);
        self::$plain = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', self::$raw) ?? '';
    }

    public function testShowsProgressBarCounters(): void
    {
        $this->assertStringContainsString('Success: 1', self::$plain);
        $this->assertStringContainsString('Skipped: 1', self::$plain);
        $this->assertStringContainsString('Incomplete: 1', self::$plain);
        $this->assertStringContainsString('Errors: 1', self::$plain);
        $this->assertStringContainsString('Fails: 1', self::$plain);
    }

    public function testShowsCurrentSuiteName(): void
    {
        $this->assertStringContainsString('Current suite: Fixture.unit', self::$plain);
    }

    public function testPrintsFailureDetails(): void
    {
        $this->assertStringContainsString('intentional failure', self::$plain);
        $this->assertStringContainsString('Failed asserting that 2 is identical to 1', self::$plain);
    }

    public function testPrintsErrorDetails(): void
    {
        $this->assertStringContainsString('intentional error', self::$plain);
        $this->assertStringContainsString('RuntimeException', self::$plain);
    }

    public function testPrintsResultFooter(): void
    {
        $this->assertStringContainsString('Tests: 5', self::$plain);
        $this->assertStringContainsString('Errors: 1, Failures: 1', self::$plain);
    }

    public function testProgressBarTurnsRedOnFailure(): void
    {
        // Cyan bar while passing, red bar once a test has failed/errored.
        $this->assertStringContainsString("\x1b[36m[", self::$raw, 'expected a cyan progress bar');
        $this->assertStringContainsString("\x1b[31m[", self::$raw, 'expected a red progress bar after failures');
    }
}
