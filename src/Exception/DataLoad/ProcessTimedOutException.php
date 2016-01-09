<?php

namespace Aziraphale\LVM\Exception\DataLoad;

use Symfony\Component\Process\Exception\ProcessTimedOutException as Symfony_ProcessTimedOutException;

/**
 * Class ProcessTimedOutException
 *
 * @package Aziraphale\LVM\Exception\DataLoad
 */
class ProcessTimedOutException extends DataLoadException
{
    /**
     * ProcessTimedOutException constructor.
     *
     * @param Symfony_ProcessTimedOutException $previous
     */
    public function __construct(Symfony_ProcessTimedOutException $previous)
    {

        if ($previous->isIdleTimeout()) {
            $message = sprintf(
                "The data-loading process spent too much time (> %s seconds)".
                " not returning data and timed out.",
                $previous->getProcess()->getIdleTimeout()
            );
        } else {
            $message = sprintf(
                "The data-loading process took too long to finish loading ".
                "(> %s seconds) and timed out.",
                $previous->getProcess()->getTimeout()
            );
        }

        parent::__construct($message, 0, $previous);
    }
}
