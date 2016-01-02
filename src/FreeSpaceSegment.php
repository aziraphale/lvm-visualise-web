<?php
namespace Aziraphale\LVM;

/**
 * A segment that simply indicates the free space on a physical volume
 *
 * @package Aziraphale\LVM
 */
class FreeSpaceSegment extends Segment
{
    /**
     * @var string
     */
    public $segmentType = 'free';
}
