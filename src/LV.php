<?php
namespace Aziraphale\LVM;

/**
 * The bare basics of a logical volume, whether it's a standard LV, a
 *  mirror leg, a snapshot or even just a mirror log
 *
 * @package Aziraphale\LVM
 */
abstract class LV
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
     * lv_uuid / LV UUID
     *
     * @var string
     */
    public $uuid;

    /**
     * lv_name / LV
     *
     * @var string
     */
    public $name;

    /**
     * The segments that comprise this LV
     *
     * @var Segment[]
     */
    public $segments = [];

    /**
     * Any snapshots that this LV has
     *
     * @var SnapshotLV[]
     */
    public $snapshotsByUUID = [];

    /**
     * Any snapshots that this LV has
     *
     * @var SnapshotLV[]
     */
    public $snapshotsByName = [];

    /**
     * LV constructor.
     * @param LVM $lvm
     * @param VG $vg
     * @param string $uuid
     * @param string $name
     */
    public function __construct(LVM $lvm, VG $vg, $uuid, $name)
    {
        $this->lvm = $lvm;
        $this->vg = $vg;
        $this->uuid = $uuid;
        $this->name = $name;

        $this->lvm->logicalVolumesByUUID[$uuid] = $this;

        $this->vg->logicalVolumesByUUID[$uuid] = $this;
        $this->vg->logicalVolumesByName[$name] = $this;
    }
}
