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
}
