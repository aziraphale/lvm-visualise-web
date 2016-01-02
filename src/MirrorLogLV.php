<?php
namespace Aziraphale\LVM;

/**
 * One of a mirrored LV's mirror log LVs (if any). A mirrored LV can have
 *  either zero logs ('core' log mode), one log (standard), or two logs
 *  (mirrored logs)
 *
 * @package Aziraphale\LVM
 */
class MirrorLogLV extends MirrorInternalLV
{
    /**
     * This is the integer number at the end of the log LV's name. E.g. for
     *  a LV named `[foo_mlog_0]` this would have a value of `0`, or for a
     *  single-log system where the log is named `[foo_mlog]`, this value
     *  would be NULL
     *
     * @var int
     */
    public $mirrorLogNumber;

    /**
     * MirrorLogLV constructor.
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
            $this->mirrorLogNumber = (int)$m[1];
            $this->parent->mirrorLogsBySequenceNumber[$this->mirrorLogNumber] = $this;
        }

        $this->parent->mirrorLogsByUUID[$uuid] = $this;
    }
}
