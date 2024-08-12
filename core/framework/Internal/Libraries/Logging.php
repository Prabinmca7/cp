<?php
namespace RightNow\Internal\Libraries;

use RightNow\Internal\Utils\Logs;
require_once CPCORE . 'Internal/Utils/Logs.php';
/* A collection of logging classes and filters to support writing, reading and filtering log files */

/**
 * A simple logger for writing messages to a file.
 *
 * Log format: '[<logLevel>] <timestamp> <msg>
 *             where LogLevel is one of DEBUG|INFO|WARN|ERROR|FATAL
 *
 * A message will be suppressed if it's specified log level's integer value is
 * less then $logLevel set in the constructor's integer value.
 *
 * Example usage:
 *                $logger = new SimpleLogger('/tmp/foo.log', 'WARN');
 *                $log->debug("Here's some debug"); // suppressed
 *                $log->info("Here's some info");   // suppressed
 *                $log->warn("Here's a warning");   // written to log
 *                $log->error("Here's an error");   // written to log
 *                $log->fatal("Here fatal");        // written to log
 *
 *                Resulting log:
 *                    [WARN]  mm/dd/YYYY HH:MM:SS Here's a warning
 *                    [ERROR] mm/dd/YYYY HH:MM:SS Here's an error
 *                    [FATAL] mm/dd/YYYY HH:MM:SS Here fatal
 */
final class SimpleLogger{
    private $logger = null;
    private $logLevels = null;
    private $fileHandle = null;
    private $logPath = null;
    private $dateTimeFormat = null;
    private $logLevelInteger = null;
    private $logLevel = null;

    const LOGGING_HEADER_DELIMITER = '----------------------------------------------';

    /**
     * Creates new SimpleLogger instance
     * @param string $logPath Absolute path name to log to be created or appended to.
     * @param string $logLevel One of DEBUG|INFO|WARN|ERROR|FATAL.
     * @param string $dateTimeFormat A valid date/time format to be passed to strftime().
     * @throws \Exception If $logPath isn't in a valid directory
     */
    public function __construct($logPath, $logLevel = 'DEBUG', $dateTimeFormat = 'Y-m-d H:i A') {
        if (!is_dir(dirname($logPath))) {
            throw new \Exception('Invalid directory: ' . dirname($logPath));
        }
        $this->logPath = $logPath;
        $this->dateTimeFormat = $dateTimeFormat;
        $this->logLevels = Logs::getLogLevels();
        $this->setLogLevel($logLevel);
        $this->fileHandle = fopen($this->logPath, 'a');
    }

    public function __destruct() {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
    }

    public function debug($msg) {
        $this->log($msg, 'DEBUG');
    }

    public function info($msg) {
        $this->log($msg, 'INFO');
    }

    public function warn($msg) {
        $this->log($msg, 'WARN');
    }

    public function error($msg) {
        $this->log($msg, 'ERROR');
    }

    public function fatal($msg) {
        $this->log($msg, 'FATAL');
    }

    private function log($msg, $logLevel) {
        if ($this->logLevelIsEnabled($logLevel)) {
            $CI = get_instance();
            fwrite($this->fileHandle, sprintf("%-7s %s %s\n", "[$logLevel]", $CI->cpwrapper->cpPhpWrapper->strftime($this->dateTimeFormat, time()), $msg));
        }
    }

    private function setLogLevel($logLevel) {
        if (!Logs::logLevelIsValid($logLevel)) {
            throw new \Exception('Invalid logLevel: ' . $logLevel);
        }
        $this->logLevel = $logLevel;
        $this->logLevelInteger = $this->logLevels[$logLevel];
    }

    private function logLevelIsEnabled($logLevel) {
        return ($this->logLevels[$logLevel] >= $this->logLevelInteger) ? true : false;
    }
}

/**
 * Filter a log created by SimpleLogger class above.
 * Assumes each line of specified log is prepended by one of the recognized log levels
 * (DEBUG|INFO|WARN|ERROR|FATAL) within brackets (E.g. '[DEBUG] <timestamp> <msg>').
 * A line will be suppressed (not returned in results) if it starts with a defined log level
 * whose integer value is less then the specified $logLevel's integer value.
 *
 * For example, specifying a $logLevel of 'INFO' will suppress 'DEBUG' lines, and
 * display everything else (INFO, WARN, ERROR, FATAL).
 *
 * @param $logPathOrContents [mixed] - if a string is sent, it is assumed to be the absolute path name to log to be filtered,
 *                                     if an array is sent, the strings within will be filtered as the log contents.
 * @param $logLevel [string] - DEBUG|INFO|WARN|ERROR|FATAL.
 */
final class LogFilter{
    private $logPath = null;
    private $logLevelInteger = null;
    private $logContents = null;
    private $logLevelRegex = '/^\[([A-Z]+)\].+$/';
    private $logLevels = null;
    private $logLevel = null;

    /**
     * Creates a new LogFilter instance
     * @param array|string $logPathOrContents If a string is sent, it is assumed to be the absolute path name to log to be filtered,
     *                                        if an array is sent, the strings within will be filtered as the log contents.
     * @param string $logLevel One of DEBUG|INFO|WARN|ERROR|FATAL.
     * @throws \Exception If $logPathOrContents is invalid
     */
    public function __construct($logPathOrContents, $logLevel = 'INFO') {
        if (is_array($logPathOrContents)) {
            $logPath = '.';
            $this->logContents = $logPathOrContents;
        }
        else if (\RightNow\Utils\FileSystem::isReadableFile($logPathOrContents)) {
            $logPath = $logPathOrContents;
            $this->logContents = explode("\n", file_get_contents($logPathOrContents));
        }
        else {
            throw new \Exception("Invalid log path: '$logPathOrContents'");
        }
        $this->logLevels = Logs::getLogLevels();
        $this->setLogLevel($logLevel);
    }

    /**
     * Filters the log
     * @param string $logLevel One of DEBUG|INFO|WARN|ERROR|FATAL.
     * @return array Array of filtered log lines.
     */
    public function filter($logLevel = 'INFO') {
        if ($logLevel !== $this->logLevel) {
            $this->setLogLevel($logLevel);
        }

        $lines = array();
        foreach ($this->logContents as $line) {
            if (preg_match($this->logLevelRegex, $line, $matches) && $matches[1] !== null) {
                $thisLogLevel = $matches[1];
                if (Logs::logLevelIsValid($logLevel) && !$this->logLevelIsEnabled($thisLogLevel)) {
                    continue;
                }
                $line = preg_replace("/\[$thisLogLevel\]\s+/", '', $line);
                //write WARN in orange
                if ($this->logLevels[$thisLogLevel] === 2) {
                    $line = sprintf('<span class="warnLogEntry">%s</span>', $line);
                }
                //write ERROR and FATAL in red
                else if ($this->logLevels[$thisLogLevel] > 2) {
                    $line = sprintf('<span class="errorLogEntry">%s</span>', $line);
                }
            }
            array_push($lines, $line);
        }
        return $lines;
    }

    private function logLevelIsEnabled($logLevel) {
        return ($this->logLevels[$logLevel] >= $this->logLevelInteger) ? true : false;
    }

    private function setLogLevel($logLevel) {
        if (!Logs::logLevelIsValid($logLevel)) {
            throw new \Exception('Invalid logLevel: ' . $logLevel);
        }
        $this->logLevel = $logLevel;
        $this->logLevelInteger = $this->logLevels[$logLevel];
    }
}