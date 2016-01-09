<?php

namespace Aziraphale\LVM\Exception\DataLoad;

use Symfony\Component\Process\Exception\ProcessFailedException as Symfony_ProcessFailedException;

/**
 * Class ProcessFailedException
 *
 * @package Aziraphale\LVM\Exception\DataLoad
 */
class ProcessFailedException extends DataLoadException
{
    /**
     * ProcessFailedException constructor.
     *
     * @param Symfony_ProcessFailedException $previous
     */
    public function __construct(Symfony_ProcessFailedException $previous)
    {
        $message = "LVM-data-fetching script exited with a non-zero exit ".
                   "status :( - " .
                   $previous->getMessage();

        parent::__construct($message, 0, $previous);
    }
}
