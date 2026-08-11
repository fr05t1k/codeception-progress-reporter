<?php

declare(strict_types=1);

namespace Codeception\ProgressReporter\Tests\Unit;

use Codeception\ProgressReporter\Status;
use Codeception\ProgressReporter\Tests\UnitTester;
use Codeception\Test\Unit;

final class StatusTest extends Unit
{
    protected UnitTester $tester;

    private Status $status;

    protected function _before(): void
    {
        $this->status = new Status();
    }

    public function testCountersStartAtZero(): void
    {
        $this->assertSame(0, $this->status->getSuccess());
        $this->assertSame(0, $this->status->getErrors());
        $this->assertSame(0, $this->status->getFails());
    }

    public function testIncSuccess(): void
    {
        $this->status->incSuccess();
        $this->status->incSuccess();

        $this->assertSame(2, $this->status->getSuccess());
        $this->assertSame(0, $this->status->getErrors());
        $this->assertSame(0, $this->status->getFails());
    }

    public function testIncErrors(): void
    {
        $this->status->incErrors();

        $this->assertSame(1, $this->status->getErrors());
        $this->assertSame(0, $this->status->getSuccess());
        $this->assertSame(0, $this->status->getFails());
    }

    public function testIncFails(): void
    {
        $this->status->incFails();
        $this->status->incFails();
        $this->status->incFails();

        $this->assertSame(3, $this->status->getFails());
        $this->assertSame(0, $this->status->getSuccess());
        $this->assertSame(0, $this->status->getErrors());
    }

    public function testCountersAreIndependent(): void
    {
        $this->status->incSuccess();
        $this->status->incErrors();
        $this->status->incFails();

        $this->assertSame(1, $this->status->getSuccess());
        $this->assertSame(1, $this->status->getErrors());
        $this->assertSame(1, $this->status->getFails());
    }
}
