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
    // we are listening for events
    /**
     * @var array
     */
    public static $events = [
        Events::SUITE_BEFORE => 'beforeSuite',
        Events::TEST_BEFORE => 'beforeTest',
        Events::TEST_AFTER => 'afterTest',
        Events::TEST_FAIL_PRINT => 'printFailed'
    ];

    /**
     * @var Console
     */
    public $standardReporter;

    /**
     * @var ProgressBar
     */
    protected $progress;

    /**
     *
     */
    public function _initialize()
    {
        $this->options['silent'] = false; // turn on printing for this extension
        $this->_reconfigure(['settings' => ['silent' => true]]); // turn off printing for everything else
        $this->standardReporter = new Console($this->options);
        ProgressBar::setFormatDefinition(
            'custom',
            "%message%\n<info>[%bar%]</info>\n%current%/%max% %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%"
        );
    }

    /**
     * @param SuiteEvent $event
     */
    public function beforeSuite(SuiteEvent $event)
    {
        $count = $event->getSuite()->count(true);
        $this->progress = new ProgressBar($this->output, $count);
        $this->progress->setFormat('custom');
        $this->progress->setBarWidth($count);
        $this->progress->setRedrawFrequency(5);
        $this->progress->start();
    }

    /**
     *
     */
    public function afterTest()
    {
        $this->progress->advance();
    }

    /**
     *
     * @param TestEvent $event
     */
    public function beforeTest(TestEvent $event)
    {
        $message = $event->getTest()->getMetadata()->getFilename();
        $this->progress->setMessage($message);
    }


    public function printFailed(FailEvent $event)
    {
        $this->standardReporter->printFail($event);
    }
}
