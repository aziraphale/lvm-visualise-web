<?php
namespace Aziraphale\LVM;

/**
 * An LV that is a snapshot of another LV
 *
 * @package Aziraphale\LVM
 */
class SnapshotLV extends LV
{
    /**
     * origin / Origin
     *
     * @var LV
     */
    public $origin;

    /**
     * snap_percent / Snap%
     *
     * @var float
     */
    public $snapshotPercentage;

    /**
     * SnapshotLV constructor.
     *
     * @param LVM $lvm
     * @param VG $vg
     * @param string $uuid
     * @param string $name
     * @param float $snapshotPercentage
     * @param LV $origin
     */
    public function __construct(LVM $lvm, VG $vg, $uuid, $name, $snapshotPercentage, LV $origin)
    {
        $this->snapshotPercentage = (float) $snapshotPercentage;
        $this->origin = $origin;

        $this->origin->snapshotsByUUID[$uuid] = $this;
        $this->origin->snapshotsByName[$name] = $this;

        parent::__construct($lvm, $vg, $uuid, $name);
    }
}
