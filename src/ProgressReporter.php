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
    public static $events = [];

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
        if ($this->options['steps'] || $this->options['debug']) {
            // Don't show progress bar when --steps or --debug option is provided
            $this->unsubscribeFromEvents();
            return;
        }

        $this->subscribeToEvents();
        $format = '';
        if (!$this->options['silent']) {
            $format = "\nCurrent suite: <options=bold>%suite%</>\n" .
                "Current test: <options=bold>%file%</>\n" .
                "<fg=green>Success: %success%</> <fg=yellow>Errors: %errors%</> <fg=red>Fails: %fails%</>\n" .
                "<fg=cyan>[%bar%]</>\n%current%/%max% %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%\n";
        }

        $this->_reconfigure(['settings' => ['silent' => true]]); // turn off printing for everything else
        $this->standardReporter = new Console($this->options);
        ProgressBar::setFormatDefinition('custom', $format);
        $this->status = new Status();
    }

    /**
     * Subscribe to all events
     */
    private function subscribeToEvents()
    {
        self::$events = [
            Events::SUITE_BEFORE => 'beforeSuite',
            Events::SUITE_AFTER => 'afterSuite',
            Events::TEST_BEFORE => 'beforeTest',
            Events::TEST_AFTER => 'afterTest',
            Events::TEST_FAIL_PRINT => 'printFailed',
            Events::TEST_SUCCESS => 'success',
            Events::TEST_ERROR => 'error',
            Events::TEST_FAIL => 'fail',
        ];
    }

    /**
     * Unsubscribe from all events
     */
    private function unsubscribeFromEvents()
    {
        self::$events = [];
    }

    /**
     * Setup progress bar
     *
     * @param SuiteEvent $event
     */
    public function beforeSuite(SuiteEvent $event)
    {
        $this->status = new Status();

        $count = $event->getSuite()->count(true);
        $this->progress = new ProgressBar($this->output, $count);
        $this->progress->setFormat('custom');
        $this->progress->setBarWidth($count);
        $this->progress->setRedrawFrequency($count / 100);

        $this->progress->setMessage('none', 'file');
        $this->progress->setMessage($event->getSuite()->getBaseName(), 'suite');
        $this->progress->setMessage($this->status->getSuccess(), 'success');
        $this->progress->setMessage($this->status->getFails(), 'fails');
        $this->progress->setMessage($this->status->getErrors(), 'errors');

        $this->progress->start();
    }

    /**
     * After suite
     */
    public function afterSuite()
    {
        $this->progress->display();
    }

    /**
     * After test
     */
    public function afterTest()
    {
        $this->progress->advance();
        $this->progress->setMessage($this->status->getSuccess(), 'success');
        $this->progress->setMessage($this->status->getFails(), 'fails');
        $this->progress->setMessage($this->status->getErrors(), 'errors');
    }

    /**
     * Before test
     *
     * @param TestEvent $event
     */
    public function beforeTest(TestEvent $event)
    {
        $message = $event->getTest()->getMetadata()->getFilename();
        $this->progress->setMessage(pathinfo($message, PATHINFO_FILENAME), 'file');
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
