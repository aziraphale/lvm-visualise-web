<?php
namespace Aziraphale\LVM;

use Aziraphale\LVM\Exception\DataLoad\ProcessFailedException;
use Aziraphale\LVM\Exception\DataLoad\ProcessSetupException;
use Aziraphale\LVM\Exception\DataLoad\ProcessTimedOutException;
use Aziraphale\LVM\Exception\DataLoad\UnknownProcessException;
use InvalidArgumentException;
use OutOfBoundsException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException as Symfony_ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException as Symfony_ProcessTimedOutException;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Top-level container class to store references to all the volume groups,
 *  physical volumes, logical volumes and segments that comprise the LVM
 *  layout we're displaying.
 *
 * Note that most properties in the classes in this LVM package are public. This
 *  is a lazy shortcut to allow access to the data until I get round to adding
 *  accessor methods for each of them. You MUST consider these properties to be
 *  READ-ONLY to everything outside of the LVM library! That is, other
 *  LVM/PV/VG/LVM/etc. classes are permitted to make changes (usually appending
 *  to arrays) to these class properties, but nothing outside of the
 *  Aziraphale\LVM namespace is permitted to make any changes at all.
 *
 * @package Aziraphale\LVM
 */
class LVM
{
    /**
     * The number of seconds to wait for the external "load our data"
     *  script/process to respond before giving up and terminating it. This time
     *  period is reset every time any data is read from the external process
     *
     * @var int
     */
    public static $loadDataTimeoutShortSecs = 10;

    /**
     * The total number of seconds that we will wait for the external "load our
     *  data" script/process to finish. If it takes longer than this, it will be
     *  killed. Remember that our default script runs several commands
     *  sequentially, and this timeout period needs to be able to encompass all
     *  of them
     *
     * @var int
     */
    public static $loadDataTimeoutLongSecs = 45;

    /**
     * Whether we need to use `sudo` in order to run our data-fetching scripts.
     *
     * Those scripts run the `pvs` and `lvs` commands (at least) and those
     *  commands always need to be run as root. The best way to give this script
     *  root access for those commands is to add a line or two in `/etc/sudoers`
     *  (via `sudo visudo`) permitting the webserver user account to run the .sh
     *  scripts in our ./bin directory without supplying a password to `sudo`
     *  (ideally the .sh scripts would then also be made read-only to the
     *  webserver user, so that a malicious web script couldn't modify "trusted
     *  scripts" to do nasty things).
     * The other options are:
     *  - permitting the webserver user to use sudo without a password to do
     *    /anything/, but then you have a giant security hole where any
     *    malicious or vulnerable script could take over the whole server very
     *    easily;
     *  - adding a password to the webserver user account and giving that
     *    password to this script, but then you risk remote agents connecting
     *    via SSH to the webserver's user - and then using sudo to take over the
     *    whole box (which is why this script DOES NOT SUPPORT being given a
     *    `sudo` password...);
     *  - or running the webserver as root, which has the same security issues
     *    as the first alternative option and is thus just as unwise
     * Seriously, just go with the first option - it's not that difficult!
     *
     * This option exists so that the script can continue to function even on a
     *  system where the webserver runs as root (thus `sudo` isn't required) and
     *  where `sudo` doesn't even exist, so attempting to run it would fail
     *  horribly.
     *
     * @var bool
     */
    public static $loadDataRequiresSudo = true;

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

    /**
     * Root directory (excluding trailing slash) for this LVM package - the
     *  directory in which our composer.json file resides
     *
     * @var string
     */
    protected static $_pkgRootDir;

    /**
     * Returns this package's root directory (excluding the trailing slash) -
     *  the directory in which our composer.json file resides
     *
     * @return string
     * @throws RuntimeException
     */
    public static function pkgRootDir()
    {
        if (!isset(static::$_pkgRootDir)) {
            $dir = __DIR__;
            while (!file_exists("$dir/composer.json") && !file_exists("$dir/composer.lock")) {
                $dir = dirname($dir);
                if ($dir === '.') {
                    throw new RuntimeException("Unable to find a parent directory of the LVM package that contains a composer.json/composer.lock file!");
                }
            }

            static::$_pkgRootDir = $dir;
        }

        return static::$_pkgRootDir;
    }

    /**
     * Requests all the required data from the Logical Volume Manager on the
     *  local machine, processing that data and storing it as objects in the
     *  returned LVM object. If $useTestData is TRUE, the loaded data will come
     *  from dummy/example .txt files instead of from running the various LVM
     *  commands - this is intended for testing/development purposes, as running
     *  the LVM commands can be moderately time-consuming and thus not desirable
     *  during a rapid development process. If $loadDataCallback is specified,
     *  that function will be called instead in order to retrieve the LVM data
     *  (thus permitting for a crude way of loading the data from another
     *  computer via SSH, for example). The callback function will be passed one
     *  argument: the $useTestData boolean. The callback will be expected to
     *  return the exact same data structure as LVM::loadData(), so see that
     *  method for details.
     *
     * @param bool $useTestData
     * @param callable $loadDataCallback
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws ProcessFailedException
     * @throws ProcessSetupException
     * @throws ProcessTimedOutException
     * @throws UnknownProcessException
     * @throws OutOfBoundsException
     */
    public function __construct($useTestData = false, callable $loadDataCallback = null)
    {
        if (PHP_INT_MAX < 4294967296) {
            throw new RuntimeException(
                'This version/build of PHP does not support 64-bit integers '.
                'and therefore cannot be used to run this software.'
            );
        }

        if ($loadDataCallback !== null) {
            if (!is_callable($loadDataCallback)) {
                throw new InvalidArgumentException(
                    "The \$loadDataCallback argument to this method must ".
                    "either be skipped (by passing NULL) or must be a valid ".
                    "callback function. In this case, neither of these were ".
                    "passed."
                );
            }

            $lvmData = call_user_func($loadDataCallback, $useTestData);

            if (!isset($lvmData['pvs'], $lvmData['lvs'], $lvmData['time'])) {
                throw new OutOfBoundsException(
                    "The \$loadDataCallback method passed to the LVM ".
                    "constructor did not return the expected data. It must ".
                    "return the same data in the same structure as does the ".
                    "LVM::loadData() method."
                );
            }
        } else {
            $lvmData = static::loadData($useTestData);
        }

        $pvsData = $lvmData['pvs'];
        $lvsData = $lvmData['lvs'];
        $timeTaken = $lvmData['time'];

        // @todo create stuff!
    }

    /**
     * Loads LVM data from the current machine, returning it as an array of raw
     *  strings, straight from the commands' output.
     *
     * If $useTestData is TRUE, this method will load and return some dummy
     *  example data from local files instead of waiting for some
     *  relatively-slow LVM commands to respond, thus improving development
     *  speed.
     *
     * The return array will have these elements:
     *  'pvs' => (string) Output of `pvs`
     *  'lvs' => (string) Output of `lvs`
     *  'time' => (float) Time it took to load the data (seconds; with µs)
     *
     * @param bool $useTestData
     * @return array
     * @throws RuntimeException
     * @throws ProcessFailedException
     * @throws ProcessSetupException
     * @throws ProcessTimedOutException
     * @throws UnknownProcessException
     */
    protected static function loadData($useTestData = false)
    {
        $binDir = static::pkgRootDir() . '/bin';
        $dev = $useTestData ? '@dev' : '';

        try {
            $builder = new ProcessBuilder();

            if (static::$loadDataRequiresSudo) {
                $builder->setPrefix('sudo');
            }

            $pvsProcess = $builder
                ->setArguments(["$binDir/pvs$dev.sh"])
                ->getProcess()
                ->setIdleTimeout(static::$loadDataTimeoutShortSecs)
                ->setTimeout(static::$loadDataTimeoutLongSecs)
            ;
            $lvsProcess = $builder
                ->setArguments(["$binDir/lvs$dev.sh"])
                ->getProcess()
                ->setIdleTimeout(static::$loadDataTimeoutShortSecs)
                ->setTimeout(static::$loadDataTimeoutLongSecs)
            ;
        } catch (\Exception $ex) {
            throw new ProcessSetupException($ex);
        }

        try {
            $procStartTime = microtime(true);

            // These will throw a ProcessFailed exception if they exist with non-zero status
            $pvsProcess->mustRun();
            $lvsProcess->mustRun();

            return [
                'pvs' => $pvsProcess->getOutput(),
                'lvs' => $lvsProcess->getOutput(),
                'time' => microtime(true) - $procStartTime,
            ];
        } catch (Symfony_ProcessFailedException $ex) {
            throw new ProcessFailedException($ex);
        } catch (Symfony_ProcessTimedOutException $ex) {
            throw new ProcessTimedOutException($ex);
        } catch (\Exception $ex) {
            throw new UnknownProcessException($ex);
        }
    }
}
