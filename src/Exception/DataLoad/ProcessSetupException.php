<?php

namespace Aziraphale\LVM\Exception\DataLoad;

use Exception;

/**
 * Class ProcessSetupException
 *
 * @package Aziraphale\LVM\Exception\DataLoad
 */
class ProcessSetupException extends DataLoadException
{
    /**
     * ProcessSetupException constructor.
     *
     * @param Exception $previous
     */
    public function __construct(Exception $previous)
    {
        $message = "An unexpected error occurred while preparing the LVM ".
                   "data-loading processes for execution: " .
                   $previous->getMessage();

        parent::__construct($message, 0, $previous);
    }
}
