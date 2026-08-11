<?php

declare(strict_types=1);

namespace Fixture\Unit;

use Codeception\Test\Unit;

final class SampleTest extends Unit
{
    public function testPass(): void
    {
        $this->assertTrue(true);
    }

    public function testFail(): void
    {
        $this->assertSame(1, 2, 'intentional failure');
    }

    public function testError(): void
    {
        throw new \RuntimeException('intentional error');
    }

    public function testSkipped(): void
    {
        $this->markTestSkipped('intentional skip');
    }

    public function testIncomplete(): void
    {
        $this->markTestIncomplete('intentional incomplete');
    }
}
