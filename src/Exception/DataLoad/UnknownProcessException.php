<?php

namespace Aziraphale\LVM\Exception\DataLoad;

use Exception;

/**
 * Class UnknownProcessException
 *
 * @package Aziraphale\LVM\Exception\DataLoad
 */
class UnknownProcessException extends DataLoadException
{
    /**
     * UnknownProcessException constructor.
     *
     * @param Exception $previous
     */
    public function __construct(Exception $previous)
    {
        $message = "An unexpected error occurred while fetching the LVM data: ".
                   $previous->getMessage();

        parent::__construct($message, 0, $previous);
    }
}
