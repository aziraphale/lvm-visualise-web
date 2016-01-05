<?php
namespace Aziraphale\LVM;

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
     * @throws \RuntimeException
     */
    public static function pkgRootDir()
    {
        if (!isset(static::$_pkgRootDir)) {
            $dir = __DIR__;
            while (!file_exists("$dir/composer.json") && !file_exists("$dir/composer.lock")) {
                $dir = dirname($dir);
                if ($dir === '.') {
                    throw new \RuntimeException("Unable to find a parent directory of the LVM package that contains a composer.json/composer.lock file!");
                }
            }

            static::$_pkgRootDir = $dir;
        }

        return static::$_pkgRootDir;
    }

    /**
     * Requests all the required data from the Logical Volume Manager on the local machine, processing that data and storing it as objects in the returned LVM object. If $useTestData is TRUE, the loaded data will come from dummy/example .txt files instead of from running the various LVM commands - this is intended for testing/development purposes, as running the LVM commands can be moderately time-consuming and thus not desirable during a rapid development process. If $loadDataCallback is specified, that function will be called instead in order to retrieve the LVM data (thus permitting for a crude way of loading the data from another computer via SSH, for example). The callback function will be passed one argument: the $useTestData boolean. The callback will be expected to return the exact same data structure as LVM::loadData(), so see that method for details.
     *
     * @param bool $useTestData
     * @param callable $loadDataCallback
     * @throws \RuntimeException
     */
    public function __construct($useTestData = false, callable $loadDataCallback = null)
    {
        if (PHP_INT_MAX < 4294967296) {
            throw new \RuntimeException('This version/build of PHP does not support 64-bit integers and therefore cannot be used to run this software.');
        }

        if ($loadDataCallback !== null) {
            if (!is_callable($loadDataCallback)) {
                throw new \InvalidArgumentException("The \$loadDataCallback argument to this method must either be skipped (by passing NULL) or must be a valid callback function. In this case, neither of these were passed.");
            }

            $lvmData = call_user_func($loadDataCallback, $useTestData);
        } else {
            $lvmData = static::loadData($useTestData);
        }

        list($pvsData, $lvsData) = $lvmData;

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
     *  0 => (string) Output of `pvs`
     *  1 => (string) Output of `lvs`
     *
     * @param bool $useTestData
     * @return array(string, string)
     * @throws \RuntimeException
     */
    protected static function loadData($useTestData = false)
    {
        $script = static::pkgRootDir() . '/bin/' . ($useTestData ? 'get-lvm-data_dummy.sh' : 'get-lvm-data.sh');

        $procDescriptorsSpec = [
            0 => ['pipe', 'r'], // We READ from the STDIN pipe
            1 => ['pipe', 'w'], // We WRITE to the STDOUT pipe
            2 => ['pipe', 'r'], // We READ from the STDERR pipe
            3 => ['pipe', 'r'], // We READ from the output of the `pvs` command
            4 => ['pipe', 'r'], // We READ from the output of the `lvs` command
        ];

        $proc = proc_open($script, $procDescriptorsSpec, $pipes, static::pkgRootDir());
        $procOpenTime = microtime(true);

        if (!$proc) {
            throw new \RuntimeException("Failed to execute the script that loads the LVM data! \$php_errormsg contains: `$php_errormsg`");
        }

        // From here on out, we have an open process handle and so need to explicitly close it (and its pipes) before we leave this method, even if we leave via an exception!
        try {
            // Make ourselves a few buffers for the data that we're going to load in...
            $bufStdout = $bufStderr = $bufPvs = $bufLvs = '';

            do {
                // Okay, we should have an opened, valid process! It's probably possible that it exited immediately without returning our data, though, so let's check that that isn't the case!
                $procStatus = proc_get_status($proc);

                // @todo Include stdout/stderr in these exceptions
                // @todo Implement our own timeouts: one for total time taken (30s? 45?) and one for time between any amount of response (reset every time we can read any data) (5s? 10s?)
                if ($procStatus['signaled']) {
                    // signaled = terminated by an uncaught signal
                    //   (termsig = number of signal that caused process to terminate)
                    throw new \RuntimeException("LVM-data-fetching script terminated due to an uncaught signal (SIG={$procStatus['termsig']})");
                } elseif ($procStatus['stopped']) {
                    // stopped = process has been stopped by a signal
                    //   (stopsig = number of signal that caused process to stop)
                    throw new \RuntimeException("LVM-data-fetching script stopped due to a signal (SIG={$procStatus['stopsig']})");
                }

                if (!$procStatus['running'] && $procStatus['exitcode'] > 0) {
                    // Process has stopped without a signal being involved, BUT it exited on a non-zero status, so it didn't complete successfully :(
                    throw new \RuntimeException("LVM-data-fetching script exited with a non-zero exit status :(");
                }

                // Process has finished without a signal, AND has exited with a 0 ("success") status code! :D
                // We do need to make sure we've finished reading everything from our pipes before we move away from here, but in theory, at least, our proc_get_status() call should indicate that the process is 'running' until the process has been able to write everything to its output streams...

                // So we just need to read whatever's available from our pipe streams, but in a nice and efficient manner :)
                $streamsToRead = [0=>$pipes[0], 2=>$pipes[2], 3=>$pipes[3], 4=>$pipes[4]];
                $streamsToWrite = null;
                $streamsToExcept = [0=>$pipes[0], 2=>$pipes[2], 3=>$pipes[3], 4=>$pipes[4]];

                $ssResult = stream_select($streamsToRead, $streamsToWrite, $streamsToExcept, static::$loadDataTimeoutShortSecs, 0);
                if ($ssResult === false) {
                    throw new \RuntimeException("LVM-data-fetching script wrapper exited unexpectedly: “{$php_errormsg}”");
                } elseif ($ssResult === 0) {
                    throw new \RuntimeException("LVM-data-fetching script took too long to load any data and timed out (the time-between-data-chunks timeout of " . static::$loadDataTimeoutShortSecs . " secs elapsed).");
                }



            } while (1);
        } finally {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($proc);
        }

        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // Any error output will be appended to /tmp/error-output.txt

            fwrite($pipes[0], '<?php print_r($_ENV); ?>');
            fclose($pipes[0]);

            echo stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);

            echo "command returned $return_value\n";
        }
    }
}
