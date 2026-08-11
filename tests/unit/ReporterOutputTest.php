<?php

declare(strict_types=1);

namespace Codeception\ProgressReporter\Tests\Unit;

use Codeception\Test\Unit;

/**
 * End-to-end test: runs Codeception against a fixture suite (with one passing,
 * one failing and one erroring test) through the ProgressReporter extension and
 * asserts the produced console output.
 */
final class ReporterOutputTest extends Unit
{
    private const FIXTURE = __DIR__ . '/../_data/reporter-fixture';

    private static string $output;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 2);
        $codecept = $root . '/vendor/bin/codecept';
        $php = escapeshellarg(PHP_BINARY);
        $config = escapeshellarg(self::FIXTURE);

        $prefix = $php . ' -d register_argc_argv=On -d memory_limit=512M ' . escapeshellarg($codecept);

        // Make sure the fixture actor classes exist, then run it.
        exec($prefix . ' build -c ' . $config . ' 2>&1');
        exec($prefix . ' run -c ' . $config . ' --no-colors 2>&1', $lines);

        // Strip any residual ANSI escape sequences so assertions are stable.
        self::$output = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', implode("\n", $lines)) ?? '';
    }

    public function testShowsProgressBarCounters(): void
    {
        $this->assertStringContainsString('Success: 1', self::$output);
        $this->assertStringContainsString('Errors: 1', self::$output);
        $this->assertStringContainsString('Fails: 1', self::$output);
    }

    public function testShowsCurrentSuiteName(): void
    {
        $this->assertStringContainsString('Current suite: Fixture.unit', self::$output);
    }

    public function testPrintsFailureDetails(): void
    {
        $this->assertStringContainsString('intentional failure', self::$output);
        $this->assertStringContainsString('Failed asserting that 2 is identical to 1', self::$output);
    }

    public function testPrintsErrorDetails(): void
    {
        $this->assertStringContainsString('intentional error', self::$output);
        $this->assertStringContainsString('RuntimeException', self::$output);
    }

    public function testPrintsResultFooter(): void
    {
        $this->assertStringContainsString('Tests: 3, Assertions: 1, Errors: 1, Failures: 1', self::$output);
    }
}
