<?php
namespace Aziraphale\LVM;
use Aziraphale\LVM\Utility\LVAttributes;

/**
 * The public-facing LV of a mirrored volume (where this is essentially a
 *  container for the references to the mirror leg LVs and the mirror
 *  log LV(s))
 *
 * @package Aziraphale\LVM
 */
class PublicMirrorLV extends PublicLV
{
    /**
     * copy_percent / Cpy%Sync
     *
     * @var float
     */
    public $syncPercentage;

    /**
     * The mirror log LV(s) for this volume
     *
     * @var MirrorLogLV[]
     */
    public $mirrorLogsByUUID = [];

    /**
     * The mirror log LV(s) for this volume
     *
     * @var MirrorLogLV[]
     */
    public $mirrorLogsBySequenceNumber = [];

    /**
     * The mirror leg LVs for this volume
     *
     * @var MirrorLegLV[]
     */
    public $mirrorLegsByUUID = [];

    /**
     * The mirror leg LVs for this volume
     *
     * @var MirrorLegLV[]
     */
    public $mirrorLegsBySequenceNumber = [];

    /**
     * PublicMirrorLV constructor.
     *
     * @param LVM $lvm
     * @param VG $vg
     * @param string $uuid
     * @param string $name
     * @param LVAttributes $attributes
     * @param string $layout
     * @param string $role
     * @param string $movePhysicalVolume
     * @param int $stripesCount
     * @param float $syncPercentage
     */
    public function __construct(LVM $lvm, VG $vg, $uuid, $name, LVAttributes $attributes, $layout, $role, $movePhysicalVolume, $stripesCount, $syncPercentage)
    {
        $this->syncPercentage = (float)$syncPercentage;

        parent::__construct($lvm, $vg, $uuid, $name, $attributes, $layout, $role, $movePhysicalVolume, $stripesCount);
    }
}
