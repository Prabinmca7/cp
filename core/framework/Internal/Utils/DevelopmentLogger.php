<?php

namespace RightNow\Internal\Utils;

use RightNow\Utils\Text as TextExternal;

if (IS_HOSTED) {
    exit("Did we ship the DevelopmentLogger class?  That would be sub-optimal.");
}

/**
 * DevelopmentLogger
 *
 * A class for logging to a file and a socket. Used by Framework::logMessage on non-hosted sites.
 */
final class DevelopmentLogger {
    /**
     * Set to false if you don't want to log to a file and only want this to attempt socket connections
     */
    private static $logToFile = true;

    // Date format for current time
    const DATEFORMAT = "Y-m-d G:i:s";

    // Default format, limited context:
    // e.g. "{$datetime} - {$file}:{$line} - {$message}"
    const DEFAULT_LINEFORMAT = "[%s] - %s:%s - %s";

    // Function format, more context:
    // e.g. "{$datetime} - {$file}:{$line} {$function} - {$message}";
    const FUNCTION_LINEFORMAT = "[%s] - %s:%s %s - %s";

    // Object format, most context:
    // e.g. "{$datetime} - {$file}:{$line} {$object/class}{$type}{$function} - {$message}";
    const OBJECT_LINEFORMAT = "[%s] - %s:%s %s%s%s - %s";

    // localhost for socket
    const HOST = '127.0.0.1';

    /**
     * Log the given message.
     *
     * @param string $message The message to write.
     */
    public static function log($message) {
        $message = self::messageBuilder($message);

        if (self::$logToFile) {
            $fileLocation = \RightNow\Api::cfg_path() . '/log/cp.log';
            file_put_contents($fileLocation, $message . "\n", FILE_APPEND);
        }

        // make an md5 of the site name and convert the first six hex characters to an integer
        // take the resulting int and compute a port number for this script
        // and extras/utils/logListener.py to agree on
        $port = (hexdec(substr(md5(basename(HTMLROOT)), 0, 6)) % 16383) + 49153;
        $socket = @fsockopen(self::HOST, $port);
        if ($socket) {
            fwrite($socket, $message);
            fclose($socket);
        }
    }

    /**
     * Create the message to be logged.
     *
     * @param string $message The message to write.
     */
    private static function messageBuilder($message) {
        $backtraceContext = array();
        $function = $object = $class = $type = '';
        $backtraceInfo = debug_backtrace(0);
        $data = '';

        foreach ($backtraceInfo as $currentBacktraceContext) {
            if($backtraceContext) {
                $function = isset($currentBacktraceContext['function']) ? $currentBacktraceContext['function'] : '';
                $object = isset($currentBacktraceContext['object']) ? $currentBacktraceContext['object'] : '';
                $class = isset($currentBacktraceContext['class']) ? $currentBacktraceContext['class'] : '';
                $type = isset($currentBacktraceContext['type']) ? $currentBacktraceContext['type'] : '';
                break;
            }
            if($currentBacktraceContext['class'] !== 'RightNow\Internal\Utils\DevelopmentLogger') {
                $backtraceContext = $currentBacktraceContext;
                continue;
            }
        }

        return self::printLine($message, $backtraceContext, $function, $object, $class, $type);
    }

    /**
     * Create the whole message.
     *
     * @param string $message The message to write.
     * @param array $backtraceContext The backtrace array with the correct file and line info.
     * @param string $function The function info from backtrace.
     * @param string $object The object info from backtrace.
     * @param string $class The class info from backtrace.
     * @param string $type The type info from backtrace.
     */
    private static function printLine($message, array $backtraceContext, $function, $object, $class, $type) {
        $datetime = date(self::DATEFORMAT);

        // Get file name; not full path
        $fileName = TextExternal::getSubstringAfter($backtraceContext['file'], 'scripts/cp/', $backtraceContext['file']);
        $fileName = TextExternal::getSubstringAfter($fileName, 'core/framework/', $fileName);
        $fileName = TextExternal::getSubstringAfter($fileName, 'core/widgets/standard/', $fileName);
        $fileName = TextExternal::getSubstringAfter($fileName, 'customer/development/', $fileName);

        $message = print_r($message, true);

        // Figure out the format, then populate it.
        // 'object' format first.
        if($class && $function && $type) {
            // Specify if object is a class or instance of class
            if($object)
                $class = "{$class}[Instance]";
            else
                $class = "{$class}[Class]";
            return sprintf(self::OBJECT_LINEFORMAT, $datetime, $fileName, $backtraceContext['line'], $class, $type, $function, $message);
        }
        else if ($function) {
            // 'function' format second.
            return sprintf(self::FUNCTION_LINEFORMAT, $datetime, $fileName, $backtraceContext['line'], $function, $message);
        } else {
            // 'default' format third.
            return sprintf(self::DEFAULT_LINEFORMAT, $datetime, $fileName, $backtraceContext['line'], $message);
        }
    }
}
