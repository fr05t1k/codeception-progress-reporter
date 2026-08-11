<?php

declare(strict_types=1);

namespace Codeception\ProgressReporter;

use Codeception\Event\PrintResultEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Subscriber\Console;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Terminal;

use function count;
use function max;
use function min;
use function pathinfo;

use const PATHINFO_FILENAME;

/**
 * Codeception extension that replaces the default reporter output with a
 * single terminal progress bar summarising the current suite run.
 */
class ProgressReporter extends Extension
{
    /**
     * Progress bar format shown while the run is still passing.
     */
    private const FORMAT_OK = 'codeception_progress';

    /**
     * Progress bar format shown once a test has failed or errored (red bar).
     */
    private const FORMAT_FAILED = 'codeception_progress_failed';

    /**
     * Events this extension subscribes to.
     *
     * @var array<string, string>
     */
    public static array $events = [];

    /**
     * Standard reporter reused for printing failures at the end of the run.
     */
    public ?Console $standardReporter = null;

    protected ?ProgressBar $progress = null;

    protected Status $status;

    public function _initialize(): void
    {
        if ($this->options['steps'] || $this->options['debug']) {
            // Don't show the progress bar when --steps or --debug is provided.
            $this->unsubscribeFromEvents();

            return;
        }

        $this->subscribeToEvents();

        $okFormat = '';
        $failedFormat = '';
        if (!$this->options['silent']) {
            $header = "Current suite: <options=bold>%suite%</>\n" .
                "Current test: <options=bold>%file%</>\n" .
                "<fg=green>Success: %success%</> " .
                "<fg=cyan>Skipped: %skipped%</> " .
                "<fg=magenta>Incomplete: %incomplete%</> " .
                "<fg=yellow>Errors: %errors%</> " .
                "<fg=red>Fails: %fails%</>\n";
            $footer = "\n%current%/%max% %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%\n";

            $okFormat = $header . "<fg=cyan>[%bar%]</>" . $footer;
            $failedFormat = $header . "<fg=red>[%bar%]</>" . $footer;
        }

        // Turn off default printing for everything else.
        $this->_reconfigure(['settings' => ['silent' => true]]);
        $this->standardReporter = new Console($this->options);
        ProgressBar::setFormatDefinition(self::FORMAT_OK, $okFormat);
        ProgressBar::setFormatDefinition(self::FORMAT_FAILED, $failedFormat);
        $this->status = new Status();
    }

    /**
     * Subscribe to all events.
     */
    private function subscribeToEvents(): void
    {
        self::$events = [
            Events::SUITE_BEFORE => 'beforeSuite',
            Events::SUITE_AFTER => 'afterSuite',
            Events::TEST_BEFORE => 'beforeTest',
            Events::TEST_AFTER => 'afterTest',
            Events::RESULT_PRINT_AFTER => 'printResult',
            Events::TEST_SUCCESS => 'success',
            Events::TEST_ERROR => 'error',
            Events::TEST_FAIL => 'fail',
            Events::TEST_SKIPPED => 'skipped',
            Events::TEST_INCOMPLETE => 'incomplete',
        ];
    }

    /**
     * Unsubscribe from all events.
     */
    private function unsubscribeFromEvents(): void
    {
        self::$events = [];
    }

    /**
     * Set up the progress bar for the suite.
     */
    public function beforeSuite(SuiteEvent $event): void
    {
        $this->status = new Status();

        $suite = $event->getSuite();
        $count = max(1, count($suite->getTests()));

        $this->progress = new ProgressBar($this->output, $count);
        $this->progress->setFormat(self::FORMAT_OK);
        $this->progress->setBarWidth($this->resolveBarWidth());
        $this->progress->setRedrawFrequency((int) max(1, $count / 100));

        $this->progress->setMessage('none', 'file');
        $this->progress->setMessage($suite->getBaseName(), 'suite');
        $this->updateCounters();

        $this->progress->start();
    }

    /**
     * Pick a progress-bar width that fits the terminal instead of scaling with
     * the number of tests (which overflowed wide on large suites and looked
     * stubby on small ones).
     */
    private function resolveBarWidth(): int
    {
        $terminalWidth = (new Terminal())->getWidth();

        return max(10, min(60, $terminalWidth - 15));
    }

    /**
     * Redraw the progress bar once the suite has finished.
     */
    public function afterSuite(): void
    {
        $this->progress?->display();
    }

    /**
     * Advance the progress bar after each test.
     */
    public function afterTest(): void
    {
        $this->progress?->advance();
        $this->updateCounters();
    }

    /**
     * Display the name of the test that is about to run.
     */
    public function beforeTest(TestEvent $event): void
    {
        $filename = $event->getTest()->getMetadata()->getFilename();
        $this->progress?->setMessage(pathinfo($filename, PATHINFO_FILENAME), 'file');
    }

    /**
     * Print the full error/failure report once the run has finished.
     *
     * In Codeception 5 the built-in Console reporter is what renders the
     * end-of-run defect list, but we silence it so only the progress bar is
     * shown during the run. We therefore delegate the final report to a
     * dedicated Console instance here.
     */
    public function printResult(PrintResultEvent $event): void
    {
        $this->standardReporter?->afterResult($event);
    }

    public function success(): void
    {
        $this->status->incSuccess();
    }

    public function error(): void
    {
        $this->status->incErrors();
        $this->markFailed();
    }

    public function fail(): void
    {
        $this->status->incFails();
        $this->markFailed();
    }

    public function skipped(): void
    {
        $this->status->incSkipped();
    }

    public function incomplete(): void
    {
        $this->status->incIncomplete();
    }

    /**
     * Switch the progress bar to its red format the first time a test fails or
     * errors, giving an at-a-glance signal that the run is no longer green.
     */
    private function markFailed(): void
    {
        $this->progress?->setFormat(self::FORMAT_FAILED);
    }

    /**
     * Push the current status counters into the progress bar messages.
     */
    private function updateCounters(): void
    {
        $this->progress?->setMessage((string) $this->status->getSuccess(), 'success');
        $this->progress?->setMessage((string) $this->status->getSkipped(), 'skipped');
        $this->progress?->setMessage((string) $this->status->getIncomplete(), 'incomplete');
        $this->progress?->setMessage((string) $this->status->getErrors(), 'errors');
        $this->progress?->setMessage((string) $this->status->getFails(), 'fails');
    }
}
