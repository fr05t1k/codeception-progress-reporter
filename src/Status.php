<?php

namespace Codeception\ProgressReporter;

/**
 * Class Status
 */
class Status
{
    /**
     * Fails count
     *
     * @var int
     */
    private $fails = 0;

    /**
     * Success count
     *
     * @var int
     */
    private $success = 0;

    /**
     * Errors count
     *
     * @var int
     */
    private $errors = 0;

    /**
     * Get fails count
     *
     * @return int
     */
    public function getFails()
    {
        return $this->fails;
    }

    /**
     * Get success count
     *
     * @return int
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Get errors count
     *
     * @return int
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Increment success
     */
    public function incSuccess()
    {
        $this->success++;
    }

    /**
     * Increment errors
     */
    public function incErrors()
    {
        $this->errors++;
    }

    /**
     * Increment fails
     */
    public function incFails()
    {
        $this->fails++;
    }
}
