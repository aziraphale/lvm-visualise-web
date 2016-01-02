<?php
namespace Aziraphale\LVM\Utility;

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
    const S_B = 1;
    const S_kB = 1000;
    const S_kiB = 1024;
    const S_MB = 1000000;
    const S_MiB = 1048576;
    const S_GB = 1000000000;
    const S_GiB = 1073741824;
    const S_TB = 1000000000000;
    const S_TiB = 1099511627776;
    const S_PB = 1000000000000000;
    const S_PiB = 1125899906842624;

    // Keys used in the prefix arrays, so it can be more obvious what's
    //  being accessed than would be the case with raw numbers
    const P_B = 0;
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
        self::P_B => 'B',
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
        self::P_B => 'B',
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
        self::P_B => 'B',
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
        self::P_B => 'B',
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
        $this->bytes = (int)$bytes;

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
        $value = (float)$value;
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
                        "This shouldn't happen! Somehow, our regex matched" .
                        " a file size letter `%s` that wasn't then in the " .
                        "switch statement block, so we don't know how to " .
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
            (int)$b * static::S_B +
            (float)$kb * static::S_kB +
            (float)$KiB * static::S_kiB +
            (float)$mb * static::S_MB +
            (float)$MiB * static::S_MiB +
            (float)$gb * static::S_GB +
            (float)$GiB * static::S_GiB +
            (float)$tb * static::S_TB +
            (float)$TiB * static::S_TiB +
            (float)$pb * static::S_PB +
            (float)$PiB * static::S_PiB;

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
    protected function _toHuman($binaryPrefixes = true, array $prefixesList, $decimalPlaces = 2)
    {
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
