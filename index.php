<?php

// @todo Also try to find the filesystem usage for each LV

namespace {
//    $cmd = "sudo " . __DIR__ . "/get-lvm-data.sh";
//$lvmData = shell_exec(escapeshellcmd($cmd));
    $pvsData = file_get_contents('example-data-pvs.txt');
    $lvsData = file_get_contents('example-data-lvs.txt');
}


namespace Aziraphale\LVM\Utility {
    /**
     * Class for converting to/from and displaying file/volume/extent sizes as
     *  given/used by LVM
     *
     * @package Aziraphale\LVM\Utility
     */
    class Size
    {
        // Constants indicating the number of bytes in various filesize levels:
        //  bytes, kilobytes, megabytes, gigabytes, terabytes, petabytes - and
        //  the binary versions of each of those: bytes, kibibytes, mebibytes,
        //  gibibytes, tebibytes, pebibytes. This helps make the below code more
        //  understandable and less prone to mathematical errors from the use
        //  of the wrong divisors.
        const S_B   = 1;
        const S_kB  = 1000;
        const S_kiB = 1024;
        const S_MB  = 1000000;
        const S_MiB = 1048576;
        const S_GB  = 1000000000;
        const S_GiB = 1073741824;
        const S_TB  = 1000000000000;
        const S_TiB = 1099511627776;
        const S_PB  = 1000000000000000;
        const S_PiB = 1125899906842624;

        // Keys used in the prefix arrays, so it can be more obvious what's
        //  being accessed than would be the case with raw numbers
        const P_B  = 0;
        const P_KB = 1;
        const P_MB = 2;
        const P_GB = 3;
        const P_TB = 4;
        const P_PB = 5;

        /**
         * The strings that we append to size figures to indicate their
         *  magnitude. These can be modified for all subsequently-created
         *  objects (via Size::$prefixesSiDefault) or on a per-instance basis
         *  (via $size->prefixesSi), for example to add a space before the
         *  suffix string, or to use full words instead of abbreviations.
         *
         * Note that these are called 'prefixes' because we're generally
         *  referring to the "k", "M", etc. order-of-magnitude PREFIX to the
         *  "B" unit - see https://en.wikipedia.org/wiki/Metric_prefix
         *
         * @var string[]
         */
        public static $prefixesSiDefault = [
            self::P_B  => 'B',
            self::P_KB => 'kB',
            self::P_MB => 'MB',
            self::P_GB => 'GB',
            self::P_TB => 'TB',
            self::P_PB => 'PB',
        ];

        /**
         * The strings that we append to size figures to indicate their
         *  magnitude. These can be modified for all subsequently-created
         *  objects (via Size::$prefixesBinaryDefault) or on a per-instance
         *  basis (via $size->prefixesBinary), for example to add a space
         *  before the suffix string, or to use full words instead of
         *  abbreviations.
         *
         * Note that these are called 'prefixes' because we're generally
         *  referring to the "k", "M", etc. order-of-magnitude PREFIX to the
         *  "B" unit - see https://en.wikipedia.org/wiki/Metric_prefix
         *
         * @var string[]
         */
        public static $prefixesBinaryDefault = [
            self::P_B  => 'B',
            self::P_KB => 'kiB',
            self::P_MB => 'MiB',
            self::P_GB => 'GiB',
            self::P_TB => 'TiB',
            self::P_PB => 'PiB',
        ];


        /**
         * The size that we're actually storing! Converted down to bytes as a
         *  sensible lowest common denominator
         *
         * @var int
         */
        private $bytes;

        /**
         * The strings that we append to size figures to indicate their
         *  magnitude. These can be modified for all subsequently-created
         *  objects (via Size::$prefixesSiDefault) or on a per-instance basis
         *  (via $size->prefixesSi), for example to add a space before the
         *  suffix string, or to use full words instead of abbreviations.
         *
         * Note that these are called 'prefixes' because we're generally
         *  referring to the "k", "M", etc. order-of-magnitude PREFIX to the
         *  "B" unit - see https://en.wikipedia.org/wiki/Metric_prefix
         *
         * @var string[]
         */
        public $prefixesSi = [
            self::P_B  => 'B',
            self::P_KB => 'kB',
            self::P_MB => 'MB',
            self::P_GB => 'GB',
            self::P_TB => 'TB',
            self::P_PB => 'PB',
        ];

        /**
         * The strings that we append to size figures to indicate their
         *  magnitude. These can be modified for all subsequently-created
         *  objects (via Size::$prefixesBinaryDefault) or on a per-instance
         *  basis (via $size->prefixesBinary), for example to add a space before
         *  the suffix string, or to use full words instead of abbreviations.
         *
         * Note that these are called 'prefixes' because we're generally
         *  referring to the "k", "M", etc. order-of-magnitude PREFIX to the
         *  "B" unit - see https://en.wikipedia.org/wiki/Metric_prefix
         *
         * @var string[]
         */
        public $prefixesBinary = [
            self::P_B  => 'B',
            self::P_KB => 'kiB',
            self::P_MB => 'MiB',
            self::P_GB => 'GiB',
            self::P_TB => 'TiB',
            self::P_PB => 'PiB',
        ];

        /**
         * Size constructor.
         *
         * @param int $bytes
         */
        private function __construct($bytes)
        {
            $this->bytes = (int) $bytes;

            $this->prefixesSi = static::$prefixesSiDefault;
            $this->prefixesBinary = static::$prefixesBinaryDefault;
        }

        /**
         * Converts from a LVM-displayed size value (where single-letter
         *  indicators are used, with uppercase letters indicating SI values and
         *  lowercase indicating binary values - so a new hard disk would be
         *  5.00T/4.55t, or 238G/221g
         *
         * @param string $size
         * @return static
         * @throws \InvalidArgumentException
         * @throws \RuntimeException
         */
        public static function fromLvmHuman($size)
        {
            if (!preg_match('/^\s*([0-9\.]+)([BkKmMgGtTpP])\s*$/S', $size, $matches)) {
                throw new \InvalidArgumentException(sprintf(
                    'Value passed for $size argument, `%s`, wasn\'t recognised as a valid LVM size string.',
                    $size
                ));
            }

            list(, $value, $suffixLetter) = $matches;

            $bytes = 0;
            $value = (float) $value;
            switch ($matches[2]) {
                case 'P':
                    $bytes = $value * static::S_PB;
                    break;
                case 'p':
                    $bytes = $value * static::S_PiB;
                    break;
                case 'T':
                    $bytes = $value * static::S_TB;
                    break;
                case 't':
                    $bytes = $value * static::S_TiB;
                    break;
                case 'G':
                    $bytes = $value * static::S_GB;
                    break;
                case 'g':
                    $bytes = $value * static::S_GiB;
                    break;
                case 'M':
                    $bytes = $value * static::S_MB;
                    break;
                case 'm':
                    $bytes = $value * static::S_MiB;
                    break;
                case 'K':
                    $bytes = $value * static::S_kB;
                    break;
                case 'k':
                    $bytes = $value * static::S_kiB;
                    break;
                case 'B':
                    $bytes = $value;
                    break;
                default:
                    throw new \RuntimeException(
                        sprintf(
                            "This shouldn't happen! Somehow, our regex matched".
                            " a file size letter `%s` that wasn't then in the ".
                            "switch statement block, so we don't know how to ".
                            "deal with the numeric value that we found: `%s`!",
                            $suffixLetter,
                            $value
                        )
                    );
            }

            return new static($bytes);
        }

        /**
         * Converts from a number of 'extents' as displayed in LVM outputs. The
         *  size of extents defaults to '4.00m' (4 MiB), as this is the value
         *  used by every LVM system I've encountered, but the extent size can
         *  be passed as well (as an LVM-style size, e.g., '4.00m', '500k',
         *  '32M') to override it
         *
         * @param int $extentCount
         * @param string $extentSize
         * @return static
         */
        public static function fromExtentCount($extentCount, $extentSize = '4.00m')
        {
            $extentSizeBytes = static::fromLvmHuman($extentSize);
            $extentSizeBytes->bytes *= $extentCount;
            return $extentSizeBytes;
        }

        /**
         * Simply returns a new Size object representing the number of bytes
         *  specified
         *
         * @param int $bytes
         * @return static
         */
        public static function fromBytes($bytes)
        {
            return new static($bytes);
        }

        /**
         * Returns a new Size object representing the combination of all of the passed size parts...
         *
         * @param float $PiB
         * @param float $pb
         * @param float $TiB
         * @param float $tb
         * @param float $GiB
         * @param float $gb
         * @param float $MiB
         * @param float $mb
         * @param float $KiB
         * @param float $kb
         * @param int $b
         * @return static
         * @throws \InvalidArgumentException
         */
        public static function fromParts($PiB = null, $pb = null, $TiB = null, $tb = null, $GiB = null, $gb = null, $MiB = null, $mb = null, $KiB = null, $kb = null, $b = null)
        {
            // Ensure none of the arguments are less than zero, as that makes no sense o.o
            foreach (func_get_args() as $k => $v) {
                $argNum = $k + 1;
                if (!is_numeric($v)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Argument #%d was not a valid numerical value! A %s of value `%s` was passed!',
                            $argNum,
                            gettype($v),
                            $v
                        )
                    );
                }
                if ($v < 0) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Argument #%d has a value less than zero! This is meaningless in this context! Value passed was `%s`.',
                            $argNum,
                            $v
                        )
                    );
                }
            }

            // On each line (i.e. for each argument/size component), cast it
            //  down to the type we expect and then multiply it by the
            //  appropriate constant to get the byte value of that argument,
            //  summing the lot together <3
            $bytes =
                (int)   $b   * static::S_B   +
                (float) $kb  * static::S_kB  +
                (float) $KiB * static::S_kiB +
                (float) $mb  * static::S_MB  +
                (float) $MiB * static::S_MiB +
                (float) $gb  * static::S_GB  +
                (float) $GiB * static::S_GiB +
                (float) $tb  * static::S_TB  +
                (float) $TiB * static::S_TiB +
                (float) $pb  * static::S_PB  +
                (float) $PiB * static::S_PiB
            ;

            return new static($bytes);
        }

        /**
         * Returns a human-readable form of this Size, selecting the most-
         *  appropriate binary- or SI-prefixed (depending on the value passed
         *  to $binaryPrefixes) size unit and using that to append the
         *  appropriate unit prefix string to the final value, thus having the
         *  return value be something like "1.23 MiB"
         *
         * @param bool $binaryPrefixes
         * @param array $prefixesList
         * @param int $decimalPlaces
         * @return string
         */
        protected function _toHuman($binaryPrefixes = true, array $prefixesList, $decimalPlaces = 2) {
            $sizeLevel = static::P_B;
            $sizeLevelCount = count($prefixesList);
            $displayValue = $this->bytes;
            $divisor = $binaryPrefixes ? 1024 : 1000;

            while ($displayValue >= $divisor && $sizeLevel < ($sizeLevelCount - 1)) {
                $displayValue /= $divisor;
                ++$sizeLevel;
            }

            // Exception - can't have partial bytes!
            if ($sizeLevel === static::P_B) {
                $decimalPlaces = 0;
            }

            return static::r($displayValue, $decimalPlaces) . $prefixesList[$sizeLevel];
        }

        /**
         * Returns a human-readable form of this Size, selecting the most-
         *  appropriate binary-prefixed (e.g. "MiB") size unit and using that
         *  to append the appropriate unit prefix string to the final value,
         *  thus having the return value be something like "1.23 MiB"
         *
         * @param int $decimalPlaces
         * @return string
         */
        public function toHumanBinary($decimalPlaces = 2)
        {
            return $this->_toHuman(true, $this->prefixesBinary, $decimalPlaces);
        }

        /**
         * Returns a human-readable form of this Size, selecting the most-
         *  appropriate SI-prefixed (e.g. "MB") size unit and using that to
         *  append the appropriate unit prefix string to the final value, thus
         *  having the return value be something like "1.23 MB"
         *
         * @param int $decimalPlaces
         * @return string
         */
        public function toLvmHuman($decimalPlaces = 2)
        {
            return $this->_toHuman(false, $this->prefixesSi, $decimalPlaces);
        }

        /**
         * Returns the number of LVM extents (of the specified size, defaulting
         *  to 4 MB) represented by this Size
         *
         * @param string $extentSize
         * @return int
         * @throws \InvalidArgumentException
         * @throws \RuntimeException
         */
        public function extents($extentSize = '4.00m')
        {
            $extentBytes = static::fromLvmHuman($extentSize);
            return ceil($this->bytes / $extentBytes);
        }

        /**
         * Convenience function to round and display a number to and with the
         *  specified number of decimal places. Required because
         *  `number_format()` doesn't round (`5.6789` formatted to 2DP would
         *  become `5.67` instead of `5.68`) and `round()` doesn't add zeroes
         *  after the decimal point if necessary (leaving `1.497` rounded to
         *  2DP displaying as `1.5` instead of `1.50`). `number_format()` also
         *  does locale-specific stuff, I believe (like using spaces as
         *  thousand-separators and commas as decimal-points).
         *
         * @param $size
         * @param int $decimals
         * @return string
         */
        private static function r($size, $decimals = 2)
        {
            return number_format(round($size, $decimals), $decimals);
        }

        /**
         * Returns the Size as a number of bytes (no included unit suffix)
         *
         * @return string
         */
        public function B()
        {
            return static::r($this->bytes, 0);
        }

        /**
         * Returns the Size as a number of kilobytes (no included unit suffix)
         *
         * @return string
         */
        public function kB()
        {
            return static::r($this->bytes / static::S_kB, 2);
        }

        /**
         * Returns the Size as a number of kibibytes (no included unit suffix)
         *
         * @return string
         */
        public function KiB()
        {
            return static::r($this->bytes / static::S_kiB, 2);
        }

        /**
         * Returns the Size as a number of megabytes (no included unit suffix)
         *
         * @return string
         */
        public function MB()
        {
            return static::r($this->bytes / static::S_MB, 2);
        }

        /**
         * Returns the Size as a number of mebibytes (no included unit suffix)
         *
         * @return string
         */
        public function MiB()
        {
            return static::r($this->bytes / static::S_MiB, 2);
        }

        /**
         * Returns the Size as a number of gigabytes (no included unit suffix)
         *
         * @return string
         */
        public function GB()
        {
            return static::r($this->bytes / static::S_GB, 2);
        }

        /**
         * Returns the Size as a number of gibibytes (no included unit suffix)
         *
         * @return string
         */
        public function GiB()
        {
            return static::r($this->bytes / static::S_GiB, 2);
        }

        /**
         * Returns the Size as a number of terabytes (no included unit suffix)
         *
         * @return string
         */
        public function TB()
        {
            return static::r($this->bytes / static::S_TB, 2);
        }

        /**
         * Returns the Size as a number of tebibytes (no included unit suffix)
         *
         * @return string
         */
        public function TiB()
        {
            return static::r($this->bytes / static::S_TiB, 2);
        }

        /**
         * Returns the Size as a number of petabytes (no included unit suffix)
         *
         * @return string
         */
        public function PB()
        {
            return static::r($this->bytes / static::S_PB, 2);
        }

        /**
         * Returns the Size as a number of pebibytes (no included unit suffix)
         *
         * @return string
         */
        public function PiB()
        {
            return static::r($this->bytes / static::S_PiB, 2);
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

        public function __construct()
        {
            if (PHP_INT_MAX < 4294967296) {
                throw new \RuntimeException('This version/build of PHP does not support 64-bit integers and therefore cannot be used to run this software.');
            }
        }
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


/*
PV"       ,"DevSize","PV UUID",                               "VG",  "VG UUID",                               "Attr",  "VSize", "VFree","Ext",  "Fmt", "Attr","PSize", "PFree",  "PE",
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721",
        "Start","SSize","LV","LV UUID","Parent","Start","SSize","PE Ranges","Devices","Type","Attr","Layout","Role","Origin","Snap%","Cpy%Sync","Move","Log","#Str
        "0","30575","","","","0","30575","","","free","","unknown","public","","","","","","0
    "30575","7936","[sys-root_mimage_0]","JSYd0p-FZZP-JoMt-gbpK-CEfb-AasK-kApdmU","sys-root","0","7936","/dev/sda1:30575-38510","/dev/sda1(30575)","linear","iwi-aom---","linear","private,mirror,image","","","","","","1
    "38511","1","[pictures_mlog]","UiBG7u-QuCb-BwdS-yEDf-wSYA-C2du-BzuRlk","pictures","0","1","/dev/sda1:38511-38511","/dev/sda1(38511)","linear","lwi-aom---","linear","private,mirror,log","","","","","","1
    "38512","1","[tv_mlog]","KfrGBc-C2nr-ZZeE-KmTP-U0jO-R3jQ-zOlnlX","tv","0","1","/dev/sda1:38512-38512","/dev/sda1(38512)","linear","lwi-aom---","linear","private,mirror,log","","","","","","1
    "46193","10528","","","","0","10528","","","free","","unknown","public","","","","","","0
/dev/sdb1","3.64t","hBhxWU-jwHk-cgTf-OBgD-TeNO-euHa-Y2BeKw","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","3.64t","0 ","953861","0","953861","[tv_mimage_1]","PBYYu7-kGxa-2cYh-KMYG-j072-vRfQ-eX0HAY","tv","953861","953861","/dev/sdb1:0-953860","/dev/sdb1(0)","linear","iwi-aom---","linear","private,mirror,image","","","","","","1
*/
/*
PV","DevSize","PV UUID","VG","VG UUID","Attr","VSize","VFree","Ext","Fmt","Attr","PSize","PFree","PE","Start","SSize","LV","LV UUID","Parent","Start","SSize","PE Ranges","Devices","Type","Attr","Layout","Role","Origin","Snap%","Cpy%Sync","Move","Log","#Str
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","0","30575","","","","0","30575","","","free","","unknown","public","","","","","","0
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","30575","7936","[sys-root_mimage_0]","JSYd0p-FZZP-JoMt-gbpK-CEfb-AasK-kApdmU","sys-root","0","7936","/dev/sda1:30575-38510","/dev/sda1(30575)","linear","iwi-aom---","linear","private,mirror,image","","","","","","1
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","38511","1","[pictures_mlog]","UiBG7u-QuCb-BwdS-yEDf-wSYA-C2du-BzuRlk","pictures","0","1","/dev/sda1:38511-38511","/dev/sda1(38511)","linear","lwi-aom---","linear","private,mirror,log","","","","","","1
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","38512","1","[tv_mlog]","KfrGBc-C2nr-ZZeE-KmTP-U0jO-R3jQ-zOlnlX","tv","0","1","/dev/sda1:38512-38512","/dev/sda1(38512)","linear","lwi-aom---","linear","private,mirror,log","","","","","","1
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","46193","10528","","","","0","10528","","","free","","unknown","public","","","","","","0
/dev/sdb1","3.64t","hBhxWU-jwHk-cgTf-OBgD-TeNO-euHa-Y2BeKw","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","3.64t","0 ","953861","0","953861","[tv_mimage_1]","PBYYu7-kGxa-2cYh-KMYG-j072-vRfQ-eX0HAY","tv","953861","953861","/dev/sdb1:0-953860","/dev/sdb1(0)","linear","iwi-aom---","linear","private,mirror,image","","","","","","1
*/
