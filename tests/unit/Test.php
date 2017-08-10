<?php
namespace Codeception\ProgressReporter\Tests;


use Codeception\ProgressReporter\ProgressReporter;

class Test extends \Codeception\Test\Unit
{
    /**
     * @var \Codeception\ProgressReporter\Tests\UnitTester
     */
    protected $tester;

    // tests
    public function testSomeFeature()
    {
        new ProgressReporter([], []);
    }
}
