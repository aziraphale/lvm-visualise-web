<?php
namespace Aziraphale\LVM;

/**
 * Abstract class for the two types of "internal" LV that mirrored LVs are
 *  comprised of (i.e. mirror legs and mirror log(s)). These LVs aren't
 *  exposed directly, generally, but they're still displayed at times so
 *  that one can see on which PV each mirror leg/log resides
 *
 * @package Aziraphale\LVM
 */
abstract class MirrorInternalLV extends LV
{
    /**
     * lv_parent / Parent
     *
     * @var PublicMirrorLV
     */
    public $parent;

    /**
     * MirrorInternalLV constructor.
     *
     * @param LVM $lvm
     * @param VG $vg
     * @param string $uuid
     * @param string $name
     * @param PublicMirrorLV $parent
     */
    public function __construct(LVM $lvm, VG $vg, $uuid, $name, PublicMirrorLV $parent)
    {
        $this->parent = $parent;

        parent::__construct($lvm, $vg, $uuid, $name);
    }
}
