<?php
namespace Aziraphale\LVM;
use Aziraphale\LVM\Utility\PVAttributes;
use Aziraphale\LVM\Utility\Size;

/**
 * A single Physical Volume in this LVM layout
 *
 * @package Aziraphale\LVM
 */
class PV
{
    /**
     * @var LVM
     */
    public $lvm;

    /**
     * @var VG
     */
    public $vg;

    /**
     * pv_uuid / PV UUID
     *
     * @var string
     */
    public $uuid;

    /**
     * pv_name / PV
     *
     * @var string
     */
    public $name;

    /**
     * dev_size / DevSize
     *
     * @var Size
     */
    public $deviceSize;

    /**
     * pv_fmt / Fmt
     *
     * @var string
     */
    public $format;

    /**
     * pv_attr / Attr
     *
     * @var PVAttributes
     */
    public $attributes;

    /**
     * pv_size / PSize
     *
     * @var Size
     */
    public $size;

    /**
     * pv_free / PFree
     *
     * @var Size
     */
    public $free;

    /**
     * pv_pe_count / PE
     *
     * @var int
     */
    public $physicalExtentCount;

    /**
     * @var Segment[]
     */
    public $physicalExtents = [];

    /**
     * PV constructor.
     *
     * @param LVM $lvm
     * @param VG $vg
     * @param string $uuid
     * @param string $name
     * @param Size $deviceSize
     * @param string $format
     * @param PVAttributes $attributes
     * @param Size $size
     * @param Size $free
     * @param int $physicalExtentCount
     */
    public function __construct(LVM $lvm, VG $vg, $uuid, $name, Size $deviceSize, $format, PVAttributes $attributes, Size $size, Size $free, $physicalExtentCount)
    {
        $this->lvm = $lvm;
        $this->vg = $vg;
        $this->uuid = $uuid;
        $this->name = $name;
        $this->deviceSize = $deviceSize;
        $this->format = $format;
        $this->attributes = $attributes;
        $this->size = $size;
        $this->free = $free;
        $this->physicalExtentCount = $physicalExtentCount;

        $this->lvm->physicalVolumesByUUID[$uuid] = $this;
        $this->lvm->physicalVolumesByName[$name] = $this;

        $vg->physicalVolumesByUUID[$uuid] = $this;
        $vg->physicalVolumesByName[$name] = $this;
    }
}
