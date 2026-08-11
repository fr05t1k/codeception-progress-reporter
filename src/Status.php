<?php

declare(strict_types=1);

namespace Codeception\ProgressReporter;

/**
 * Mutable counter that tracks the outcome of tests within a suite run.
 */
final class Status
{
    private int $fails = 0;

    private int $success = 0;

    private int $errors = 0;

    private int $skipped = 0;

    private int $incomplete = 0;

    public function getFails(): int
    {
        return $this->fails;
    }

    public function getSuccess(): int
    {
        return $this->success;
    }

    public function getErrors(): int
    {
        return $this->errors;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function getIncomplete(): int
    {
        return $this->incomplete;
    }

    public function incSuccess(): void
    {
        $this->success++;
    }

    public function incErrors(): void
    {
        $this->errors++;
    }

    public function incFails(): void
    {
        $this->fails++;
    }

    public function incSkipped(): void
    {
        $this->skipped++;
    }

    public function incIncomplete(): void
    {
        $this->incomplete++;
    }

    /**
     * Whether any test has errored or failed so far.
     */
    public function hasFailures(): bool
    {
        return $this->fails > 0 || $this->errors > 0;
    }
}
