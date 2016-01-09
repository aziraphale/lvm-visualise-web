<?php

namespace Aziraphale\LVM\Exception\DataLoad;

use Exception;

/**
 * Class DataLoadException
 *
 * @package Aziraphale\LVM\Exception\DataLoad
 */
class DataLoadException extends Exception
{
    /**
     * DataLoadException constructor.
     *
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct($message = null, $code = null, Exception $previous = null)
    {
        if ($message === null) {
            $message = "An unexpected error occurred while loading the LVM".
                       " data: " . $previous->getMessage();
        }

        parent::__construct($message, 0, $previous);
    }
}
