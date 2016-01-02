<?php
namespace Aziraphale\LVM;

/**
 * One of a mirrored LV's mirror leg LVs. An active mirrored LV will have
 *  at least two mirror legs
 *
 * @package Aziraphale\LVM
 */
class MirrorLegLV extends MirrorInternalLV
{
    /**
     * This is the integer number at the end of the LV name, e.g. for a LV
     *  named `[foo_mimage_0]`, this would have a value of `0`
     *
     * @var int
     */
    public $mirrorLegNumber;

    /**
     * MirrorLegLV constructor.
     *
     * @param LVM $lvm
     * @param VG $vg
     * @param string $uuid
     * @param string $name
     * @param PublicMirrorLV $parent
     */
    public function __construct(LVM $lvm, VG $vg, $uuid, $name, PublicMirrorLV $parent)
    {
        parent::__construct($lvm, $vg, $uuid, $name, $parent);

        if (preg_match('/_(\d+)\]$/S', $name, $m)) {
            $this->mirrorLegNumber = (int)$m[1];
            $this->parent->mirrorLegsBySequenceNumber[$this->mirrorLegNumber] = $this;
        }

        $this->parent->mirrorLegsByUUID[$uuid] = $this;
    }
}
