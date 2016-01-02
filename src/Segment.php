<?php
namespace Aziraphale\LVM;
use Aziraphale\LVM\Utility\Size;

/**
 * A single segment in this LVM layout. A segment has both physical and
 *  logical dimensions (i.e. a start position and a length). This class
 *  will always be extended in order to define more precisely what this
 *  segment is and does.
 *
 * @package Aziraphale\LVM
 */
abstract class Segment
{
    /**
     * @var LVM
     */
    public $lvm;

    /**
     * The PV that this segment is a part of
     *
     * @var PV
     */
    public $physicalVolume;

    /**
     * The LV that this segment is a part of
     *
     * @var LV
     */
    public $logicalVolume;

    /**
     * pvseg_start / Start
     *
     * @var int
     */
    public $physicalStart;

    /**
     * pvseg_size / SSize
     *
     * Note that $physicalExtentCount and $logicalExtentCount are likely to be the same
     *
     * @var int
     */
    public $physicalExtentCount;

    /**
     * The physical byte size of this segment
     *
     * @var Size
     */
    public $physicalSize;

    /**
     * seg_start / Start
     *
     * @var int
     */
    public $logicalStart;

    /**
     * seg_size / SSize
     *
     * Note that $physicalExtentCount and $logicalExtentCount are likely to be the same
     *
     * @var int
     */
    public $logicalExtentCount;

    /**
     * The logical byte size of this segment
     *
     * @var Size
     */
    public $logicalSize;

    /**
     * Segment constructor.
     *
     * @param LVM $lvm
     * @param PV $physicalVolume
     * @param LV $logicalVolume
     * @param int $physicalStart
     * @param int $physicalExtentCount
     * @param int $logicalExtentCount
     * @param int $logicalStart
     */
    public function __construct(LVM $lvm, PV $physicalVolume, LV $logicalVolume, $physicalStart, $physicalExtentCount, $logicalExtentCount, $logicalStart)
    {
        $this->lvm = $lvm;
        $this->lvm->segments[] = $this;

        $this->physicalVolume = $physicalVolume;
        $this->physicalVolume->physicalExtents[] = $this;

        $this->logicalVolume = $logicalVolume;
        $this->logicalVolume->segments[] = $this;

        $this->physicalStart = $physicalStart;
        $this->physicalExtentCount = $physicalExtentCount;
        $this->logicalExtentCount = $logicalExtentCount;
        $this->logicalStart = $logicalStart;
    }
}
