<?php

namespace Codeception\ProgressReporter;

use Codeception\Event\FailEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Subscriber\Console;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class ProgressReporter
 */
class ProgressReporter extends Extension
{
    /**
     * We are listening for events
     *
     * @var array
     */
    public static $events = [
        Events::SUITE_BEFORE => 'beforeSuite',
        Events::TEST_BEFORE => 'beforeTest',
        Events::TEST_AFTER => 'afterTest',
        Events::TEST_FAIL_PRINT => 'printFailed',
        Events::TEST_SUCCESS => 'success',
        Events::TEST_ERROR => 'error',
        Events::TEST_FAIL => 'fail',
    ];

    /**
     * Standard reporter for printing fails
     *
     * @var Console
     */
    public $standardReporter;

    /**
     * Progress bar
     *
     * @var ProgressBar
     */
    protected $progress;

    /**
     * Status (counter)
     *
     * @var Status
     */
    protected $status;

    /**
     * Setup
     */
    public function _initialize()
    {
        $this->options['silent'] = false; // turn on printing for this extension
        $this->_reconfigure(['settings' => ['silent' => true]]); // turn off printing for everything else
        $this->standardReporter = new Console($this->options);
        ProgressBar::setFormatDefinition(
            'custom',
            "Current test: <options=bold>%file%</>\n".
            "<fg=green>Success: %success%</> <fg=yellow>Errors: %errors%</> <fg=red>Fails: %fails%</>\n" .
            "<fg=cyan>[%bar%]</>\n%current%/%max% %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%"
        );
        $this->status = new Status();
    }

    /**
     * Setup progress bar
     *
     * @param SuiteEvent $event
     */
    public function beforeSuite(SuiteEvent $event)
    {
        $count = $event->getSuite()->count(true);
        $this->progress = new ProgressBar($this->output, $count);
        $this->progress->setFormat('custom');
        $this->progress->setBarWidth($count);
        $this->progress->setRedrawFrequency($count / 100);
        $this->progress->start();
    }

    /**
     * After test
     */
    public function afterTest()
    {
        $this->progress->advance();
    }

    /**
     * Before test
     *
     * @param TestEvent $event
     */
    public function beforeTest(TestEvent $event)
    {
        $message = $event->getTest()->getMetadata()->getFilename();
        $this->progress->setMessage($message, 'file');
        $this->progress->setMessage($this->status->getSuccess(), 'success');
        $this->progress->setMessage($this->status->getFails(), 'fails');
        $this->progress->setMessage($this->status->getErrors(), 'errors');
    }


    /**
     * Print failed tests
     *
     * @param FailEvent $event
     */
    public function printFailed(FailEvent $event)
    {
        $this->standardReporter->printFail($event);
    }

    /**
     * Success event
     */
    public function success()
    {
        $this->status->incSuccess();
    }

    /**
     * Error event
     */
    public function error()
    {
        $this->status->incErrors();
    }

    /**
     * Fail event
     */
    public function fail()
    {
        $this->status->incFails();
    }
}
