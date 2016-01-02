<?php
namespace Aziraphale\LVM;

/**
 * Top-level container class to store references to all the volume groups,
 *  physical volumes, logical volumes and segments that comprise the LVM
 *  layout we're displaying
 *
 * @package Aziraphale\LVM
 */
class LVM
{
    /**
     * @var VG[]
     */
    public $volumeGroupsByUUID = [];

    /**
     * @var VG[]
     */
    public $volumeGroupsByName = [];

    /**
     * @var PV[]
     */
    public $physicalVolumesByUUID = [];

    /**
     * @var PV[]
     */
    public $physicalVolumesByName = [];

    /**
     * @var LV[]
     */
    public $logicalVolumesByUUID = [];

    /**
     * @var Segment[]
     */
    public $segments = [];

    public function __construct()
    {
        if (PHP_INT_MAX < 4294967296) {
            throw new \RuntimeException('This version/build of PHP does not support 64-bit integers and therefore cannot be used to run this software.');
        }
    }
}
