<?php
namespace Aziraphale\LVM;

/**
 * A "normal" segment - i.e. one that isn't just free space on a PV
 *
 * @package Aziraphale\LVM
 */
class StandardSegment extends Segment
{
    /**
     * segtype / Type
     *
     * e.g. linear, mirror
     *
     * @var string
     */
    public $segmentType;

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
     * @param string $segmentType
     */
    public function __construct(LVM $lvm, PV $physicalVolume, LV $logicalVolume, $physicalStart, $physicalExtentCount, $logicalExtentCount, $logicalStart, $segmentType)
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

        $this->segmentType = $segmentType;

        parent::__construct($lvm, $physicalVolume, $logicalVolume, $physicalStart, $physicalExtentCount, $logicalExtentCount, $logicalStart);
    }
}
