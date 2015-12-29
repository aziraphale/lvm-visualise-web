<?php

// @todo Also try to find the filesystem usage for each LV

namespace {
//    $cmd = "sudo " . __DIR__ . "/get-lvm-data.sh";
//$lvmData = shell_exec(escapeshellcmd($cmd));
    $pvsData = file_get_contents('example-data-pvs.txt');
    $lvsData = file_get_contents('example-data-lvs.txt');
}


namespace Aziraphale\LVM\Utility {
    class Size
    {
        const S_B   = 1;
        const S_kB  = 1000;
        const S_kiB = 1024;
        const S_MB  = 1000000;
        const S_MiB = 1048576;
        const S_GB  = 1000000000;
        const S_GiB = 1073741824;
        const S_TB  = 1000000000000;
        const S_TiB = 1099511627776;

        private $bytes;

        public static function fromHuman($size)
        {
            // e.g. 1.23g, 5.43T, etc. - lowercase letter is binary prefixes
        }

        public static function fromExtentCount($extentCount, $extentSize = '4.00m')
        {

        }

        public static function fromParts($TiB = null, $tb = null, $GiB = null, $gb = null, $MiB = null, $mb = null, $KiB = null, $b = null)
        {

        }

        public function toHuman()
        {

        }

        public function extents($extentSize = '4.00m')
        {
            $extentBytes = static::fromHuman($extentSize);
            return ceil($this->bytes / $extentBytes);
        }

        private static function r($size, $decimals = 2)
        {
            return number_format(round($size, $decimals), $decimals);
        }

        public function B()
        {
            return static::r($this->bytes, 0);
        }

        public function kB()
        {
            return static::r($this->bytes / static::S_kB, 2);
        }

        public function KiB()
        {
            return static::r($this->bytes / static::S_kiB, 2);
        }

        public function MB()
        {
            return static::r($this->bytes / static::S_MB, 2);
        }

        public function MiB()
        {
            return static::r($this->bytes / static::S_MiB, 2);
        }

        public function GB()
        {
            return static::r($this->bytes / static::S_GB, 2);
        }

        public function GiB()
        {
            return static::r($this->bytes / static::S_GiB, 2);
        }

        public function TB()
        {
            return static::r($this->bytes / static::S_TB, 2);
        }

        public function TiB()
        {
            return static::r($this->bytes / static::S_TiB, 2);
        }
    }

    abstract class Attributes
    {

    }

    class PVAttributes extends Attributes
    {

    }

    class VGAttributes extends Attributes
    {

    }

    class LVAttributes extends Attributes
    {

    }
}


namespace Aziraphale\LVM {

    use Aziraphale\LVM\Utility\LVAttributes;
    use Aziraphale\LVM\Utility\Size;
    use Aziraphale\LVM\Utility\PVAttributes;
    use Aziraphale\LVM\Utility\VGAttributes;

    /**
     * Top-level container class to store references to all the volume groups,
     *  physical volumes, logical volumes and segments that comprise the LVM
     *  layout we're displaying
     *
     * @package Aziraphale\LVM
     */
    class LVM
    {
        /**
         * @var VG[]
         */
        public $volumeGroupsByUUID = [];

        /**
         * @var VG[]
         */
        public $volumeGroupsByName = [];

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
         * @var Segment[]
         */
        public $segments = [];
    }

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

    /**
     * A single segment in this LVM layout. A segment has both physical and
     *  logical dimensions (i.e. a start position and a length). This class
     *  will always be extended in order to define more precisely what this
     *  segment is and does.
     *
     * @package Aziraphale\LVM
     */
    abstract class Segment
    {
        /**
         * @var LVM
         */
        public $lvm;

        /**
         * The PV that this segment is a part of
         *
         * @var PV
         */
        public $physicalVolume;

        /**
         * The LV that this segment is a part of
         *
         * @var LV
         */
        public $logicalVolume;

        /**
         * pvseg_start / Start
         *
         * @var int
         */
        public $physicalStart;

        /**
         * pvseg_size / SSize
         *
         * Note that $physicalExtentCount and $logicalExtentCount are likely to be the same
         *
         * @var int
         */
        public $physicalExtentCount;

        /**
         * The physical byte size of this segment
         *
         * @var Size
         */
        public $physicalSize;

        /**
         * seg_start / Start
         *
         * @var int
         */
        public $logicalStart;

        /**
         * seg_size / SSize
         *
         * Note that $physicalExtentCount and $logicalExtentCount are likely to be the same
         *
         * @var int
         */
        public $logicalExtentCount;

        /**
         * The logical byte size of this segment
         *
         * @var Size
         */
        public $logicalSize;

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
         */
        public function __construct(LVM $lvm, PV $physicalVolume, LV $logicalVolume, $physicalStart, $physicalExtentCount, $logicalExtentCount, $logicalStart)
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
        }
    }

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

    /**
     * A public-facing LV - probably a standard, linear LV, but this class is
     *  extended to create other LV types, so that can't be guaranteed unless
     *  this object is exactly this class, rather than a subclass
     *
     * @package Aziraphale\LVM
     */
    class PublicLV extends LV
    {
        /**
         * lv_attr / Attr
         *
         * @var LVAttributes
         */
        public $attributes;

        /**
         * lv_layout / Layout
         *
         * @var string
         */
        public $layout;

        /**
         * lv_role / Role
         *
         * @var string
         */
        public $role;

        /**
         * move_pv /  Move
         *
         * @var string
         */
        public $movePhysicalVolume;

        /**
         * stripes / #Str
         *
         * @var int
         */
        public $stripesCount;

        /**
         * PublicLV constructor.
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
         */
        public function __construct(LVM $lvm, VG $vg, $uuid, $name, LVAttributes $attributes, $layout, $role, $movePhysicalVolume, $stripesCount)
        {
            $this->attributes = $attributes;
            $this->layout = $layout;
            $this->role = $role;
            $this->movePhysicalVolume = $movePhysicalVolume;
            $this->stripesCount = $stripesCount;

            parent::__construct($lvm, $vg, $uuid, $name);
        }
    }

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
            $this->syncPercentage = (float) $syncPercentage;

            parent::__construct($lvm, $vg, $uuid, $name, $attributes, $layout, $role, $movePhysicalVolume, $stripesCount);
        }
    }

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
                $this->mirrorLegNumber = (int) $m[1];
                $this->parent->mirrorLegsBySequenceNumber[$this->mirrorLegNumber] = $this;
            }

            $this->parent->mirrorLegsByUUID[$uuid] = $this;
        }
    }

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
                $this->mirrorLogNumber = (int) $m[1];
                $this->parent->mirrorLogsBySequenceNumber[$this->mirrorLogNumber] = $this;
            }

            $this->parent->mirrorLogsByUUID[$uuid] = $this;
        }
    }

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
}
