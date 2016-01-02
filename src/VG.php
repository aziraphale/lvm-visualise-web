<?php
namespace Aziraphale\LVM;
use Aziraphale\LVM\Utility\Size;
use Aziraphale\LVM\Utility\VGAttributes;

/**
 * A single Volume Group in this LVM layout
 *
 * @package Aziraphale\LVM
 */
class VG
{
    /**
     * @var LVM
     */
    public $lvm;

    /**
     * vg_uuid / VG UUID
     *
     * @var string
     */
    public $uuid;

    /**
     * vg_name / VG
     *
     * @var string
     */
    public $name;

    /**
     * vg_attr / Attr
     *
     * e.g. 'wz--n-'
     *
     * @var VGAttributes
     */
    public $attributes;

    /**
     * vg_size / VSize
     *
     * @var Size
     */
    public $size;

    /**
     * vg_free / VFree
     *
     * @var Size
     */
    public $free;

    /**
     * vg_extent_size / Ext
     *
     * @var Size
     */
    public $extentSize;

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
     * @var LV[]
     */
    public $logicalVolumesByName = [];

    /**
     * @var Segment[]
     */
    public $segments = [];

    /**
     * VG constructor.
     *
     * @param LVM $lvm
     * @param string $uuid
     * @param string $name
     * @param VGAttributes $attributes
     * @param Size $size
     * @param Size $free
     * @param Size $extentSize
     */
    public function __construct(LVM $lvm, $uuid, $name, VGAttributes $attributes, Size $size, Size $free, Size $extentSize)
    {
        $this->lvm = $lvm;
        $this->uuid = $uuid;
        $this->name = $name;
        $this->attributes = $attributes;
        $this->size = $size;
        $this->free = $free;
        $this->extentSize = $extentSize;

        $this->lvm->volumeGroupsByUUID[$uuid] = $this;
        $this->lvm->volumeGroupsByName[$name] = $this;
    }
}
