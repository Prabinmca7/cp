<?php
/**
 * system/CoreCodeIgniter.php
 * ------------------------------------------------------------------------
 * This file contains the core CodeIgniter files/classes combined into one
 * large file for performance improvements.  This file is included from within
 * the CP Initializer Script (cp/core/framework/init.php).
 *
 * This file contains the following files/classes (in order):
 *
 *           system/core/Common.php
 *           system/core/Hooks.php
 *           system/core/Config.php
 *           system/core/Router.php
 *           system/core/Output.php
 *           system/core/Input.php
 *           system/core/URI.php
 *           system/core/Loader.php
 *           system/core/Controller.php
 *           system/core/Exceptions.php
 *           system/core/Security.php
 *           system/core/Themes.php
 *           system/core/Rnow.php
 *           system/libraries/Parser.php
 *           system/libraries/User_agent.php
 *           system/core/CodeIgniter.php
 *
 * ------------------------------------------------------------------------*/


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Common Functions
 *
 * Loads the base classes and executes the request.
 *
 * @package CodeIgniter
 * @subpackage CodeIgniter
 * @category Common Functions
 * @author EllisLab Dev Team
 * @link https://codeigniter.com/userguide3/
 */

// ------------------------------------------------------------------------

if (! function_exists('is_php')) {

    /**
     * Determines if the current version of PHP is equal to or greater than the supplied value
     *
     * @param
     *            string
     * @return bool TRUE if the current version is $version or higher
     */
    function is_php($version)
    {
        static $_is_php;
        $version = (string) $version;

        if (! isset($_is_php[$version])) {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }
}

// ------------------------------------------------------------------------

if (! function_exists('load_class')) {

    /**
     * Class registry
     *
     * This function acts as a singleton. If the requested class does not
     * exist it is instantiated and set to a static variable. If it has
     * previously been instantiated the variable is returned.
     *
     * @param
     *            string the class name being requested
     * @param
     *            string the directory where the class should be found
     * @param
     *            mixed an optional argument to pass to the class constructor
     * @return object
     */
    function &load_class($class, $directory = 'libraries', $param = NULL)
    {
        static $_classes = array();
        if (stripos($class, EXT)!== false) {
            $class = substr($class, 0, -4);
        }

        // Does the class exist? If so, we're done...
        if (isset($_classes[$class])) {
            return $_classes[$class];
        }

        $name = FALSE;

        if (file_exists(BASEPATH . $directory . '/' . $class . EXT)) {
            $name = 'CI_' . $class;

            if (class_exists($name, FALSE) === FALSE) {
                require_once (BASEPATH . $directory . '/' . $class . EXT);
            }
        }

        // Is the request a class extension? If so we load it too
        /*
         * if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php'))
         * {
         * $name = config_item('subclass_prefix').$class;
         *
         * if (class_exists($name, FALSE) === FALSE)
         * {
         * require_once(APPPATH.$directory.'/'.$name.'.php');
         * }
         * }
         */

        // Did we find the class?
        if ($name === FALSE) {
            // Note: We use exit() rather than show_error() in order to avoid a
            // self-referencing loop with the Exceptions class
            echo 'Unable to locate the specified class: ' . $class . '.php';
            exit(5); // EXIT_UNK_CLASS
        }

        // Keep track of what we just loaded
        is_loaded($class);

        $_classes[$class] = isset($param) ? new $name($param) : new $name();

        return $_classes[$class];
    }
}

// --------------------------------------------------------------------

if (! function_exists('is_loaded')) {

    /**
     * Keeps track of which libraries have been loaded.
     * This function is
     * called by the load_class() function above
     *
     * @param
     *            string
     * @return array
     */
    function &is_loaded($class = '')
    {
        static $_is_loaded = array();

        if ($class !== '') {
            $_is_loaded[strtolower($class)] = $class;
        }

        return $_is_loaded;
    }
}

// ------------------------------------------------------------------------

if (! function_exists('get_config')) {
/**
 * Loads the main config.php file
 *
 * This function lets us grab the config file even if the Config class
 * hasn't been instantiated yet
 *
 * @param
 *            array
 * @return array
 */
    // vasanth commented as not required for CP
    /*
     * function &get_config(Array $replace = array())
     * {
     * static $config;
     *
     * if (empty($config))
     * {
     * $file_path = APPPATH.'config/config.php';
     * $found = FALSE;
     * if (file_exists($file_path))
     * {
     * $found = TRUE;
     * require($file_path);
     * }
     *
     * // Is the config file in the environment folder?
     * if (file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/config.php'))
     * {
     * require($file_path);
     * }
     * elseif ( ! $found)
     * {
     * set_status_header(503);
     * echo 'The configuration file does not exist.';
     * exit(3); // EXIT_CONFIG
     * }
     *
     * // Does the $config array exist in the file?
     * if ( ! isset($config) OR ! is_array($config))
     * {
     * set_status_header(503);
     * echo 'Your config file does not appear to be formatted correctly.';
     * exit(3); // EXIT_CONFIG
     * }
     * }
     *
     * // Are any values being dynamically added or replaced?
     * foreach ($replace as $key => $val)
     * {
     * $config[$key] = $val;
     * }
     *
     * return $config;
     * }
     */
}

// ------------------------------------------------------------------------

if (! function_exists('config_item')) {

    /**
     * Returns the specified config item
     *
     * @param
     *            string
     * @return mixed
     */
    function config_item($item)
    {
        static $_config;

        // vasanth commented as we dont need this for CP
        /*
         * if (empty($_config))
         * {
         * // references cannot be directly assigned to static variables, so we use an array
         * $_config[0] =& get_config();
         * }
         */

        return isset($_config[0][$item]) ? $_config[0][$item] : NULL;
    }
}

// ------------------------------------------------------------------------

if (! function_exists('get_mimes')) {
/**
 * Returns the MIME types array from config/mimes.php
 *
 * @return array
 */
    // vasanth commented as not required for CP
    /*
     * function &get_mimes()
     * {
     * static $_mimes;
     *
     * if (empty($_mimes))
     * {
     * $_mimes = file_exists(APPPATH.'config/mimes.php')
     * ? include(APPPATH.'config/mimes.php')
     * : array();
     *
     * if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/mimes.php'))
     * {
     * $_mimes = array_merge($_mimes, include(APPPATH.'config/'.ENVIRONMENT.'/mimes.php'));
     * }
     * }
     *
     * return $_mimes;
     * }
     */
}

// ------------------------------------------------------------------------

if (! function_exists('show_error')) {

    /**
     * Error Handler
     *
     * This function lets us invoke the exception class and
     * display errors using the standard error template located
     * in application/views/errors/error_general.php
     * This function will send the error page directly to the
     * browser and exit.
     *
     * @param
     *            string
     * @param
     *            int
     * @param
     *            string
     * @return void
     */
    function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
    {
        $status_code = abs($status_code);
        if ($status_code < 100) {
            $exit_status = $status_code + 9; // 9 is EXIT__AUTO_MIN
            $status_code = 500;
        } else {
            $exit_status = 1; // EXIT_ERROR
        }

        $_error = &load_class('Exceptions', 'core');
        echo $_error->show_error($heading, $message, 'error_general', $status_code);
        exit($exit_status);
    }
}

// ------------------------------------------------------------------------

if (! function_exists('show_404')) {

    /**
     * 404 Page Handler
     *
     * This function is similar to the show_error() function above
     * However, instead of the standard error template it displays
     * 404 errors.
     *
     * @param
     *            string
     * @param
     *            bool
     * @return void
     */
    function show_404($page = '', $log_error = TRUE)
    {
        // Admin controller requests to unfound resources
        // render the admin 404 page. If this method is called
        // programmatically from an admin controller
        // then simply output the 404 header.
        if (! IS_ADMIN) {
            // Attempt to load the old, deprecated error_404 page. If it
            // isn't there, then just send a 404 header and exit.
            $oldCI404Page = APPPATH . 'errors/error_404' . EXT;
            if (is_file($oldCI404Page) && is_readable($oldCI404Page)) {
                $error = &load_class('Exceptions');
                $error->show_404($page);
                exit();
            }
        }
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        exit(\RightNow\Utils\Config::getMessage(NUM_404_PAGE_NOT_FOUND_LBL) . str_repeat(' ', 512));
    }
}

// ------------------------------------------------------------------------

if (! function_exists('log_message')) {

    /**
     * Error Logging Interface
     *
     * We use this as a simple mechanism to access the logging
     * class and send messages to be logged.
     *
     * @param
     *            string the error level: 'error', 'debug' or 'info'
     * @param
     *            string the error message
     * @return void
     */
    function log_message($level, $message)
    {
        return;
    }
}

// ------------------------------------------------------------------------

if (! function_exists('set_status_header')) {

    /**
     * Set HTTP Status Header
     *
     * @param
     *            int the status code
     * @param
     *            string
     * @return void
     */
    function set_status_header($code = 200, $text = '')
    {
        // vasanth commented as not required for CP
        /*
         * if (is_cli())
         * {
         * return;
         * }
         *
         * if (empty($code) OR ! is_numeric($code))
         * {
         * show_error('Status codes must be numeric', 500);
         * }
         *
         * if (empty($text))
         * {
         * is_int($code) OR $code = (int) $code;
         * $stati = array(
         * 100 => 'Continue',
         * 101 => 'Switching Protocols',
         *
         * 200 => 'OK',
         * 201 => 'Created',
         * 202 => 'Accepted',
         * 203 => 'Non-Authoritative Information',
         * 204 => 'No Content',
         * 205 => 'Reset Content',
         * 206 => 'Partial Content',
         *
         * 300 => 'Multiple Choices',
         * 301 => 'Moved Permanently',
         * 302 => 'Found',
         * 303 => 'See Other',
         * 304 => 'Not Modified',
         * 305 => 'Use Proxy',
         * 307 => 'Temporary Redirect',
         *
         * 400 => 'Bad Request',
         * 401 => 'Unauthorized',
         * 402 => 'Payment Required',
         * 403 => 'Forbidden',
         * 404 => 'Not Found',
         * 405 => 'Method Not Allowed',
         * 406 => 'Not Acceptable',
         * 407 => 'Proxy Authentication Required',
         * 408 => 'Request Timeout',
         * 409 => 'Conflict',
         * 410 => 'Gone',
         * 411 => 'Length Required',
         * 412 => 'Precondition Failed',
         * 413 => 'Request Entity Too Large',
         * 414 => 'Request-URI Too Long',
         * 415 => 'Unsupported Media Type',
         * 416 => 'Requested Range Not Satisfiable',
         * 417 => 'Expectation Failed',
         * 422 => 'Unprocessable Entity',
         * 426 => 'Upgrade Required',
         * 428 => 'Precondition Required',
         * 429 => 'Too Many Requests',
         * 431 => 'Request Header Fields Too Large',
         *
         * 500 => 'Internal Server Error',
         * 501 => 'Not Implemented',
         * 502 => 'Bad Gateway',
         * 503 => 'Service Unavailable',
         * 504 => 'Gateway Timeout',
         * 505 => 'HTTP Version Not Supported',
         * 511 => 'Network Authentication Required',
         * );
         *
         * if (isset($stati[$code]))
         * {
         * $text = $stati[$code];
         * }
         * else
         * {
         * show_error('No status text available. Please check your status code number or supply your own message text.', 500);
         * }
         * }
         *
         * $server_protocol = (isset($_SERVER['SERVER_PROTOCOL']) && in_array($_SERVER['SERVER_PROTOCOL'], array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0'), TRUE))
         * ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
         * header($server_protocol.' '.$code.' '.$text, TRUE, $code);
         */
    }
}

// --------------------------------------------------------------------

if (! function_exists('_error_handler')) {

    /**
     * Error Handler
     *
     * This is the custom error handler that is declared at the (relative)
     * top of CodeIgniter.php. The main reason we use this is to permit
     * PHP errors to be logged in our own log files since the user may
     * not have access to server logs. Since this function effectively
     * intercepts PHP errors, however, we also need to display errors
     * based on the current error_reporting level.
     * We do that with the use of a PHP error template.
     *
     * @param int $severity
     * @param string $message
     * @param string $filepath
     * @param int $line
     * @return void
     */
    function _error_handler($severity, $message, $filepath, $line)
    {
        $is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

        if ($severity === E_STRICT || (IS_HOSTED && IS_OPTIMIZED)) {
            return;
        }

        if (($severity & error_reporting()) === $severity) {
            $error = load_class('Exceptions', 'core');
            $error->show_php_error($severity, $message, $filepath, $line);
        }
    }
}

// ------------------------------------------------------------------------

if (! function_exists('_exception_handler')) {

    /**
     * Exception Handler
     *
     * Sends uncaught exceptions to the logger and displays them
     * only if display_errors is On so that they don't show up in
     * production environments.
     *
     * @param Exception $exception
     * @return void
     */
    function _exception_handler($exception)
    {
        $_error = &load_class('Exceptions', 'core');
        $_error->log_exception('error', 'Exception: ' . $exception->getMessage(), $exception->getFile(), $exception->getLine());

        // Should we display the error?
        if (str_ireplace(array(
            'off',
            'none',
            'no',
            'false',
            'null'
        ), '', ini_get('display_errors'))) {
            $_error->show_exception($exception);
        }

        exit(1); // EXIT_ERROR
    }
}

// ------------------------------------------------------------------------

if (! function_exists('_shutdown_handler')) {

    /**
     * Shutdown Handler
     *
     * This is the shutdown handler that is declared at the top
     * of CodeIgniter.php. The main reason we use this is to simulate
     * a complete custom exception handler.
     *
     * E_STRICT is purposively neglected because such events may have
     * been caught. Duplication or none? None is preferred for now.
     *
     * @link http://insomanic.me.uk/post/229851073/php-trick-catching-fatal-errors-e-error-with-a
     * @return void
     */
    function _shutdown_handler()
    {
        $last_error = error_get_last();
        if (isset($last_error) && ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING))) {
            _error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
        }
    }
}

// --------------------------------------------------------------------

if (! function_exists('remove_invisible_characters')) {

    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     *
     * @param
     *            string
     * @param
     *            bool
     * @return string
     */
    function remove_invisible_characters($str, $url_encoded = TRUE)
    {
        $non_displayables = array();

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/i'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/i'; // url encoded 16-31
            $non_displayables[] = '/%7f/i'; // url encoded 127
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, - 1, $count);
        } while ($count);

        return $str;
    }
}

// ------------------------------------------------------------------------

if (! function_exists('html_escape')) {

    /**
     * Returns HTML escaped variable.
     *
     * @param mixed $var
     *            The input string or array of strings to be escaped.
     * @param bool $double_encode
     *            $double_encode set to FALSE prevents escaping twice.
     * @return mixed The escaped string or array of strings as a result.
     */
    function html_escape($var, $double_encode = TRUE)
    {
        if (empty($var)) {
            return $var;
        }

        if (is_array($var)) {
            foreach (array_keys($var) as $key) {
                $var[$key] = html_escape($var[$key], $double_encode);
            }

            return $var;
        }

        return htmlspecialchars($var, ENT_QUOTES, config_item('charset'), $double_encode);
    }
}

// ------------------------------------------------------------------------

if (! function_exists('_stringify_attributes')) {

    /**
     * Stringify attributes for use in HTML tags.
     *
     * Helper function used to convert a string, array, or object
     * of attributes to a string.
     *
     * @param
     *            mixed string, array, object
     * @param
     *            bool
     * @return string
     */
    function _stringify_attributes($attributes, $js = FALSE)
    {
        if (empty($attributes)) {
            return NULL;
        }

        if (is_string($attributes)) {
            return ' ' . $attributes;
        }

        $attributes = (array) $attributes;

        $atts = '';
        foreach ($attributes as $key => $val) {
            $atts .= ($js) ? $key . '=' . $val . ',' : ' ' . $key . '="' . $val . '"';
        }

        return rtrim($atts, ',');
    }
}



/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Hooks Class
 *
 * Provides a mechanism to extend the base system without hacking.
 *
 * @package CodeIgniter
 * @subpackage Libraries
 * @category Libraries
 * @author EllisLab Dev Team
 * @link https://codeigniter.com/userguide3/general/hooks.html
 */
class CI_Hooks
{

    /**
     * Determines whether hooks are enabled
     *
     * @var bool
     */
    public $enabled = FALSE;

    /**
     * List of all hooks set in config/hooks.php
     *
     * @var array
     */
    public $hooks = array(
        'post_controller_constructor' => array(
            array(
                'class' => 'RightNow\Hooks\CleanseData',
                'function' => 'cleanse',
                'filename' => 'CleanseData.php',
                'filepath' => 'Hooks'
            ),
            array(
                'class' => 'RightNow\Hooks\Clickstream',
                'function' => 'trackSession',
                'filename' => 'Clickstream.php',
                'filepath' => 'Hooks',
                'params' => 'normal'
            ),
            array(
                'class' => 'RightNow\\Hooks\\Acs',
                'function' => 'initialize',
                'filename' => 'Acs.php',
                'filepath' => 'Hooks'
            )
        ),
        'post_controller' => array(
            array(
                'class' => 'RightNow\Hooks\SqlMailCommit',
                'function' => 'commit',
                'filename' => 'SqlMailCommit.php',
                'filepath' => 'Hooks',
                'params' => true
            )
        )
    );

    /**
     * Array with class objects to use hooks methods
     *
     * @var array
     */
    protected $_objects = array();

    /**
     * In progress flag
     *
     * Determines whether hook is in progress, used to prevent infinte loops
     *
     * @var bool
     */
    protected $_in_progress = FALSE;

    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct()
    {
        $CFG = &load_class('Config', 'core');
        log_message('info', 'Hooks Class Initialized');

        // If hooks are not enabled in the config file
        // there is nothing else to do
        if ($CFG->item('enable_hooks') === FALSE) {
            return;
        }

        // Grab the "hooks" definition file.
        /*
         * Normally CodeIngiter would slurp in hooks.php here, but we're trying
         * to minimize the number of files included, so I stuck the 3 hooks we
         * define directly into this class above.
         * @include(CPCORE.'config/hooks'.EXT);
         * if (file_exists(APPPATH.'config/hooks.php'))
         * {
         * include(APPPATH.'config/hooks.php');
         * }
         *
         * if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/hooks.php'))
         * {
         * include(APPPATH.'config/'.ENVIRONMENT.'/hooks.php');
         * }
         *
         * // If there are no hooks, we're done.
         * if ( ! isset($hook) OR ! is_array($hook))
         * {
         * return;
         * }
         *
         * $this->hooks =& $hook;
         */
        $this->enabled = TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Call Hook
     *
     * Calls a particular hook. Called by CodeIgniter.php.
     *
     * @uses CI_Hooks::_run_hook()
     *      
     * @param string $which
     *            Hook name
     * @return bool TRUE on success or FALSE on failure
     */
    public function call_hook($which = '')
    {
        if (! $this->enabled or ! isset($this->hooks[$which])) {
            return FALSE;
        }

        if (is_array($this->hooks[$which]) && ! isset($this->hooks[$which]['function'])) {
            foreach ($this->hooks[$which] as $val) {
                $this->_run_hook($val);
            }
        } else {
            $this->_run_hook($this->hooks[$which]);
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Run Hook
     *
     * Runs a particular hook
     *
     * @param array $data
     *            Hook details
     * @return bool TRUE on success or FALSE on failure
     */
    public function _run_hook($data)
    {
        // Closures/lambda functions and array($object, 'method') callables
        if (is_callable($data)) {
            is_array($data) ? $data[0]->{$data[1]}() : $data();

            return TRUE;
        } elseif (! is_array($data)) {
            return FALSE;
        }

        // -----------------------------------
        // Safety - Prevents run-away loops
        // -----------------------------------

        // If the script being called happens to have the same
        // hook call within it a loop can happen
        if ($this->_in_progress === TRUE) {
            return;
        }

        // -----------------------------------
        // Set file path
        // -----------------------------------

        if (! isset($data['filepath'], $data['filename'])) {
            return FALSE;
        }

        $filepath = CPCORE . $data['filepath'] . '/' . $data['filename'];

        if (! file_exists($filepath)) {
            return FALSE;
        }

        // Determine and class and/or function names
        $class = empty($data['class']) ? FALSE : $data['class'];
        $function = empty($data['function']) ? FALSE : $data['function'];
        $params = isset($data['params']) ? $data['params'] : '';

        if (empty($function)) {
            return FALSE;
        }

        // Set the _in_progress flag
        $this->_in_progress = TRUE;

        // Call the requested class and/or function
        if ($class !== FALSE) {
            // The object is stored?
            if (isset($this->_objects[$class])) {
                if (method_exists($this->_objects[$class], $function)) {
                    $this->_objects[$class]->$function($params);
                } else {
                    return $this->_in_progress = FALSE;
                }
            } else {
                class_exists($class, FALSE) or require_once ($filepath);

                if (! class_exists($class, FALSE) or ! method_exists($class, $function)) {
                    return $this->_in_progress = FALSE;
                }

                // Store the object and execute the method
                $this->_objects[$class] = new $class();
                $this->_objects[$class]->$function($params);
            }
        } else {
            function_exists($function) or require_once ($filepath);

            if (! function_exists($function)) {
                return $this->_in_progress = FALSE;
            }

            $function($params);
        }

        $this->_in_progress = FALSE;
        return TRUE;
    }
}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Config Class
 *
 * This class contains functions that enable config files to be managed
 *
 * @package CodeIgniter
 * @subpackage Libraries
 * @category Libraries
 * @author EllisLab Dev Team
 * @link https://codeigniter.com/userguide3/libraries/config.html
 */
class CI_Config
{

    /**
     * List of all loaded config values
     *
     * @var array
     */
    public $config = array(
        /*
        |--------------------------------------------------------------------------
        | Index File
        |--------------------------------------------------------------------------
        | Typically this will be your index.php file, unless you've renamed it to
        | something else. If you are using mod_rewrite to remove the page set this
        | variable so that it is blank. This is set below in the constructor since
        | its value is set via an expression.
        */
        'index_page' => '',

        /*
        |--------------------------------------------------------------------------
        | URI PROTOCOL
        |--------------------------------------------------------------------------
        | This item determines which server global should be used to retrieve the
        | URI string.  The default setting of "AUTO" works for most servers.
        | If your links do not seem to work, try one of the other delicious flavors:
        |
        | 'AUTO'            Default - auto detects
        | 'PATH_INFO'        Uses the PATH_INFO
        | 'QUERY_STRING'    Uses the QUERY_STRING
        | 'REQUEST_URI'        Uses the REQUEST_URI
        | 'ORIG_PATH_INFO'    Uses the ORIG_PATH_INFO
        */
        'uri_protocol' => 'QUERY_STRING',

        /*
        |--------------------------------------------------------------------------
        | URL suffix
        |--------------------------------------------------------------------------
        | This option allows you to add a suffix to all URLs generated by CodeIgniter.
        | For more information please see the user guide:
        | http://www.codeigniter.com/user_guide/general/urls.html
        */
        'url_suffix' => '',

        /*
        |--------------------------------------------------------------------------
        | Default Language
        |--------------------------------------------------------------------------
        | This determines which set of language files should be used. Make sure
        | there is an available translation if you intend to use something other
        | than english.
        */
        'language' => 'english',

        /*
        |--------------------------------------------------------------------------
        | Default Character Set
        |--------------------------------------------------------------------------
        | This determines which character set is used by default in various methods
        | that require a character set to be provided.
        */
        'charset' => 'UTF-8',

        /*
        |--------------------------------------------------------------------------
        | Enable/Disable System Hooks
        |--------------------------------------------------------------------------
        | If you would like to use the "hooks" feature you must enable it by
        | setting this variable to TRUE (boolean).  See the user guide for details.
        */
        'enable_hooks' => true,

        /*
        |--------------------------------------------------------------------------
        | Class Extension Prefix
        |--------------------------------------------------------------------------
        | This item allows you to set the filename/classname prefix when extending
        | native libraries.  For more information please see the user guide:
        | http://www.codeigniter.com/user_guide/general/core_classes.html
        | http://www.codeigniter.com/user_guide/general/creating_libraries.html
        */
        'subclass_prefix' => '',

        /*
        |--------------------------------------------------------------------------
        | Allowed URL Characters
        |--------------------------------------------------------------------------
        | This lets you specify which characters are permitted within your URLs.
        | When someone tries to submit a URL with disallowed characters they will
        | get a warning message.
        | As a security measure you are STRONGLY encouraged to restrict URLs to
        | as few characters as possible.  By default only these are allowed: a-z 0-9~%.:_-
        | Leave blank to allow all characters -- but only if you are insane. <-- GOOD THING WE ARE!
        | DO NOT CHANGE THIS UNLESS YOU FULLY UNDERSTAND THE REPERCUSSIONS!! <-- GOOD THING WE DO!
        */
        'permitted_uri_chars' => '',

        /*
        |--------------------------------------------------------------------------
        | Enable Query Strings
        |--------------------------------------------------------------------------
        | By default CodeIgniter uses search-engine friendly segment based URLs:
        | www.your-site.com/who/what/where/
        | You can optionally enable standard query string based URLs:
        | www.your-site.com?who=me&what=something&where=here
        | Options are: TRUE or FALSE (boolean)
        | The two other items let you set the query string "words" that will
        | invoke your controllers and its functions:
        | www.your-site.com/index.php?c=controller&m=function
        | Please note that some of the helpers won't work as expected when
        | this feature is enabled, since CodeIgniter is designed primarily to
        | use segment based URLs.
        */
        'enable_query_strings' => false,
        'controller_trigger' => 'c',
        'function_trigger' => 'm',

        /*
        |--------------------------------------------------------------------------
        | Error Logging Threshold
        |--------------------------------------------------------------------------
        | If you have enabled error logging, you can set an error threshold to
        | determine what gets logged. Threshold options are:
        | You can enable error logging by setting a threshold over zero. The
        | threshold determines what gets logged. Threshold options are:
        |
        |    0 = Disables logging, Error logging TURNED OFF
        |    1 = Error Messages (including PHP errors)
        |    2 = Debug Messages
        |    3 = Informational Messages
        |    4 = All Messages
        |
        | For a live site you'll usually only enable Errors (1) to be logged otherwise
        | your log files will fill up very fast.
        */
        'log_threshold' => 0,

        /*
        |--------------------------------------------------------------------------
        | Error Logging Directory Path
        |--------------------------------------------------------------------------
        | Leave this BLANK unless you would like to set something other than the default
        | system/logs/ folder.  Use a full server path with trailing slash.
        */
        'log_path' => '',

        /*
        |--------------------------------------------------------------------------
        | Date Format for Logs
        |--------------------------------------------------------------------------
        | Each item that is logged has an associated date. You can use PHP date
        | codes to set your own date formatting
        */
        'log_date_format' => 'Y-m-d H:i:s',

        /*
        |--------------------------------------------------------------------------
        | Cache Directory Path
        |--------------------------------------------------------------------------
        | Leave this BLANK unless you would like to set something other than the default
        | system/cache/ folder.  Use a full server path with trailing slash.
        */
        'cache_path' => '',
	    
        /*
        |--------------------------------------------------------------------------
        | Cache Include Query String
        |--------------------------------------------------------------------------
        |
        | Whether to take the URL query string into consideration when generating
        | output cache files. Valid options are:
        |
        |	FALSE      = Disabled
        |	TRUE       = Enabled, take all query parameters into account.
        |	             Please be aware that this may result in numerous cache
        |	             files generated for the same page over and over again.
        |	array('q') = Enabled, but only take into account the specified list
        |	             of query parameters.
        |
        */
        'cache_query_string' => FALSE,

        /*
        |--------------------------------------------------------------------------
        | Encryption Key
        |--------------------------------------------------------------------------
        | If you use the Encryption class or the Sessions class with encryption
        | enabled you MUST set an encryption key.  See the user guide for info.
        */
        'encryption_key' => '',

         /*
        |--------------------------------------------------------------------------
        | Session Variables
        |--------------------------------------------------------------------------
        | 'session_cookie_name' = the name you want for the cookie
        | 'encrypt_sess_cookie' = TRUE/FALSE (boolean).  Whether to encrypt the cookie
        | 'session_expiration'  = the number of SECONDS you want the session to last.
        |  by default sessions last 7200 seconds (two hours).  Set to zero for no expiration.
        */
        'sess_cookie_name' => 'ci_session',
        'sess_expiration' => 86400,
        'sess_encrypt_cookie' => true,
        'sess_use_database' => false,
        'sess_table_name' => '',
        'sess_match_ip' => false,
        'sess_match_useragent' => true,

        /*
        |--------------------------------------------------------------------------
        | Cookie Related Variables
        |--------------------------------------------------------------------------
        | 'cookie_prefix' = Set a prefix if you need to avoid collisions
        | 'cookie_domain' = Set to .your-domain.com for site-wide cookies
        | 'cookie_path'   =  Typically will be a forward slash
        */
        'cookie_prefix' => '',
        'cookie_domain' => '',
        'cookie_path' => '/',

        /*
        |--------------------------------------------------------------------------
        | Global XSS Filtering
        |--------------------------------------------------------------------------
        | Determines whether the XSS filter is always active when GET, POST or
        | COOKIE data is encountered
        */
        'global_xss_filtering' => false,

        /*
        |--------------------------------------------------------------------------
        | Output Compression
        |--------------------------------------------------------------------------
        | Enables Gzip output compression for faster page loads.  When enabled,
        | the output class will test whether your server supports Gzip.
        | Even if it does, however, not all browsers support compression
        | so enable only if you are reasonably sure your visitors can handle it.
        | VERY IMPORTANT:  If you are getting a blank page when compression is enabled it
        | means you are prematurely outputting something to your browser. It could
        | even be a line of whitespace at the end of one of your scripts.  For
        | compression to work, nothing can be sent before the output buffer is called
        | by the output class.  Do not "echo" any values with compression enabled.
        */
        'compress_output' => false,

        /*
        |--------------------------------------------------------------------------
        | Master Time Reference
        |--------------------------------------------------------------------------
        | Options are "local" or "gmt".  This pref tells the system whether to use
        | your server's local time as the master "now" reference, or convert it to
        | GMT.  See the "date helper" page of the user guide for information
        | regarding date handling.
        */
        'time_reference' => 'local',

        /*
        |--------------------------------------------------------------------------
        | Rewrite PHP Short Tags
        |--------------------------------------------------------------------------
        | If your PHP installation does not have short tag support enabled CI
        | can rewrite the tags on-the-fly, enabling you to utilize that syntax
        | in your view files.  Options are TRUE or FALSE (boolean)
        */
        'rewrite_short_tags' => false,

    	/*
    	|--------------------------------------------------------------------------
    	| Reverse Proxy IPs
    	|--------------------------------------------------------------------------
    	|
    	| If your server is behind a reverse proxy, you must whitelist the proxy IP
    	| addresses from which CodeIgniter should trust the HTTP_X_FORWARDED_FOR
    	| header in order to properly identify the visitor's IP address.
    	| Comma-delimited, e.g. '10.0.1.200,10.0.1.201'
    	|
    	*/
	    'proxy_ips' => '',

        /**
         * ***RNT CONFIG SECTION****
         */

        /*
        |--------------------------------------------------------------------------
        | suffix
        |--------------------------------------------------------------------------
        | The suffix setting holds a counter for an entire page. The counter
        | is used as a suffix to all ID's for that widget html. This allows multiple
        | instances of the same widget to be placed on a page. The counter is also used
        | for tab indexes. This value is incremented every time a widget is placed on
        | the page.
        */
        'w_id' => 0,

        /*
        |--------------------------------------------------------------------------
        | Parameter Segment Location
        |--------------------------------------------------------------------------
        | This number denotes which segment is the start of parameters in the page. This
        | value is set up in the page controller and will be used by widgets to know where
        | the parameters of a page begin. Default is 3.
        */
        'parm_segment' => 3,

        /*
        |--------------------------------------------------------------------------
        | Widget Instance IDs
        |--------------------------------------------------------------------------
        | Array to keep track of all defined values for the instanceID widget attributes. Duplicate
        | values are not allowed so we store the values during runtime in order to throw an error
        | if two widgets contain the same value.
        */
        'widgetInstanceIDs' => array()
    );

    /**
     * List of all loaded config files
     *
     * @var array
     */
    public $is_loaded = array();

    /**
     * List of paths to search when trying to load a config file.
     *
     * @used-by	CI_Loader
     * @var array
     */
    public $_config_paths = array(
        CPCORE
    );

    // --------------------------------------------------------------------

    /**
     * Class constructor
     *
     * Sets the $config data from the primary config.php file as a class variable.
     *
     * @return void
     */
    public function __construct()
    {
        /*
         * Ernie: Since we've already started editing CI source code, theres no need to have the
         * config details in another file so I've set them above except for index_page, which requires
         * the use of an expression to set.
         * $this->config =& get_config();
         */
        $this->config['index_page'] = SELF . '?';
        log_message('debug', "Config Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Load Config File
     *
     * @param string $file
     *            Configuration file name
     * @param bool $use_sections
     *            Whether configuration values should be loaded into their own section
     * @param bool $fail_gracefully
     *            Whether to just return FALSE or display an error message
     * @return bool TRUE if the file was loaded correctly or FALSE on failure
     */
    public function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE)
    {
        $file = ($file == '') ? 'config' : str_replace(EXT, '', $file);
        $loaded = FALSE;

        if (in_array($file, $this->is_loaded, TRUE)) {
            $loaded = TRUE;
            return TRUE;
        }

        if (! is_readable(CPCORE . 'config/' . $file . EXT)) {
            if ($fail_gracefully === TRUE) {
                return FALSE;
            }
            show_error('The configuration file ' . $file . EXT . ' does not exist.');
        }

        include (CPCORE . 'config/' . $file . EXT);

        if (! isset($config) or ! is_array($config)) {
            if ($fail_gracefully === TRUE) {
                return FALSE;
            }
            show_error('Your ' . $file . EXT . ' file does not appear to contain a valid configuration array.');
        }

        if ($use_sections === TRUE) {
            if (isset($this->config[$file])) {
                $this->config[$file] = array_merge($this->config[$file], $config);
            } else {
                $this->config[$file] = $config;
            }
        } else {
            $this->config = array_merge($this->config, $config);
        }

        $this->is_loaded[] = $file;
        $config = NULL;
        $loaded = TRUE;
        log_message('debug', 'Config file loaded: config/' . $file . EXT);
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch a config file item
     *
     * @param string $item
     *            Config item name
     * @param string $index
     *            Index name
     * @return string|null The configuration item or NULL if the item doesn't exist
     */
    public function item($item, $index = '')
    {
        if ($index == '') {
            return isset($this->config[$item]) ? $this->config[$item] : NULL;
        }

        return isset($this->config[$index], $this->config[$index][$item]) ? $this->config[$index][$item] : NULL;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch a config file item with slash appended (if not empty)
     *
     * @param string $item
     *            Config item name
     * @return string|null The configuration item or NULL if the item doesn't exist
     */
    public function slash_item($item)
    {
        if (! isset($this->config[$item])) {
            return NULL;
        } elseif (trim($this->config[$item]) === '') {
            return '';
        }

        return rtrim($this->config[$item], '/') . '/';
    }

    // --------------------------------------------------------------------

    /**
     * Site URL
     *
     * Returns base_url . index_page [. uri_string]
     *
     * @uses CI_Config::_uri_string()
     *      
     * @param string|string[] $uri
     *            URI string or an array of segments
     * @param string $protocol
     * @return string
     */
    public function site_url($uri = '', $protocol = NULL)
    {
        $base_url = $this->slash_item('base_url');

        if (isset($protocol)) {
            // For protocol-relative links
            if ($protocol === '') {
                $base_url = substr($base_url, strpos($base_url, '//'));
            } else {
                $base_url = $protocol . substr($base_url, strpos($base_url, '://'));
            }
        }

        if (empty($uri)) {
            return $base_url . $this->item('index_page');
        }

        $uri = $this->_uri_string($uri);

        if ($this->item('enable_query_strings') === FALSE) {
            $suffix = isset($this->config['url_suffix']) ? $this->config['url_suffix'] : '';

            if ($suffix !== '') {
                if (($offset = strpos($uri, '?')) !== FALSE) {
                    $uri = substr($uri, 0, $offset) . $suffix . substr($uri, $offset);
                } else {
                    $uri .= $suffix;
                }
            }

            return $base_url . $this->slash_item('index_page') . $uri;
        } elseif (strpos($uri, '?') === FALSE) {
            $uri = '?' . $uri;
        }

        return $base_url . $this->item('index_page') . $uri;
    }

    // -------------------------------------------------------------

    /**
     * Base URL
     *
     * Returns base_url [. uri_string]
     *
     * @uses CI_Config::_uri_string()
     *      
     * @param string|string[] $uri
     *            URI string or an array of segments
     * @param string $protocol
     * @return string
     */
    public function base_url($uri = '', $protocol = NULL)
    {
        $base_url = $this->slash_item('base_url');

        if (isset($protocol)) {
            // For protocol-relative links
            if ($protocol === '') {
                $base_url = substr($base_url, strpos($base_url, '//'));
            } else {
                $base_url = $protocol . substr($base_url, strpos($base_url, '://'));
            }
        }

        return $base_url . $this->_uri_string($uri);
    }

    // -------------------------------------------------------------

    /**
     * Build URI string
     *
     * @used-by	CI_Config::site_url()
     * @used-by	CI_Config::base_url()
     *
     * @param string|string[] $uri
     *            URI string or an array of segments
     * @return string
     */
    protected function _uri_string($uri)
    {
        if ($this->item('enable_query_strings') === FALSE) {
            is_array($uri) && $uri = implode('/', $uri);
            return ltrim($uri, '/');
        } elseif (is_array($uri)) {
            return http_build_query($uri);
        }

        return $uri;
    }

    // --------------------------------------------------------------------

    /**
     * System URL
     *
     * @deprecated 3.0.0 Encourages insecure practices
     * @return string
     */
    public function system_url()
    {
        $x = explode('/', preg_replace('|/*(.+?)/*$|', '\\1', BASEPATH));
        return $this->slash_item('base_url') . end($x) . '/';
    }

    // --------------------------------------------------------------------

    /**
     * Set a config file item
     *
     * @param string $item
     *            Config item key
     * @param string $value
     *            Config item value
     * @return void
     */
    public function set_item($item, $value)
    {
        $this->config[$item] = $value;
    }
}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Router Class
 *
 * Parses URIs and determines routing
 *
 * @package CodeIgniter
 * @subpackage Libraries
 * @category Libraries
 * @author EllisLab Dev Team
 * @link https://codeigniter.com/userguide3/general/routing.html
 */
class CI_Router
{

    /**
     * CI_Config class object
     *
     * @var object
     */
    public $config;

    /**
     * CI_URI class object
     *
     * @var object
     */
    public $uri;

    /**
     * List of routes
     *
     * @var array
     */
    public $routes = array();

    /**
     * Current class name
     *
     * @var string
     */
    public $class = '';

    /**
     * Current method name
     *
     * @var string
     */
    public $method = 'index';

    /**
     * Sub-directory that contains the requested controller class
     *
     * @var string
     */
    public $directory = '';

    /**
     * Default controller (and method if specific)
     *
     * @var string
     */
    public $default_controller;

    /**
     * Translate URI dashes
     *
     * Determines whether dashes in controller & method segments
     * should be automatically replaced by underscores.
     *
     * @var bool
     */
    public $translate_uri_dashes = FALSE;

    /**
     * Enable query strings flag
     *
     * Determines whether to use GET parameters or segment URIs
     *
     * @var bool
     */
    public $enable_query_strings = FALSE;

    // --------------------------------------------------------------------
    // CP specific route variables
    // --------------------------------------------------------------------
    public $uri_string = '';

    public $segments = array();

    public $rsegments = array();

    public $foundControllerInCpCore = true;

    private $fullPath = '';

    /**
     * Class constructor
     *
     * Runs the route mapping function.
     *
     * @param array $routing
     * @return void
     */
    public function __construct($routing = NULL)
    {
        $this->config = &load_class('Config', 'core');
        $this->uri = &load_class('URI', 'core');

        $this->enable_query_strings = ($this->config->item('enable_query_strings') === TRUE);
        $this->_set_route_mapping();
        $this->setUriData();

        log_message('info', 'Router Class Initialized');
    }

    // --------------------------------------------------------------------
    // CP function for routing
    // --------------------------------------------------------------------

    /**
     * Set the route mapping
     *
     * This function determines what should be served based on the URI request,
     * as well as any "routes" that have been set in the routing config file.
     *
     * @access private
     * @return void
     */
    private function _set_route_mapping()
    {
        $this->default_controller = 'page';

        // Fetch the complete URI string CP function
        $this->uri_string = $this->_get_uri_string();

        // If the URI contains only a slash we'll kill it
        if ($this->uri_string === '/') {
            $this->uri_string = '';
        }

        // Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
        if ($this->uri_string === '') {
            $this->set_class($this->default_controller);
            $this->set_method('index');

            log_message('debug', "No URI present. Default controller set.");
            return;
        }

        // Do we need to remove the suffix specified in the config file?
        if ($this->config->item('url_suffix') !== "") {
            $this->uri_string = preg_replace("|" . preg_quote($this->config->item('url_suffix')) . "$|", "", $this->uri_string);
        }

        // Explode the URI Segments. The individual segments will
        // be stored in the $this->segments array.
        foreach (explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->uri_string)) as $val) {
            // Filter segments for security taken from URI
            trim($this->uri->filter_uri($val) ? $this->uri->filter_uri($val) : '');

            if ($val !== '')
                $this->segments[] = $val;
        }

        // Parse any custom routing that may exist
        $this->_parse_routes();

        // Re-index the segment array so that it starts with 1 rather than 0 taken from URI
        $this->_reindex_segments();
    }

    // --------------------------------------------------------------------
    // CP function getting the query string set in CP framework init.php
    // --------------------------------------------------------------------

    /**
     * Get the URI String
     *
     * @access private
     * @return string
     */
    private function _get_uri_string()
    {
        return (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
    }

    // --------------------------------------------------------------------
    // CP function
    // --------------------------------------------------------------------
    /**
     * Re-index Segments
     *
     * This function re-indexes the $this->segment array so that it
     * starts at 1 rather then 0. Doing so makes it simpler to
     * use functions like $this->uri->segment(n) since there is
     * a 1:1 relationship between the segment array and the actual segments.
     *
     * @access public
     * @return void
     */
    public function _reindex_segments()
    {
        // Is the routed segment array different then the main segment array?
        $diff = (count(array_diff($this->rsegments, $this->segments)) == 0) ? FALSE : TRUE;

        $i = 1;
        foreach ($this->segments as $val) {
            $this->segments[$i ++] = $val;
        }
        unset($this->segments[0]);

        if ($diff == FALSE) {
            $this->rsegments = $this->segments;
        } else {
            $i = 1;
            foreach ($this->rsegments as $val) {
                $this->rsegments[$i ++] = $val;
            }
            unset($this->rsegments[0]);
        }
    }

    // --------------------------------------------------------------------
    // CP function
    // --------------------------------------------------------------------
    /**
     * Compile Segments
     *
     * This function takes an array of URI segments as
     * input, and puts it into the $this->segments array.
     * It also sets the current class/method
     *
     * @access private
     * @param
     *            array
     * @param
     *            bool
     * @return void
     */
    private function _compile_segments($segments = array())
    {
        $segments = $this->_preventAgentSessionIdFromBeingTheMethod($this->_validate_segments($segments));
        if (count($segments) == 0) {
            return;
        }

        $this->set_class($segments[0]);

        if (isset($segments[1])) {
            $this->set_method($segments[1]);
        }

        // Update our "routed" segment array to contain the segments.
        // Note: If there is no custom routing, this array will be
        // identical to $this->segments
        $this->rsegments = $segments;
    }

    // --------------------------------------------------------------------
    // CP function
    // --------------------------------------------------------------------

    /**
     * Hotswap out variables to load the 404 page.
     * This allows us to keep the URL
     * the same as what the user typed in in addition to loading a normal page which
     * can render rn: tags. We are using the %error404% placeholder here because at
     * the point this executes, we haven't opened the config bases yet. So instead
     * we're denoting a placeholder which will be replaced with the config value within
     * the page controller.
     *
     * @access public
     * @return Array The modified segment array
     */
    public function setVariablesFor404Page()
    {
        $currentUriSegments = explode('/', $this->uri_string);
        if (IS_ADMIN) {
            $this->fullPath = CPCORE . 'Controllers/Admin/Overview.php';
            $this->uri_string = 'overview/admin404';
        } else {
            $this->fullPath = CPCORE . 'Controllers/Page.php';
            $this->uri_string = "page/render/%error404%";
        }
        $this->directory = '';
        $this->segments = $this->rsegments = explode('/', $this->uri_string);
        // Since we're swapping in the page controller, we need to denote that we're going to a
        // controller in the CPCORE directory.
        $this->foundControllerInCpCore = true;
        // Even though we're going to render the 404 page, we still want to persist the session
        // parameter if it exists in the URL. That way we don't create a new session for non-cookied
        // users when hitting the 404 page. There might be other URL parameters specified for the page
        // they attempted to access, but we're not going to persist those through since we don't know what they are.
        $sessionParameterSegment = array_search('session', $currentUriSegments, true);
        $sessionValue = null;
        // If the session parameter was found, grab the value in the next segment
        if ($sessionParameterSegment !== false)
            $sessionValue = $currentUriSegments[$sessionParameterSegment + 1];
        if ($sessionValue)
            array_push($this->segments, 'session', $sessionValue);
        return $this->segments;
    }

    // --------------------------------------------------------------------
    // CP function
    // --------------------------------------------------------------------
    /**
     * If the method segment of the URI was going to be "session_id" then kill it and the next segment
     * since those are really authenitcation parameters being passed in.
     *
     * This change to the segments means that CI's URI segments are going to be different
     * from the value in REQUEST_URI. Then again, they were already different in some cases,
     * so it's no big deal.
     *
     * @access private
     * @return array
     */
    private function _preventAgentSessionIdFromBeingTheMethod($segments)
    {
        if (count($segments) >= 3 && $segments[1] === \RightNow\Controllers\Base::agentSessionIdKey) {
            array_splice($segments, 1, 2);
        }
        return $segments;
    }

    // --------------------------------------------------------------------
    // CP function
    // --------------------------------------------------------------------

    /**
     * Parse the REQUEST_URI
     *
     * Due to the way REQUEST_URI works it usually contains path info
     * that makes it unusable as URI data. We'll trim off the unnecessary
     * data, hopefully arriving at a valid URI that we can use.
     *
     * @access private
     * @return string
     */
    private function _parse_request_uri()
    {
        if (! isset($_SERVER['REQUEST_URI']) or $_SERVER['REQUEST_URI'] == '') {
            return '';
        }

        $request_uri = preg_replace("|/(.*)|", "\\1", str_replace("\\", "/", $_SERVER['REQUEST_URI']));

        if ($request_uri == '' or $request_uri == SELF) {
            return '';
        }

        $fc_path = FCPATH;
        if (strpos($request_uri, '?') !== FALSE) {
            $fc_path .= '?';
        }

        $parsed_uri = explode("/", $request_uri);

        $i = 0;
        foreach (explode("/", $fc_path) as $segment) {
            if (isset($parsed_uri[$i]) and $segment == $parsed_uri[$i]) {
                $i ++;
            }
        }

        $parsed_uri = implode("/", array_slice($parsed_uri, $i));

        if ($parsed_uri != '') {
            $parsed_uri = '/' . $parsed_uri;
        }

        return $parsed_uri;
    }

    // --------------------------------------------------------------------
    // CP function
    // --------------------------------------------------------------------
    /**
     * Validates the supplied segments.
     * Attempts to determine the path to
     * the controller.
     *
     * @access private
     * @param
     *            array
     * @return array
     */
    function _validate_segments($segments)
    {
        if (! CUSTOM_CONTROLLER_REQUEST) {
            $possibleLocations = array(
                CPCORE . 'Controllers/' . ucfirst($segments[0]),
                CORE_FILES . 'compatibility/Controllers/' . ucfirst($segments[0])
            );
            foreach ($possibleLocations as $controllerPath) {
                // First check if the controller exists as specified, then check if it's in a sub directory.
                if (is_readable($controllerPath . EXT)) {
                    $this->fullPath = $controllerPath . EXT;
                    return $segments;
                }
                if (is_dir($controllerPath)) {
                    $this->set_directory(ucfirst($segments[0]));
                    $directorySegments = array_slice($segments, 1);
                    $expectedPath = "$controllerPath/" . ucfirst($directorySegments[0]) . EXT;
                    if (count($directorySegments) === 0 || ! is_readable($expectedPath)) {
                        continue;
                    }
                    $this->fullPath = $expectedPath;
                    return $directorySegments;
                }
            }
            return $this->setVariablesFor404Page();
        }
        $this->foundControllerInCpCore = false;
        $customControllerBasePath = APPPATH . 'controllers/';
        if (is_readable($customControllerBasePath . $segments[0] . EXT)) {
            $this->fullPath = $customControllerBasePath . $segments[0] . EXT;
            return $segments;
        }
        if (is_readable($customControllerBasePath . ucfirst($segments[0]) . EXT)) {
            $this->fullPath = $customControllerBasePath . ucfirst($segments[0]) . EXT;
            return $segments;
        }

        // Is the controller in a sub-folder?
        if (is_dir($customControllerBasePath . $segments[0])) {
            // Set the directory and remove it from the segment array
            $this->set_directory($segments[0]);
            array_shift($segments);

            // Does the requested controller exist in the sub-folder?
            if (count($segments) > 0) {
                $expectedPathBasePath = $customControllerBasePath . $this->fetch_directory();
                if (is_readable($expectedPathBasePath . $segments[0] . EXT)) {
                    $this->fullPath = $expectedPathBasePath . $segments[0] . EXT;
                    return $segments;
                }
                if (is_readable($expectedPathBasePath . ucfirst($segments[0]) . EXT)) {
                    $this->fullPath = $expectedPathBasePath . ucfirst($segments[0]) . EXT;
                    return $segments;
                }
            }
        }
        // Can't find the requested controller...
        return $this->setVariablesFor404Page();
    }

    // --------------------------------------------------------------------

    /**
     * Set request route
     *
     * Takes an array of URI segments as input and sets the class/method
     * to be called.
     *
     * @used-by	CI_Router::_parse_routes()
     * @param array $segments
     *            URI segments
     * @return void
     */
    protected function _set_request($segments = array())
    {
        $segments = $this->_validate_request($segments);
        // If we don't have any segments left - try the default controller;
        // WARNING: Directories get shifted out of the segments array!
        if (empty($segments)) {
            $this->_set_default_controller();
            return;
        }

        if ($this->translate_uri_dashes === TRUE) {
            $segments[0] = str_replace('-', '_', $segments[0]);
            if (isset($segments[1])) {
                $segments[1] = str_replace('-', '_', $segments[1]);
            }
        }

        $this->set_class($segments[0]);
        if (isset($segments[1])) {
            $this->set_method($segments[1]);
        } else {
            $segments[1] = 'index';
        }

        array_unshift($segments, NULL);
        unset($segments[0]);
        $this->uri->rsegments = $segments;
    }

    // --------------------------------------------------------------------

    /**
     * Set default controller
     *
     * @return void
     */
    protected function _set_default_controller()
    {
        if (empty($this->default_controller)) {
            show_error('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
        }

        // Is the method being specified?
        if (sscanf($this->default_controller, '%[^/]/%s', $class, $method) !== 2) {
            $method = 'index';
        }

        if (! file_exists(APPPATH . 'controllers/' . $this->directory . ucfirst($class) . '.php')) {
            // This will trigger 404 later
            return;
        }

        $this->set_class($class);
        $this->set_method($method);

        // Assign routed segments, index starting from 1
        $this->uri->rsegments = array(
            1 => $class,
            2 => $method
        );

        log_message('debug', 'No URI present. Default controller set.');
    }

    // --------------------------------------------------------------------

    /**
     * Validate request
     *
     * Attempts validate the URI request and determine the controller path.
     *
     * @used-by	CI_Router::_set_request()
     * @param array $segments
     *            URI segments
     * @return mixed URI segments
     */
    protected function _validate_request($segments)
    {
        $c = count($segments);
        $directory_override = isset($this->directory);

        // Loop through our segments and return as soon as a controller
        // is found or when such a directory doesn't exist
        while ($c -- > 0) {
            $test = $this->directory . ucfirst($this->translate_uri_dashes === TRUE ? str_replace('-', '_', $segments[0]) : $segments[0]);

            if (! file_exists(APPPATH . 'controllers/' . $test . '.php') && $directory_override === FALSE && is_dir(APPPATH . 'controllers/' . $this->directory . $segments[0])) {
                $this->set_directory(array_shift($segments), TRUE);
                continue;
            }

            return $segments;
        }

        // This means that all segments were actually directories
        return $segments;
    }

    // --------------------------------------------------------------------

    /**
     * Parse Routes
     *
     * Matches any routes that may exist in the config/routes.php file
     * against the URI to determine if the class/method need to be remapped.
     *
     * @return void
     */
    protected function _parse_routes()
    {
        $this->_compile_segments($this->segments);
        
    }

    // --------------------------------------------------------------------
    // CP function getting controller full path
    // --------------------------------------------------------------------
    /**
     * Get the URI String
     *
     * @access public
     * @return string
     */
    public function fetchFullControllerPath()
    {
        return $this->fullPath;
    }

    // --------------------------------------------------------------------
    // CP function to set URI object properties in CI 3.
    // --------------------------------------------------------------------
    /**
     * Setting CP has most of the URI properties into Router.
     * With CI 3 we need to set these URI property
     * so that we can access as $CI->uri->segment_array() and other URI methods and properties
     *
     * @access public
     * @return void
     */
    public function setUriData()
    {
        $this->uri->uri_string = $this->uri_string;
        $this->uri->segments = $this->segments;
        $this->uri->rsegments = $this->rsegments;
    }

    // --------------------------------------------------------------------

    /**
     * Set class name
     *
     * @param string $class
     *            Class name
     * @return void
     */
    public function set_class($class)
    {
        $this->class = str_replace(array(
            '/',
            '.'
        ), '', $class);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the current class
     *
     * @deprecated 3.0.0 Read the 'class' property instead
     * @return string
     */
    public function fetch_class()
    {
        return $this->class;
    }

    // --------------------------------------------------------------------

    /**
     * Set method name
     *
     * @param string $method
     *            Method name
     * @return void
     */
    public function set_method($method)
    {
        $this->method = $method;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the current method
     *
     * @deprecated 3.0.0 Read the 'method' property instead
     * @return string
     */
    public function fetch_method()
    {
        return $this->method;
    }

    // --------------------------------------------------------------------

    /**
     * Set directory name
     *
     * @param string $dir
     *            Directory name
     * @param bool $append
     *            Whether we're appending rather than setting the full value
     * @return void
     */
    public function set_directory($dir, $append = FALSE)
    {
        if ($append !== TRUE or empty($this->directory)) {
            $this->directory = str_replace('.', '', trim($dir, '/')) . '/';
        } else {
            $this->directory .= str_replace('.', '', trim($dir, '/')) . '/';
        }
    }

    // --------------------------------------------------------------------

    /**
     * Fetch directory
     *
     * Feches the sub-directory (if any) that contains the requested
     * controller class.
     *
     * @deprecated 3.0.0 Read the 'directory' property instead
     * @return string
     */
    public function fetch_directory()
    {
        return $this->directory;
    }
}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Output Class
 *
 * Responsible for sending final output to the browser.
 *
 * @package CodeIgniter
 * @subpackage Libraries
 * @category Output
 * @author EllisLab Dev Team
 * @link https://codeigniter.com/userguide3/libraries/output.html
 */
class CI_Output
{

    /**
     * Final output string
     *
     * @var string
     */
    public $final_output = '';

    /**
     * Cache expiration time
     *
     * @var int
     */
    public $cache_expiration = 0;

    /**
     * List of server headers
     *
     * @var array
     */
    public $headers = array();

    /**
     * List of mime types
     *
     * @var array
     */
    public $mimes = array();

    /**
     * Mime-type for the current page
     *
     * @var string
     */
    protected $mime_type = 'text/html';

    /**
     * Enable Profiler flag
     *
     * @var bool
     */
    public $enable_profiler = FALSE;

    /**
     * php.ini zlib.output_compression flag
     *
     * @var bool
     */
    protected $_zlib_oc = FALSE;

    /**
     * CI output compression flag
     *
     * @var bool
     */
    protected $_compress_output = FALSE;

    /**
     * List of profiler sections
     *
     * @var array
     */
    protected $_profiler_sections = array();

    /**
     * Parse markers flag
     *
     * Whether or not to parse variables like {elapsed_time} and {memory_usage}.
     *
     * @var bool
     */
    public $parse_exec_vars = FALSE;

    /**
     * mbstring.func_overload flag
     *
     * @var bool
     */
    protected static $func_overload = FALSE;

    /**
     * Class constructor
     *
     * Determines whether zLib output compression will be used.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_zlib_oc = (bool) ini_get('zlib.output_compression');
        $this->_compress_output = ($this->_zlib_oc === FALSE && config_item('compress_output') === TRUE && extension_loaded('zlib'));

        log_message('info', 'Output Class Initialized');
    }

    // --------------------------------------------------------------------

    /**
     * Get Output
     *
     * Returns the current output string.
     *
     * @return string
     */
    public function get_output()
    {
        return $this->final_output;
    }

    // --------------------------------------------------------------------

    /**
     * Set Output
     *
     * Sets the output string.
     *
     * @param string $output
     *            Output data
     * @return CI_Output
     */
    public function set_output($output)
    {
        $this->final_output = $output;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Append Output
     *
     * Appends data onto the output string.
     *
     * @param string $output
     *            Data to append
     * @return CI_Output
     */
    public function append_output($output)
    {
        $this->final_output .= $output;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Set Header
     *
     * Lets you set a server header which will be sent with the final output.
     *
     * Note: If a file is cached, headers will not be sent.
     *
     * @todo We need to figure out how to permit headers to be cached.
     *      
     * @param string $header
     *            Header
     * @param bool $replace
     *            Whether to replace the old header value, if already set
     * @return CI_Output
     */
    public function set_header($header, $replace = TRUE)
    {
        $this->headers[] = $header;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Set Content-Type Header
     *
     * @param string $mime_type
     *            Extension of the file we're outputting
     * @param string $charset
     *            Character set (default: NULL)
     * @return CI_Output
     */
    public function set_content_type($mime_type, $charset = NULL)
    {
        if (strpos($mime_type, '/') === FALSE) {
            $extension = ltrim($mime_type, '.');

            // Is this extension supported?
            if (isset($this->mimes[$extension])) {
                $mime_type = &$this->mimes[$extension];

                if (is_array($mime_type)) {
                    $mime_type = current($mime_type);
                }
            }
        }

        $this->mime_type = $mime_type;

        if (empty($charset)) {
            $charset = config_item('charset');
        }

        $header = 'Content-Type: ' . $mime_type . (empty($charset) ? '' : '; charset=' . $charset);

        $this->headers[] = $header;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Get Current Content-Type Header
     *
     * @return string 'text/html', if not already set
     */
    public function get_content_type()
    {
        for ($i = 0, $c = count($this->headers); $i < $c; $i ++) {
            if (sscanf($this->headers[$i][0], 'Content-Type: %[^;]', $content_type) === 1) {
                return $content_type;
            }
        }

        return 'text/html';
    }

    // --------------------------------------------------------------------

    /**
     * Get Header
     *
     * @param string $header
     * @return string
     */
    public function get_header($header)
    {
        // We only need [x][0] from our multi-dimensional array
        $header_lines = array_map(function ($headers) {
            return array_shift($headers);
        }, $this->headers);

        $headers = array_merge($header_lines, headers_list());

        if (empty($headers) or empty($header)) {
            return NULL;
        }

        // Count backwards, in order to get the last matching header
        for ($c = count($headers) - 1; $c > - 1; $c --) {
            if (strncasecmp($header, $headers[$c], $l = strlen($header)) === 0) {
                return trim(self::substr($headers[$c], $l + 1));
            }
        }

        return NULL;
    }

    // --------------------------------------------------------------------

    /**
     * Set Profiler Sections
     *
     * Allows override of default/config settings for
     * Profiler section display.
     *
     * @param array $sections
     *            Profiler sections
     * @return CI_Output
     */
    public function set_profiler_sections($sections)
    {
        return;
    }

    // --------------------------------------------------------------------

    /**
     * Set Cache
     *
     * @param int $time
     *            Cache expiration time in minutes
     * @return CI_Output
     */
    public function cache($time)
    {
        $this->cache_expiration = is_numeric($time) ? $time : 0;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Display Output
     *
     * Processes and sends finalized output data to the browser along
     * with any server headers and profile data. It also stops benchmark
     * timers so the page rendering speed and memory usage can be shown.
     *
     * Note: All "view" data is automatically put into $this->final_output
     * by controller class.
     *
     * @uses CI_Output::$final_output
     * @param string $output
     *            Output data override
     * @return void
     */
    public function _display($output = '')
    {
        // Note: We use load_class() because we can't use $CI =& get_instance()
        // since this function is sometimes called by the caching mechanism,
        // which happens before the CI super object is available.
        $CFG = &load_class('Config', 'core');

        // Grab the super object if we can.
        if (class_exists('CI_Controller', FALSE)) {
            $CI = &get_instance();
        }

        // --------------------------------------------------------------------

        // Set the output data
        if ($output === '') {
            $output = &$this->final_output;
        }

        // --------------------------------------------------------------------

        // Do we need to write a cache file? Only if the controller does not have its
        // own _output() method and we are not dealing with a cache file, which we
        // can determine by the existence of the $CI object above
        if ($this->cache_expiration > 0 && isset($CI) && ! method_exists($CI, '_output')) {
            $this->_write_cache($output);
        }

        // Is compression requested?
        if (isset($CI) && // This means that we're not serving a cache file, if we were, it would already be compressed
        $this->_compress_output === TRUE && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
            ob_start('ob_gzhandler');
        }

        // --------------------------------------------------------------------

        // Are there any server headers to send?
        if (count($this->headers) > 0) {
            foreach ($this->headers as $header) {
                @header($header);
            }
        }

        // --------------------------------------------------------------------

        // Does the $CI object exist?
        // If not we know we are dealing with a cache file so we'll
        // simply echo out the data and exit.
        if (! isset($CI)) {
            if ($this->_compress_output === TRUE) {
                if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
                    header('Content-Encoding: gzip');
                    header('Content-Length: ' . strlen($output));
                } else {
                    // User agent doesn't support gzip compression,
                    // so we'll have to decompress our cache
                    $output = gzinflate(self::substr($output, 10, - 8));
                }
            }

            echo $output;
            log_message('info', 'Final output sent to browser');
            return;
        }

        // --------------------------------------------------------------------
        // Does the controller contain a function named _output()?
        // If so send the output there. Otherwise, echo it.
        if (method_exists($CI, '_output')) {
            $CI->_output($output);
        } else {
            echo $output; // Send it to the browser!
        }

        log_message('info', 'Final output sent to browser');
    }

    // --------------------------------------------------------------------

    /**
     * Write Cache
     *
     * @param string $output
     *            Output data to cache
     * @return void
     */
    public function _write_cache($output)
    {
        $CI = &get_instance();
        $path = $CI->config->item('cache_path');
        $cache_path = ($path === '') ? APPPATH . 'cache/' : $path;

        if (! is_dir($cache_path) or ! is_writable($cache_path)) {
            log_message('error', 'Unable to write cache file: ' . $cache_path);
            return;
        }

        $uri = $CI->config->item('base_url') . $CI->config->item('index_page') . $CI->uri->uri_string();

        if (($cache_query_string = $CI->config->item('cache_query_string')) && ! empty($_SERVER['QUERY_STRING'])) {
            if (is_array($cache_query_string)) {
                $uri .= '?' . http_build_query(array_intersect_key($_GET, array_flip($cache_query_string)));
            } else {
                $uri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        $cache_path .= md5($uri);

        if (! $fp = @fopen($cache_path, 'w+b')) {
            log_message('error', 'Unable to write cache file: ' . $cache_path);
            return;
        }

        if (! flock($fp, LOCK_EX)) {
            log_message('error', 'Unable to secure a file lock for file at: ' . $cache_path);
            fclose($fp);
            return;
        }

        // If output compression is enabled, compress the cache
        // itself, so that we don't have to do that each time
        // we're serving it
        if ($this->_compress_output === TRUE) {
            $output = gzencode($output);

            if ($this->get_header('content-type') === NULL) {
                $this->set_content_type($this->mime_type);
            }
        }

        $expire = time() + ($this->cache_expiration * 60);

        // Put together our serialized info.
        $cache_info = serialize(array(
            'expire' => $expire,
            'headers' => $this->headers
        ));

        $output = $cache_info . 'ENDCI--->' . $output;

        for ($written = 0, $length = strlen($output); $written < $length; $written += $result) {
            if (($result = fwrite($fp, self::substr($output, $written))) === FALSE) {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        if (! is_int($result)) {
            @unlink($cache_path);
            log_message('error', 'Unable to write the complete cache content at: ' . $cache_path);
            return;
        }

        log_message('debug', 'Cache file written: ' . $cache_path);
    }

    // --------------------------------------------------------------------

    /**
     * Update/serve cached output
     *
     * @uses CI_Config
     * @uses CI_URI
     *      
     * @param
     *            object &$CFG CI_Config class instance
     * @param
     *            object &$URI CI_URI class instance
     * @return bool TRUE on success or FALSE on failure
     */
    public function _display_cache(&$CFG, &$URI)
    {
        $cache_path = ($CFG->item('cache_path') === '') ? APPPATH . 'cache/' : $CFG->item('cache_path');

        // Build the file path. The file name is an MD5 hash of the full URI
        $uri = $CFG->item('base_url') . $CFG->item('index_page') . $URI->uri_string;

        if (($cache_query_string = $CFG->item('cache_query_string')) && ! empty($_SERVER['QUERY_STRING'])) {
            if (is_array($cache_query_string)) {
                $uri .= '?' . http_build_query(array_intersect_key($_GET, array_flip($cache_query_string)));
            } else {
                $uri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        $filepath = $cache_path . md5($uri);

        if (! file_exists($filepath) or ! $fp = @fopen($filepath, 'rb')) {
            return FALSE;
        }

        flock($fp, LOCK_SH);

        $cache = (filesize($filepath) > 0) ? fread($fp, filesize($filepath)) : '';

        flock($fp, LOCK_UN);
        fclose($fp);

        // Look for embedded serialized file info.
        if (! preg_match('/^(.*)ENDCI--->/', $cache, $match)) {
            return FALSE;
        }

        $cache_info = unserialize($match[1]);
        $expire = $cache_info['expire'];

        $last_modified = filemtime($filepath);

        // Has the file expired?
        if ($_SERVER['REQUEST_TIME'] >= $expire && is_writable($cache_path)) {
            // If so we'll delete it.
            @unlink($filepath);
            log_message('debug', 'Cache file has expired. File deleted.');
            return FALSE;
        }

        // Add headers from cache file.
        foreach ($cache_info['headers'] as $header) {
            $this->set_header($header);
        }

        // Display the cache
        $this->_display(self::substr($cache, strlen($match[0])));
        log_message('debug', 'Cache file is current. Sending it to browser.');
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Delete cache
     *
     * @param string $uri
     *            URI string
     * @return bool
     */
    public function delete_cache($uri = '')
    {
        $CI = &get_instance();
        $cache_path = $CI->config->item('cache_path');
        if ($cache_path === '') {
            $cache_path = APPPATH . 'cache/';
        }

        if (! is_dir($cache_path)) {
            log_message('error', 'Unable to find cache path: ' . $cache_path);
            return FALSE;
        }

        if (empty($uri)) {
            $uri = $CI->uri->uri_string();

            if (($cache_query_string = $CI->config->item('cache_query_string')) && ! empty($_SERVER['QUERY_STRING'])) {
                if (is_array($cache_query_string)) {
                    $uri .= '?' . http_build_query(array_intersect_key($_GET, array_flip($cache_query_string)));
                } else {
                    $uri .= '?' . $_SERVER['QUERY_STRING'];
                }
            }
        }

        $cache_path .= md5($CI->config->item('base_url') . $CI->config->item('index_page') . ltrim($uri, '/'));

        if (! @unlink($cache_path)) {
            log_message('error', 'Unable to delete cache file for ' . $uri);
            return FALSE;
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Byte-safe substr()
     *
     * @param string $str
     * @param int $start
     * @param int $length
     * @return string
     */
    protected static function substr($str, $start, $length = NULL)
    {
        return isset($length) ? substr($str, $start, $length) : substr($str, $start);
    }
}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Input Class
 *
 * Pre-processes global input data for security
 *
 * @package CodeIgniter
 * @subpackage Libraries
 * @category Input
 * @author EllisLab Dev Team
 * @link https://codeigniter.com/userguide3/libraries/input.html
 */
class CI_Input
{

    /**
     * IP address of the current user
     *
     * @var string
     */
    protected $ip_address = FALSE;

    /**
     * Allow GET array flag
     *
     * If set to FALSE, then $_GET will be set to an empty array.
     *
     * @var bool
     */
    protected $_allow_get_array = TRUE;

    /**
     * Standardize new lines flag
     *
     * If set to TRUE, then newlines are standardized.
     *
     * @var bool
     */
    protected $_standardize_newlines;

    /**
     * Enable XSS flag
     *
     * Determines whether the XSS filter is always active when
     * GET, POST or COOKIE data is encountered.
     * Set automatically based on config setting.
     *
     * @var bool
     */
    protected $_enable_xss = FALSE;

    /**
     * List of all HTTP request headers
     *
     * @var array
     */
    protected $headers = array();

    /**
     * Raw input stream data
     *
     * Holds a cache of php://input contents
     *
     * @var string
     */
    protected $_raw_input_stream;

    /**
     * Parsed input stream data
     *
     * Parsed from php://input at runtime
     *
     * @see CI_Input::input_stream()
     * @var array
     */
    protected $_input_stream;

    protected $security;

    protected $uni;

    // --------------------------------------------------------------------

    /**
     * Class constructor
     *
     * Determines whether to globally enable the XSS processing
     * and whether to allow the $_GET array.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_enable_xss = (config_item('global_xss_filtering') === TRUE);
        $this->_standardize_newlines = (bool) config_item('standardize_newlines');

        $this->security = &load_class('Security', 'core');

        // Sanitize global arrays
        $this->_sanitize_globals();

        log_message('info', 'Input Class Initialized');
    }

    // --------------------------------------------------------------------

    /**
     * Fetch from array
     *
     * Internal method used to retrieve values from global arrays.
     *
     * @param
     *            array &$array $_GET, $_POST, $_COOKIE, $_SERVER, etc.
     * @param mixed $index
     *            Index for item to be fetched from $array
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return mixed
     */
    protected function _fetch_from_array(&$array, $index = NULL, $xss_clean = NULL)
    {
        is_bool($xss_clean) or $xss_clean = $this->_enable_xss;

        // If $index is NULL, it means that the whole $array is requested
        isset($index) or $index = array_keys($array);

        // allow fetching multiple keys at once
        if (is_array($index)) {
            $output = array();
            foreach ($index as $key) {
                $output[$key] = $this->_fetch_from_array($array, $key, $xss_clean);
            }

            return $output;
        }

        if (isset($array[$index])) {
            $value = $array[$index];
        } elseif (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) // Does the index contain array notation
        {
            $value = $array;
            for ($i = 0; $i < $count; $i ++) {
                $key = trim($matches[0][$i], '[]');
                if ($key === '') // Empty notation will return the value as array
                {
                    break;
                }

                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return NULL;
                }
            }
        } else {
            // in CP we are considering false for populating empty input fields for DB values
            return FALSE;
        }

        return ($xss_clean === TRUE) ? $this->security->xss_clean($value) : $value;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the GET array
     *
     * @param mixed $index
     *            Index for item to be fetched from $_GET
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return mixed
     */
    public function get($index = NULL, $xss_clean = NULL)
    {
        return $this->_fetch_from_array($_GET, $index, $xss_clean);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the POST array
     *
     * @param mixed $index
     *            Index for item to be fetched from $_POST
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return mixed
     */
    public function post($index = NULL, $xss_clean = NULL)
    {
        return $this->_fetch_from_array($_POST, $index, $xss_clean);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from POST data with fallback to GET
     *
     * @param string $index
     *            Index for item to be fetched from $_POST or $_GET
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return mixed
     */
    public function post_get($index, $xss_clean = NULL)
    {
        return isset($_POST[$index]) ? $this->post($index, $xss_clean) : $this->get($index, $xss_clean);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from GET data with fallback to POST
     *
     * @param string $index
     *            Index for item to be fetched from $_GET or $_POST
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return mixed
     */
    public function get_post($index, $xss_clean = NULL)
    {
        return isset($_GET[$index]) ? $this->get($index, $xss_clean) : $this->post($index, $xss_clean);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the COOKIE array
     *
     * @param mixed $index
     *            Index for item to be fetched from $_COOKIE
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return mixed
     */
    public function cookie($index = NULL, $xss_clean = NULL)
    {
        return $this->_fetch_from_array($_COOKIE, $index, $xss_clean);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the SERVER array
     *
     * @param mixed $index
     *            Index for item to be fetched from $_SERVER
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return mixed
     */
    public function server($index, $xss_clean = NULL)
    {
        return $this->_fetch_from_array($_SERVER, $index, $xss_clean);
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch an item from the php://input stream
     *
     * Useful when you need to access PUT, DELETE or PATCH request data.
     *
     * @param string $index
     *            Index for item to be fetched
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return mixed
     */
    public function input_stream($index = NULL, $xss_clean = NULL)
    {
        // Prior to PHP 5.6, the input stream can only be read once,
        // so we'll need to check if we have already done that first.
        if (! is_array($this->_input_stream)) {
            // $this->raw_input_stream will trigger __get().
            parse_str($this->raw_input_stream, $this->_input_stream);
            is_array($this->_input_stream) or $this->_input_stream = array();
        }

        return $this->_fetch_from_array($this->_input_stream, $index, $xss_clean);
    }

    // ------------------------------------------------------------------------

    /**
     * Set cookie
     *
     * Accepts an arbitrary number of parameters (up to 7) or an associative
     * array in the first parameter containing all the values.
     *
     * @param string|mixed[] $name
     *            Cookie name or an array containing parameters
     * @param string $value
     *            Cookie value
     * @param int $expire
     *            Cookie expiration time in seconds
     * @param string $domain
     *            Cookie domain (e.g.: '.yourdomain.com')
     * @param string $path
     *            Cookie path (default: '/')
     * @param string $prefix
     *            Cookie name prefix
     * @param bool $secure
     *            Whether to only transfer cookies via SSL
     * @param bool $httponly
     *            Whether to only makes the cookie accessible via HTTP (no javascript)
     * @param string $samesite
     *            SameSite attribute
     * @return void
     */
    public function set_cookie($name, $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = NULL, $httponly = NULL, $samesite = NULL)
    {
        return;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the IP Address
     *
     * Determines and validates the visitor's IP address.
     *
     * @return string IP address
     */
    public function ip_address()
    {
        if ($this->ip_address !== FALSE) {
            return $this->ip_address;
        }

        $proxy_ips = config_item('proxy_ips');
        if (! empty($proxy_ips) && ! is_array($proxy_ips)) {
            $proxy_ips = explode(',', str_replace(' ', '', $proxy_ips));
        }

        $this->ip_address = $this->server('REMOTE_ADDR');

        if ($proxy_ips) {
            foreach (array(
                'HTTP_X_FORWARDED_FOR',
                'HTTP_CLIENT_IP',
                'HTTP_X_CLIENT_IP',
                'HTTP_X_CLUSTER_CLIENT_IP'
            ) as $header) {
                if (($spoof = $this->server($header)) !== NULL) {
                    // Some proxies typically list the whole chain of IP
                    // addresses through which the client has reached us.
                    // e.g. client_ip, proxy_ip1, proxy_ip2, etc.
                    sscanf($spoof, '%[^,]', $spoof);

                    if (! $this->valid_ip($spoof)) {
                        $spoof = NULL;
                    } else {
                        break;
                    }
                }
            }

            if ($spoof) {
                for ($i = 0, $c = count($proxy_ips); $i < $c; $i ++) {
                    // Check if we have an IP address or a subnet
                    if (strpos($proxy_ips[$i], '/') === FALSE) {
                        // An IP address (and not a subnet) is specified.
                        // We can compare right away.
                        if ($proxy_ips[$i] === $this->ip_address) {
                            $this->ip_address = $spoof;
                            break;
                        }

                        continue;
                    }

                    // We have a subnet ... now the heavy lifting begins
                    isset($separator) or $separator = $this->valid_ip($this->ip_address, 'ipv6') ? ':' : '.';

                    // If the proxy entry doesn't match the IP protocol - skip it
                    if (strpos($proxy_ips[$i], $separator) === FALSE) {
                        continue;
                    }

                    // Convert the REMOTE_ADDR IP address to binary, if needed
                    if (! isset($ip, $sprintf)) {
                        if ($separator === ':') {
                            // Make sure we're have the "full" IPv6 format
                            $ip = explode(':', str_replace('::', str_repeat(':', 9 - substr_count($this->ip_address, ':')), $this->ip_address));

                            for ($j = 0; $j < 8; $j ++) {
                                $ip[$j] = intval($ip[$j], 16);
                            }

                            $sprintf = '%016b%016b%016b%016b%016b%016b%016b%016b';
                        } else {
                            $ip = explode('.', $this->ip_address);
                            $sprintf = '%08b%08b%08b%08b';
                        }

                        $ip = vsprintf($sprintf, $ip);
                    }

                    // Split the netmask length off the network address
                    sscanf($proxy_ips[$i], '%[^/]/%d', $netaddr, $masklen);

                    // Again, an IPv6 address is most likely in a compressed form
                    if ($separator === ':') {
                        $netaddr = explode(':', str_replace('::', str_repeat(':', 9 - substr_count($netaddr, ':')), $netaddr));
                        for ($j = 0; $j < 8; $j ++) {
                            $netaddr[$j] = intval($netaddr[$j], 16);
                        }
                    } else {
                        $netaddr = explode('.', $netaddr);
                    }

                    // Convert to binary and finally compare
                    if (strncmp($ip, vsprintf($sprintf, $netaddr), $masklen) === 0) {
                        $this->ip_address = $spoof;
                        break;
                    }
                }
            }
        }

        if (! $this->valid_ip($this->ip_address)) {
            return $this->ip_address = '0.0.0.0';
        }

        return $this->ip_address;
    }

    // --------------------------------------------------------------------

    /**
     * Validate IP Address
     *
     * @param string $ip
     *            IP address
     * @param string $which
     *            IP protocol: 'ipv4' or 'ipv6'
     * @return bool
     */
    public function valid_ip($ip, $which = '')
    {
        // RightNow PHP does not support filter_var. Hence using the old school way of ip validaiton
        /*
         * switch (strtolower($which))
         * {
         * case 'ipv4':
         * $which = FILTER_FLAG_IPV4;
         * break;
         * case 'ipv6':
         * $which = FILTER_FLAG_IPV6;
         * break;
         * default:
         * $which = 0;
         * break;
         * }
         *
         * return (bool) filter_var($ip, FILTER_VALIDATE_IP, $which);
         */
        $ip_segments = explode('.', $ip);

        // Always 4 segments needed
        if (count($ip_segments) != 4) {
            return FALSE;
        }
        // IP can not start with 0
        if (substr($ip_segments[0], 0, 1) == '0') {
            return FALSE;
        }
        // Check each segment
        foreach ($ip_segments as $segment) {
            // IP segments must be digits and can not be
            // longer than 3 digits or greater then 255
            if (preg_match("/[^0-9]/", $segment) or $segment > 255 or strlen($segment) > 3) {
                return FALSE;
            }
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch User Agent string
     *
     * @return string|null User Agent string or NULL if it doesn't exist
     */
    public function user_agent($xss_clean = NULL)
    {
        return $this->_fetch_from_array($_SERVER, 'HTTP_USER_AGENT', $xss_clean);
    }

    // --------------------------------------------------------------------

    /**
     * Sanitize Globals
     *
     * Internal method serving for the following purposes:
     *
     * - Unsets $_GET data, if query strings are not enabled
     * - Cleans POST, COOKIE and SERVER data
     * - Standardizes newline characters to PHP_EOL
     *
     * @return void
     */
    protected function _sanitize_globals()
    {
        // Is $_GET data allowed? If not we'll set the $_GET to an empty array
        if ($this->_allow_get_array === FALSE) {
            $_GET = array();
        } elseif (is_array($_GET)) {
            foreach ($_GET as $key => $val) {
                $_GET[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }
        }

        // Clean $_POST Data
        if (is_array($_POST)) {
            foreach ($_POST as $key => $val) {
                $_POST[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }
        }

        // Clean $_COOKIE Data
        if (is_array($_COOKIE)) {
            // Also get rid of specially treated cookies that might be set by a server
            // or silly application, that are of no use to a CI application anyway
            // but that when present will trip our 'Disallowed Key Characters' alarm
            // http://www.ietf.org/rfc/rfc2109.txt
            // note that the key names below are single quoted strings, and are not PHP variables
            unset($_COOKIE['$Version'], $_COOKIE['$Path'], $_COOKIE['$Domain']);

            foreach ($_COOKIE as $key => $val) {
                if (($cookie_key = $this->_clean_input_keys($key)) !== FALSE) {
                    $_COOKIE[$cookie_key] = $this->_clean_input_data($val);
                } else {
                    unset($_COOKIE[$key]);
                }
            }
        }

        // Sanitize PHP_SELF
        $_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

        log_message('debug', 'Global POST, GET and COOKIE data sanitized');
    }

    // --------------------------------------------------------------------

    /**
     * Clean Input Data
     *
     * Internal method that aids in escaping data and
     * standardizing newline characters to PHP_EOL.
     *
     * @param string|string[] $str
     *            Input string(s)
     * @return string
     */
    protected function _clean_input_data($str)
    {
        if (is_array($str)) {
            $new_array = array();
            foreach (array_keys($str) as $key) {
                $new_array[$this->_clean_input_keys($key)] = $this->_clean_input_data($str[$key]);
            }
            return $new_array;
        }

        // Remove control characters
        $str = remove_invisible_characters($str, FALSE);

        // Standardize newlines if needed
        if ($this->_standardize_newlines === TRUE) {
            return preg_replace('/(?:\r\n|[\r\n])/', PHP_EOL, $str);
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Clean Keys
     *
     * Internal method that helps to prevent malicious users
     * from trying to exploit keys we make sure that keys are
     * only named with alpha-numeric text and a few other items.
     *
     * @param string $str
     *            Input string
     * @param bool $fatal
     *            Whether to terminate script exection
     *            or to return FALSE if an invalid
     *            key is encountered
     * @return string|bool
     */
    protected function _clean_input_keys($str, $fatal = TRUE)
    {
        if (! preg_match('/^[a-zA-Z0-9.#:_\/|-]+$/i', $str)) {
            if ($fatal === TRUE) {
                return FALSE;
            } else {
                echo 'Disallowed Key Characters.';
                exit(7); // EXIT_USER_INPUT
            }
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Request Headers
     *
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return array
     */
    public function request_headers($xss_clean = FALSE)
    {
        // If header is already defined, return it immediately
        if (! empty($this->headers)) {
            return $this->_fetch_from_array($this->headers, NULL, $xss_clean);
        }

        // In Apache, you can simply call apache_request_headers()
        if (function_exists('apache_request_headers')) {
            $this->headers = apache_request_headers();
        } else {
            isset($_SERVER['CONTENT_TYPE']) && $this->headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];

            foreach ($_SERVER as $key => $val) {
                if (sscanf($key, 'HTTP_%s', $header) === 1) {
                    // take SOME_HEADER and turn it into Some-Header
                    $header = str_replace('_', ' ', strtolower($header));
                    $header = str_replace(' ', '-', ucwords($header));

                    $this->headers[$header] = $_SERVER[$key];
                }
            }
        }

        return $this->_fetch_from_array($this->headers, NULL, $xss_clean);
    }

    // --------------------------------------------------------------------

    /**
     * Get Request Header
     *
     * Returns the value of a single member of the headers class member
     *
     * @param string $index
     *            Header name
     * @param bool $xss_clean
     *            Whether to apply XSS filtering
     * @return string|null The requested header on success or NULL on failure
     */
    public function get_request_header($index, $xss_clean = FALSE)
    {
        static $headers;

        if (! isset($headers)) {
            empty($this->headers) && $this->request_headers();
            foreach ($this->headers as $key => $value) {
                $headers[strtolower($key)] = $value;
            }
        }

        $index = strtolower($index);

        if (! isset($headers[$index])) {
            return NULL;
        }

        return ($xss_clean === TRUE) ? $this->security->xss_clean($headers[$index]) : $headers[$index];
    }

    // --------------------------------------------------------------------

    /**
     * Is AJAX request?
     *
     * Test to see if a request contains the HTTP_X_REQUESTED_WITH header.
     *
     * @return bool
     */
    public function is_ajax_request()
    {
        return (! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    // --------------------------------------------------------------------

    /**
     * Get Request Method
     *
     * Return the request method
     *
     * @param bool $upper
     *            Whether to return in upper or lower case
     *            (default: FALSE)
     * @return string
     */
    public function method($upper = FALSE)
    {
        return ($upper) ? strtoupper($this->server('REQUEST_METHOD')) : strtolower($this->server('REQUEST_METHOD'));
    }

    // ------------------------------------------------------------------------

    /**
     * Magic __get()
     *
     * Allows read access to protected properties
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === 'raw_input_stream') {
            isset($this->_raw_input_stream) or $this->_raw_input_stream = file_get_contents('php://input');
            return $this->_raw_input_stream;
        } elseif ($name === 'ip_address') {
            return $this->ip_address;
        }
    }

    // --------------------------------------------------------------------
    // CP specific function
    // --------------------------------------------------------------------

    /**
     * Fetch an item from either POST or GET
     *
     * @access public
     * @param
     *            string
     * @param
     *            bool
     * @return string|boolean String value or False if not found
     */
    public function request($index = '', $xssClean = true)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // #post returns false if not found
            if (($result = $this->post($index)) === false)
                return false;
            return ($xssClean) ? str_replace('"', '&quot;', $result) : $result;
        }

        // #getParameter returns null if not found
        if (($result = \RightNow\Utils\Url::getParameter($index)) === null)
            return false;
        return ($xssClean) ? $result : str_replace("&quot;", '"', $result);
    }
}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * URI Class
 *
 * Parses URIs and determines routing
 *
 * @package CodeIgniter
 * @subpackage Libraries
 * @category URI
 * @author EllisLab Dev Team
 * @link https://codeigniter.com/userguide3/libraries/uri.html
 */
class CI_URI
{

    /**
     * List of cached URI segments
     *
     * @var array
     */
    public $keyval = array();

    /**
     * Current URI string
     *
     * @var string
     */
    public $uri_string = '';

    /**
     * List of URI segments
     *
     * Starts at 1 instead of 0.
     *
     * @var array
     */
    public $segments = array();

    /**
     * List of routed URI segments
     *
     * Starts at 1 instead of 0.
     *
     * @var array
     */
    public $rsegments = array();

    /**
     * Permitted URI chars
     *
     * PCRE character group allowed in URI segments
     *
     * @var string
     */
    protected $_permitted_uri_chars;

    /**
     *
     * @var object
     */
    public $router;

    /**
     * @var object
     */
    public $config;

    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->config = &load_class('Config', 'core');
        log_message('info', 'URI Class Initialized');
    }

    // --------------------------------------------------------------------

    /**
     * Set URI String
     *
     * @param string $str
     * @return void
     */
    protected function _set_uri_string($str)
    {
        // Filter out control characters and trim slashes
        $this->uri_string = trim(remove_invisible_characters($str, FALSE), '/');

        if ($this->uri_string !== '') {
            // Remove the URL suffix, if present
            if (($suffix = (string) $this->config->item('url_suffix')) !== '') {
                $slen = strlen($suffix);

                if (substr($this->uri_string, - $slen) === $suffix) {
                    $this->uri_string = substr($this->uri_string, 0, - $slen);
                }
            }

            $this->segments[0] = NULL;
            // Populate the segments array
            foreach (explode('/', trim($this->uri_string, '/')) as $val) {
                $val = trim($val);
                // Filter segments for security
                $this->filter_uri($val);

                if ($val !== '') {
                    $this->segments[] = $val;
                }
            }

            unset($this->segments[0]);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Parse REQUEST_URI
     *
     * Will parse REQUEST_URI and automatically detect the URI from it,
     * while fixing the query string if necessary.
     *
     * @return string
     */
    protected function _parse_request_uri()
    {
        if (! isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) {
            return '';
        }

        // parse_url() returns false if no host is present, but the path or query string
        // contains a colon followed by a number
        $uri = parse_url('http://dummy' . $_SERVER['REQUEST_URI']);
        $query = isset($uri['query']) ? $uri['query'] : '';
        $uri = isset($uri['path']) ? $uri['path'] : '';

        if (isset($_SERVER['SCRIPT_NAME'][0])) {
            if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
                $uri = (string) substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            } elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
                $uri = (string) substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }
        }

        // This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
        // URI is found, and also fixes the QUERY_STRING server var and $_GET array.
        if (trim($uri, '/') === '' && strncmp($query, '/', 1) === 0) {
            $query = explode('?', $query, 2);
            $uri = $query[0];
            $_SERVER['QUERY_STRING'] = isset($query[1]) ? $query[1] : '';
        } else {
            $_SERVER['QUERY_STRING'] = $query;
        }

        parse_str($_SERVER['QUERY_STRING'], $_GET);

        if ($uri === '/' or $uri === '') {
            return '/';
        }

        // Do some final cleaning of the URI and return it
        return $this->_remove_relative_directory($uri);
    }

    // --------------------------------------------------------------------

    /**
     * Parse QUERY_STRING
     *
     * Will parse QUERY_STRING and automatically detect the URI from it.
     *
     * @return string
     */
    protected function _parse_query_string()
    {
        $uri = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');

        if (trim($uri, '/') === '') {
            return '';
        } elseif (strncmp($uri, '/', 1) === 0) {
            $uri = explode('?', $uri, 2);
            $_SERVER['QUERY_STRING'] = isset($uri[1]) ? $uri[1] : '';
            $uri = $uri[0];
        }

        parse_str($_SERVER['QUERY_STRING'], $_GET);

        return $this->_remove_relative_directory($uri);
    }

    // --------------------------------------------------------------------

    /**
     * Parse CLI arguments
     *
     * Take each command line argument and assume it is a URI segment.
     *
     * @return string
     */
    protected function _parse_argv()
    {
        $args = array_slice($_SERVER['argv'], 1);
        return $args ? implode('/', $args) : '';
    }

    // --------------------------------------------------------------------

    /**
     * Remove relative directory (../) and multi slashes (///)
     *
     * Do some final cleaning of the URI and return it, currently only used in self::_parse_request_uri()
     *
     * @param string $uri
     * @return string
     */
    protected function _remove_relative_directory($uri)
    {
        $uris = array();
        $tok = strtok($uri, '/');
        while ($tok !== FALSE) {
            if ((! empty($tok) or $tok === '0') && $tok !== '..') {
                $uris[] = $tok;
            }
            $tok = strtok('/');
        }

        return implode('/', $uris);
    }

    // --------------------------------------------------------------------

    /**
     * Filter URI
     *
     * Filters segments for malicious characters.
     *
     * @param string $str
     * @return void
     */
    public function filter_uri(&$str)
    {
        if (! empty($str) && ! empty($this->_permitted_uri_chars) && ! preg_match('/^[' . $this->_permitted_uri_chars . ']+$/i', $str)) {
            show_error('The URI you submitted has disallowed characters.', 400);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Fetch URI Segment
     *
     * @see CI_URI::$segments
     * @param int $n
     *            Index
     * @param mixed $no_result
     *            What to return if the segment index is not found
     * @return mixed
     */
    public function segment($n, $no_result = NULL)
    {
        return isset($this->segments[$n]) ? $this->segments[$n] : $no_result;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch URI "routed" Segment
     *
     * Returns the re-routed URI segment (assuming routing rules are used)
     * based on the index provided. If there is no routing, will return
     * the same result as CI_URI::segment().
     *
     * @see CI_URI::$rsegments
     * @see CI_URI::segment()
     * @param int $n
     *            Index
     * @param mixed $no_result
     *            What to return if the segment index is not found
     * @return mixed
     */
    public function rsegment($n, $no_result = NULL)
    {
        return isset($this->rsegments[$n]) ? $this->rsegments[$n] : $no_result;
    }

    // --------------------------------------------------------------------

    /**
     * URI to assoc
     *
     * Generates an associative array of URI data starting at the supplied
     * segment index. For example, if this is your URI:
     *
     * example.com/user/search/name/joe/location/UK/gender/male
     *
     * You can use this method to generate an array with this prototype:
     *
     * array (
     * name => joe
     * location => UK
     * gender => male
     * )
     *
     * @param int $n
     *            Index (default: 3)
     * @param array $default
     *            Default values
     * @return array
     */
    public function uri_to_assoc($n = 3, $default = array())
    {
        return $this->_uri_to_assoc($n, $default, 'segment');
    }

    // --------------------------------------------------------------------

    /**
     * Routed URI to assoc
     *
     * Identical to CI_URI::uri_to_assoc(), only it uses the re-routed
     * segment array.
     *
     * @see CI_URI::uri_to_assoc()
     * @param int $n
     *            Index (default: 3)
     * @param array $default
     *            Default values
     * @return array
     */
    public function ruri_to_assoc($n = 3, $default = array())
    {
        return $this->_uri_to_assoc($n, $default, 'rsegment');
    }

    // --------------------------------------------------------------------

    /**
     * Internal URI-to-assoc
     *
     * Generates a key/value pair from the URI string or re-routed URI string.
     *
     * @used-by	CI_URI::uri_to_assoc()
     * @used-by	CI_URI::ruri_to_assoc()
     * @param int $n
     *            Index (default: 3)
     * @param array $default
     *            Default values
     * @param string $which
     *            Array name ('segment' or 'rsegment')
     * @return array
     */
    protected function _uri_to_assoc($n = 3, $default = array(), $which = 'segment')
    {
        if (! is_numeric($n)) {
            return $default;
        }

        if (isset($this->keyval[$which], $this->keyval[$which][$n]) && load_class('Router', 'core')->fetch_class() !== 'phpFunctional' && load_class('Router', 'core')->fetch_class() !== 'widgetFunctional') {
            return $this->keyval[$which][$n];
        }

        $total_segments = "total_{$which}s";
        $segment_array = "{$which}_array";

        if ($this->$total_segments() < $n) {
            return (count($default) === 0) ? array() : array_fill_keys($default, NULL);
        }

        // Check cache for existing result (except for when we're running unit tests)
        if (isset($this->keyval[$n]) && ! CUSTOM_CONTROLLER_REQUEST && load_class('Router', 'core')->fetch_class() !== 'phpFunctional' && load_class('Router', 'core')->fetch_class() !== 'widgetFunctional') {
            return $this->keyval[$n];
        }

        $segments = array_slice($this->$segment_array(), ($n - 1));
        $i = 0;
        $lastval = '';
        $retval = array();
        foreach ($segments as $seg) {
            if ($i % 2) {
                $retval[$lastval] = $seg;
            } else {
                $retval[$seg] = NULL;
                $lastval = $seg;
            }

            $i ++;
        }

        if (count($default) > 0) {
            foreach ($default as $val) {
                if (! array_key_exists($val, $retval)) {
                    $retval[$val] = NULL;
                }
            }
        }

        // Cache the array for reuse
        isset($this->keyval[$which]) or $this->keyval[$which] = array();
        $this->keyval[$which][$n] = $retval;
        return $retval;
    }

    // --------------------------------------------------------------------

    /**
     * Assoc to URI
     *
     * Generates a URI string from an associative array.
     *
     * @param array $array
     *            Input array of key/value pairs
     * @return string URI string
     */
    public function assoc_to_uri($array)
    {
        $temp = array();
        foreach ((array) $array as $key => $val) {
            $temp[] = $key;
            $temp[] = $val;
        }

        return implode('/', $temp);
    }

    // --------------------------------------------------------------------

    /**
     * Slash segment
     *
     * Fetches an URI segment with a slash.
     *
     * @param int $n
     *            Index
     * @param string $where
     *            Where to add the slash ('trailing' or 'leading')
     * @return string
     */
    public function slash_segment($n, $where = 'trailing')
    {
        return $this->_slash_segment($n, $where, 'segment');
    }

    // --------------------------------------------------------------------

    /**
     * Slash routed segment
     *
     * Fetches an URI routed segment with a slash.
     *
     * @param int $n
     *            Index
     * @param string $where
     *            Where to add the slash ('trailing' or 'leading')
     * @return string
     */
    public function slash_rsegment($n, $where = 'trailing')
    {
        return $this->_slash_segment($n, $where, 'rsegment');
    }

    // --------------------------------------------------------------------

    /**
     * Internal Slash segment
     *
     * Fetches an URI Segment and adds a slash to it.
     *
     * @used-by	CI_URI::slash_segment()
     * @used-by	CI_URI::slash_rsegment()
     *
     * @param int $n
     *            Index
     * @param string $where
     *            Where to add the slash ('trailing' or 'leading')
     * @param string $which
     *            Array name ('segment' or 'rsegment')
     * @return string
     */
    protected function _slash_segment($n, $where = 'trailing', $which = 'segment')
    {
        $leading = $trailing = '/';

        if ($where === 'trailing') {
            $leading = '';
        } elseif ($where === 'leading') {
            $trailing = '';
        }

        return $leading . $this->$which($n) . $trailing;
    }

    // --------------------------------------------------------------------

    /**
     * Segment Array
     *
     * @return array CI_URI::$segments
     */
    public function segment_array()
    {
        return $this->segments;
    }

    // --------------------------------------------------------------------

    /**
     * Routed Segment Array
     *
     * @return array CI_URI::$rsegments
     */
    public function rsegment_array()
    {
        return $this->rsegments;
    }

    // --------------------------------------------------------------------

    /**
     * Total number of segments
     *
     * @return int
     */
    public function total_segments()
    {
        return count($this->segments);
    }

    // --------------------------------------------------------------------

    /**
     * Total number of routed segments
     *
     * @return int
     */
    public function total_rsegments()
    {
        return count($this->rsegments);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch URI string
     *
     * @return string CI_URI::$uri_string
     */
    public function uri_string()
    {
        return $this->uri_string;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch Re-routed URI string
     *
     * @return string
     */
    public function ruri_string()
    {
        return ltrim(load_class('Router', 'core')->directory, '/') . implode('/', $this->rsegments);
    }
}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loader Class
 *
 * Loads framework components.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Loader
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/libraries/loader.html
 */
#[\AllowDynamicProperties]
class CI_Loader {
    
    // All these are set automatically. Don't mess with them.
    /**
     * Nesting level of the output buffering mechanism
     *
     * @var int
     */
    protected $_ci_ob_level;
    
    /**
     * List of paths to load views from
     *
     * @var array
     */
    protected $_ci_view_paths = array(APPPATH.'views/' => TRUE,);
    
    /**
     * List of paths to load libraries from
     *
     * @var array
     */
    protected $_ci_library_paths =  array(APPPATH, BASEPATH);
    
    /**
     * List of paths to load models from
     *
     * @var array
     */
    protected $_ci_model_paths =    array();
    
    /**
     * List of paths to load helpers from
     *
     * @var array
     */
    protected $_ci_helper_paths =   array();
    
    /**
     * List of cached variables
     *
     * @var array
     */
    protected $_ci_cached_vars =    array();
    
    /**
     * List of loaded classes
     *
     * @var array
     */
    protected $_ci_classes =    array();
    
    /**
     * List of loaded models
     *
     * @var array
     */
    protected $_ci_models = array();
    
    /**
     * List of loaded helpers
     *
     * @var array
     */
    protected $_ci_helpers =    array();
    
    /**
     * List of class name mappings
     *
     * @var array
     */
    protected $_ci_varmap = array(
        'unit_test' => 'unit',
        'user_agent' => 'agent',
        'Rntphpwrapper' => 'cpwrapper',
    );
    
    /**
     * CI Objects
     */
    public $loader;
    public $hooks;
    public $config;
    public $uri;
    public $router;
    public $output;
    public $security;
    public $input;
    public $rnow;
    public $themes;
    public $load;
    public $agent;
    public $cpwrapper;
    public $developmentHeader;
    public $session;
    public $postHandler;
    public $clientLoader;
    public $meta;
    public $page;
    public $widgetCallsOnPage;
    
    /**
     * CP Specific classes to be loaded
     */
    private static $classesLoadedByCoreCodeIgniter = array('Themes', 'Rnow', 'User_agent','Rntphpwrapper',);
    
    // --------------------------------------------------------------------
    
    /**
     * Class constructor
     *
     * Sets component load paths, gets the initial output buffering level.
     *
     * @return  void
     */
    public function __construct()
    {
        $this->_ci_ob_level = ob_get_level();
        $this->_ci_classes =& is_loaded();
        
        log_message('info', 'Loader Class Initialized');
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Initializer
     *
     * @todo    Figure out a way to move this to the constructor
     *      without breaking *package_path*() methods.
     * @uses    CI_Loader::_ci_autoloader()
     * @used-by CI_Controller::__construct()
     * @return  void
     */
    public function initialize()
    {
        $this->_ci_autoloader();
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Is Loaded
     *
     * A utility method to test if a class is in the self::$_ci_classes array.
     *
     * @used-by Mainly used by Form Helper function _get_validation_object().
     *
     * @param   string      $class  Class name to check for
     * @return  string|bool Class object name if loaded or FALSE
     */
    public function is_loaded($class)
    {
        return array_search(ucfirst($class), $this->_ci_classes, TRUE);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Library Loader
     *
     * Loads and instantiates libraries.
     * Designed to be called from application controllers.
     *
     * @param   mixed   $library    Library name
     * @param   array   $params     Optional parameters to pass to the library class constructor
     * @param   string  $object_name    An optional object name to assign to
     * @return  object
     */
    public function library($library, $params = NULL, $object_name = NULL)
    {
        if (empty($library))
        {
            return $this;
        }
        elseif (is_array($library))
        {
            foreach ($library as $key => $value)
            {
                if (is_int($key))
                {
                    return $this->loadLibrary($value, $params);
                }
                else
                {
                    return $this->loadLibrary($key, $params);
                }
            }
            
        }
        else {
            return $this->loadLibrary($library, $params);
        }
    }
    
    // --------------------------------------------------------------------
    // CP SPECIFIC FUNCTION BELOW
    // --------------------------------------------------------------------
    
    /**
     * Finds the path to the correct library and loads it off disk.
     * @param $library [string] The name of the library to load
     * @param $params [mixed] Parameters to pass to library constructor
     */
    private function loadLibrary($library, $constructorArguments){
        // Get the class name
        $library = ucfirst(str_replace(EXT, '', $library));
        
        // I added this bit to allow us to preload classes in
        // CoreCodeIgniter.php
        if (in_array($library, self::$classesLoadedByCoreCodeIgniter)) {
            $filepath = BASEPATH . 'core/' . $library . EXT;
            
            if(in_array($filepath, $this->_ci_classes))
                return;
            
            $this->_ci_classes[] = $filepath;
            return $this->initializeLibrary($library, $constructorArguments);
        }
        
        // We'll test for both lowercase and capitalized versions of the file name
        foreach (array($library, strtolower($library)) as $library)
        {
            // Lets search for the requested library file and load it.
            foreach (array(APPPATH, BASEPATH) as $path)
            {
                $filepath = "{$path}libraries/{$library}" . EXT;
                // Does the file exist?  No?  Bummer...
                if(!is_readable($filepath))
                    continue;
                    
                    // Safety:  Was the class already loaded by a previous call?
                    if(in_array($filepath, $this->_ci_classes))
                    {
                        log_message('debug', $library." class already loaded. Second attempt ignored.");
                        return;
                    }
                    include($filepath);
                    $this->_ci_classes[] = $filepath;
                    return $this->initializeLibrary($library, $constructorArguments, $path === APPPATH);
            }
        }
        
        // If we got this far we were unable to find the requested class.
        // We do not issue errors if the load call failed due to a duplicate request
        log_message('error', "Unable to load the requested class: ".$library);
        show_error("Unable to load the requested class: ".$library);
    }
    
    // --------------------------------------------------------------------
    // CP SPECIFIC FUNCTION BELOW
    // --------------------------------------------------------------------
    
    /**
     * Instantiates the requested library and assign it to the global CI instance.
     *
     * @param $library [string] The name of the library to instantiate
     * @param $constructorArguments [mixed] Arguments to pass to library constructor
     * @param $isCustomLibrary [bool] Denotes if custom library is being loaded. This will invoke namespacing rules
     */
    private function initializeLibrary($library, $constructorArguments, $isCustomLibrary = false)
    {
        if($isCustomLibrary){
            $name = "Custom\\Libraries\\$library";
            if(!class_exists($name)){
                show_error(sprintf(\RightNow\Utils\Config::getMessage(FND_LB_FILE_EXPECTED_CLASS_NAME_MSG), $name));
            }
        }
        else{
            if(class_exists("CI_$library")){
                $name = "CI_$library";
            }
            else if(class_exists($library)){
                $name = $library;
            }
            else{
                show_error(sprintf(\RightNow\Utils\Config::getMessage(FND_LIB_FILE_EXPECTED_CLASS_NAME_MSG), $name));
            }
        }
        
        // Set the variable name we will assign the class to
        $library = strtolower($library);
        $aliasName = (!isset($this->_ci_varmap[$library])) ? $library : $this->_ci_varmap[$library];
                
        // Instantiate the class
        if ($constructorArguments !== NULL){
            return get_instance()->$aliasName = new $name($constructorArguments);
        }
        else{
            return get_instance()->$aliasName = new $name;
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Model Loader
     *
     * This function lets users load and instantiate models. [It now uses our new autoloader in RightNow\Controllers\Base]
     *
     * @param   string  $model      String of the format <standard/custom>/<path>/<model name>
     * @param   string  $name       Alternate variable name with which to reference the model
     * @return  object
     */
    public function model($model, $name = '')
    {
        if (empty($model))
        {
            return;
        }
        
        if (in_array($name, $this->_ci_models, TRUE))
        {
            return;
        }
        
        $explodedPath = explode('/', $model);
        if(strtolower($explodedPath[0]) === 'standard')
            $fileName = str_ireplace('_model', '', end($explodedPath));
        else
            $fileName = end($explodedPath);

        $modelName = end($explodedPath);
        unset($explodedPath[count($explodedPath)-1]);

        if($name === '')
            $name = $modelName;

        $CI = get_instance();
        $CI->$name = $CI->model(implode('/', $explodedPath) . '/' . $fileName);
    }
    
    // --------------------------------------------------------------------
    // CP SPECIFIC FUNCTION
    // --------------------------------------------------------------------
    /**
     * Use this function to add a model to the internal CodeIgniter structure. Letting code igniter know that we have
     * added a model so that it can be updated with new references.
     * @param $model [object] - Reference to the loaded model
     */
    public function setModelLoaded($model)
    {
        $this->_ci_models[] = $model;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * View Loader
     *
     * Loads "view" files.
     *
     * @param   string  $view   View name
     * @param   array   $vars   An associative array of data
     *              to be extracted for use in the view
     * @param   bool    $return Whether to return the view output
     *              or leave it to the Output class
     * @return  object|string
     */
    public function view($view, $vars = array(), $return = FALSE)
    {
        return $this->_ci_load(array('_ci_view' => $view, 'vars' => $this->_ci_prepare_view_vars($vars), '_ci_return' => $return));
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Generic File Loader
     *
     * @param   string  $path   File path
     * @param   bool    $return Whether to return the file output
     * @return  object|string
     */
    public function file($path, $return = FALSE)
    {
        return $this->_ci_load(array('_ci_path' => $path, '_ci_return' => $return));
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Set Variables
     *
     * Once variables are set they become available within
     * the controller class and its "view" files.
     *
     * @param   array|object|string $vars
     *                  An associative array or object containing values
     *                  to be set, or a value's name if string
     * @param   string  $val    Value to set, only used if $vars is a string
     * @return  object
     */
    public function vars($vars, $val = '')
    {
        $vars = $this->_ci_prepare_view_vars($vars);
        foreach ($vars as $key => $val)
        {
            $this->_ci_cached_vars[$key] = $val;
        }
        
        return $this;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Clear Cached Variables
     *
     * Clears the cached variables.
     *
     * @return  CI_Loader
     */
    public function clear_vars()
    {
        $this->_ci_cached_vars = array();
        return $this;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get Variable
     *
     * Check if a variable is set and retrieve it.
     *
     * @param   string  $key    Variable name
     * @return  mixed   The variable or NULL if not found
     */
    public function get_var($key)
    {
        return isset($this->_ci_cached_vars[$key]) ? $this->_ci_cached_vars[$key] : NULL;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get Variables
     *
     * Retrieves all loaded variables.
     *
     * @return  array
     */
    public function get_vars()
    {
        return $this->_ci_cached_vars;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Helper Loader
     *
     * @param   string|string[] $helpers    Helper name(s)
     * @return  object
     */
    public function helper($helpers = array())
    {
        is_array($helpers) OR $helpers = array($helpers);
        foreach ($helpers as &$helper)
        {
            $filename = basename($helper);
            $filepath = ($filename === $helper) ? '' : substr($helper, 0, strlen($helper) - strlen($filename));
            $filename = strtolower(preg_replace('#(_helper)?(\.php)?$#i', '', $filename)).'_helper';
            $helper   = $filepath.$filename;
            
            if (isset($this->_ci_helpers[$helper]))
            {
                continue;
            }
            
            if (is_readable(APPPATH.'helpers/'.$helper.EXT))
            {
                include_once(APPPATH.'helpers/'.$helper.EXT);
            }
            else
            {
                if (is_readable(BASEPATH.'helpers/'.$helper.EXT))
                {
                    include(BASEPATH.'helpers/'.$helper.EXT);
                }
                else
                {
                    show_error('Unable to load the requested file: helpers/'.$helper.EXT);
                }
            }
            
            $this->_ci_helpers[$helper] = TRUE;
        }
        
        return $this;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Load Helpers
     *
     * An alias for the helper() method in case the developer has
     * written the plural form of it.
     *
     * @uses    CI_Loader::helper()
     * @param   string|string[] $helpers    Helper name(s)
     * @return  object
     */
    public function helpers($helpers = array())
    {
        return $this->helper($helpers);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Config Loader
     *
     * Loads a config file (an alias for CI_Config::load()).
     *
     * @uses    CI_Config::load()
     * @param   string  $file           Configuration file name
     * @param   bool    $use_sections       Whether configuration values should be loaded into their own section
     * @param   bool    $fail_gracefully    Whether to just return FALSE or display an error message
     * @return  bool    TRUE if the file was loaded correctly or FALSE on failure
     */
    public function config($file, $use_sections = FALSE, $fail_gracefully = FALSE)
    {
        return get_instance()->config->load($file, $use_sections, $fail_gracefully);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Remove Package Path
     *
     * Remove a path from the library, model, helper and/or config
     * path arrays if it exists. If no path is provided, the most recently
     * added path will be removed removed.
     *
     * @param   string  $path   Path to remove
     * @return  object
     */
    public function remove_package_path($path = '')
    {
        //vasanth commented as not required for CP
        /*
         $config =& $this->_ci_get_component('config');
         
         if ($path === '')
         {
         array_shift($this->_ci_library_paths);
         array_shift($this->_ci_model_paths);
         array_shift($this->_ci_helper_paths);
         array_shift($this->_ci_view_paths);
         array_pop($config->_config_paths);
         }
         else
         {
         $path = rtrim($path, '/').'/';
         foreach (array('_ci_library_paths', '_ci_model_paths', '_ci_helper_paths') as $var)
         {
         if (($key = array_search($path, $this->{$var})) !== FALSE)
         {
         unset($this->{$var}[$key]);
         }
         }
         
         if (isset($this->_ci_view_paths[$path.'views/']))
         {
         unset($this->_ci_view_paths[$path.'views/']);
         }
         
         if (($key = array_search($path, $config->_config_paths)) !== FALSE)
         {
         unset($config->_config_paths[$key]);
         }
         }
         
         // make sure the application default paths are still in the array
         $this->_ci_library_paths = array_unique(array_merge($this->_ci_library_paths, array(APPPATH, BASEPATH)));
         $this->_ci_helper_paths = array_unique(array_merge($this->_ci_helper_paths, array(APPPATH, BASEPATH)));
         $this->_ci_model_paths = array_unique(array_merge($this->_ci_model_paths, array(APPPATH)));
         $this->_ci_view_paths = array_merge($this->_ci_view_paths, array(APPPATH.'views/' => TRUE));
         $config->_config_paths = array_unique(array_merge($config->_config_paths, array(APPPATH)));
         
         return $this;
         */
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Internal CI Data Loader
     *
     * Used to load views and files.
     *
     * Variables are prefixed with _ci_ to avoid symbol collision with
     * variables made available to view files.
     *
     * @used-by CI_Loader::view()
     * @used-by CI_Loader::file()
     * @param   array   $_ci_data   Data to load
     * @return  object
     */
    protected function _ci_load($_ci_data)
    {
        // Set the default data variables
        foreach (array('_ci_view', 'vars', '_ci_path', '_ci_return') as $_ci_val)
        {
            $$_ci_val = isset($_ci_data[$_ci_val]) ? $_ci_data[$_ci_val] : FALSE;
        }
        
        $file_exists = FALSE;
        
        // Set the path to the requested file
        if (is_string($_ci_path) && $_ci_path !== '')
        {
            $_ci_x = explode('/', $_ci_path);
            $_ci_file = end($_ci_x);
        }
        else
        {
            $_ci_ext = pathinfo($_ci_view, PATHINFO_EXTENSION);
            $_ci_file = ($_ci_ext === '') ? $_ci_view.EXT : $_ci_view;
            
            foreach ($this->_ci_view_paths as $_ci_view_file => $cascade)
            {
                if (file_exists($_ci_view_file.$_ci_file))
                {
                    $_ci_path = $_ci_view_file.$_ci_file;
                    $file_exists = TRUE;
                    break;
                }
                
                if ( ! $cascade)
                {
                    break;
                }
            }
        }
        
        if ( ! is_readable($_ci_path))
        {
            $_ci_path = CPCORE . 'Views/' . $_ci_file;
            if ( ! is_readable($_ci_path))
            {
                show_error('Unable to load the requested file: '.$_ci_file);
            }
        }
        
        // This allows anything loaded using $this->load (views, files, etc.)
        // to become accessible from within the Controller and Model functions.
        $_ci_CI =& get_instance();
        foreach (get_object_vars($_ci_CI) as $_ci_key => $_ci_var)
        {
            if ( ! isset($this->$_ci_key))
            {
                $this->$_ci_key =& $_ci_CI->$_ci_key;
            }
        }
        
        /*
         * Extract and cache variables
         *
         * You can either set variables using the dedicated $this->load->vars()
         * function or via the second parameter of this function. We'll merge
         * the two types and cache them so that views that are embedded within
         * other views can have access to these variables.
         */
        empty($vars) OR $this->_ci_cached_vars = array_merge($this->_ci_cached_vars, $vars);
        extract($this->_ci_cached_vars);
        
        /*
         * Buffer the output
         *
         * We buffer the output for two reasons:
         * 1. Speed. You get a significant speed boost.
         * 2. So that the final rendered template can be post-processed by
         *  the output class. Why do we need post processing? For one thing,
         *  in order to show the elapsed page load time. Unless we can
         *  intercept the content right before it's sent to the browser and
         *  then stop the timer it won't be accurate.
         */
        ob_start();
        
        // If the PHP installation does not support short tags we'll
        // do a little string replacement, changing the short tags
        // to standard PHP echo statements.
        if ( ! is_php('5.4') && ! ini_get('short_open_tag') && config_item('rewrite_short_tags') === TRUE)
        {
            echo eval('?>'.preg_replace('/;*\s*\?>/', '; ?>', str_replace('<?=', '<?php echo ', file_get_contents($_ci_path))));
        }
        else
        {
            include($_ci_path); // include() vs include_once() allows for multiple views with the same name
        }
        
        log_message('info', 'File loaded: '.$_ci_path);
        
        // Return the file data if requested
        if ($_ci_return === TRUE)
        {
            $buffer = ob_get_contents();
            @ob_end_clean();
            return $buffer;
        }
        
        /*
         * Flush the buffer... or buff the flusher?
         *
         * In order to permit views to be nested within
         * other views, we need to flush the content back out whenever
         * we are beyond the first level of output buffering so that
         * it can be seen and included properly by the first included
         * template and any subsequent ones. Oy!
         */
        if (ob_get_level() > $this->_ci_ob_level + 1)
        {
            ob_end_flush();
        }
        else
        {
            $_ci_CI->output->append_output(ob_get_contents());
            @ob_end_clean();
        }
        
        return $this;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Internal CI Library Loader
     *
     * @used-by CI_Loader::library()
     * @uses    CI_Loader::_ci_init_library()
     *
     * @param   string  $class      Class name to load
     * @param   mixed   $params     Optional parameters to pass to the class constructor
     * @param   string  $object_name    Optional object name to assign to
     * @return  void
     */
    protected function _ci_load_library($class, $params = NULL, $object_name = NULL)
    {
        //vasanth commented as not required for CP
        /*
         // Get the class name, and while we're at it trim any slashes.
         // The directory path can be included as part of the class name,
         // but we don't want a leading slash
         $class = str_replace('.php', '', trim($class, '/'));
         
         // Was the path included with the class name?
         // We look for a slash to determine this
         if (($last_slash = strrpos($class, '/')) !== FALSE)
         {
         // Extract the path
         $subdir = substr($class, 0, ++$last_slash);
         
         // Get the filename from the path
         $class = substr($class, $last_slash);
         }
         else
         {
         $subdir = '';
         }
         
         $class = ucfirst($class);
         
         // Is this a stock library? There are a few special conditions if so ...
         if (file_exists(BASEPATH.'libraries/'.$subdir.$class.'.php'))
         {
         return $this->_ci_load_stock_library($class, $subdir, $params, $object_name);
         }
         
         // Safety: Was the class already loaded by a previous call?
         if (class_exists($class, FALSE))
         {
         $property = $object_name;
         if (empty($property))
         {
         $property = strtolower($class);
         isset($this->_ci_varmap[$property]) && $property = $this->_ci_varmap[$property];
         }
         
         $CI =& get_instance();
         if (isset($CI->$property))
         {
         log_message('debug', $class.' class already loaded. Second attempt ignored.');
         return;
         }
         
         return $this->_ci_init_library($class, '', $params, $object_name);
         }
         
         // Let's search for the requested library file and load it.
         foreach ($this->_ci_library_paths as $path)
         {
         // BASEPATH has already been checked for
         if ($path === BASEPATH)
         {
         continue;
         }
         
         $filepath = $path.'libraries/'.$subdir.$class.'.php';
         // Does the file exist? No? Bummer...
         if ( ! file_exists($filepath))
         {
         continue;
         }
         
         include_once($filepath);
         return $this->_ci_init_library($class, '', $params, $object_name);
         }
         
         // One last attempt. Maybe the library is in a subdirectory, but it wasn't specified?
         if ($subdir === '')
         {
         return $this->_ci_load_library($class.'/'.$class, $params, $object_name);
         }
         
         // If we got this far we were unable to find the requested class.
         log_message('error', 'Unable to load the requested class: '.$class);
         show_error('Unable to load the requested class: '.$class);
         */
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Internal CI Stock Library Loader
     *
     * @used-by CI_Loader::_ci_load_library()
     * @uses    CI_Loader::_ci_init_library()
     *
     * @param   string  $library_name   Library name to load
     * @param   string  $file_path  Path to the library filename, relative to libraries/
     * @param   mixed   $params     Optional parameters to pass to the class constructor
     * @param   string  $object_name    Optional object name to assign to
     * @return  void
     */
    protected function _ci_load_stock_library($library_name, $file_path, $params, $object_name)
    {
        //vasanth commented as not required for CP
        /*
         $prefix = 'CI_';
         
         if (class_exists($prefix.$library_name, FALSE))
         {
         if (class_exists(config_item('subclass_prefix').$library_name, FALSE))
         {
         $prefix = config_item('subclass_prefix');
         }
         
         $property = $object_name;
         if (empty($property))
         {
         $property = strtolower($library_name);
         isset($this->_ci_varmap[$property]) && $property = $this->_ci_varmap[$property];
         }
         
         $CI =& get_instance();
         if ( ! isset($CI->$property))
         {
         return $this->_ci_init_library($library_name, $prefix, $params, $object_name);
         }
         
         log_message('debug', $library_name.' class already loaded. Second attempt ignored.');
         return;
         }
         
         $paths = $this->_ci_library_paths;
         array_pop($paths); // BASEPATH
         array_pop($paths); // APPPATH (needs to be the first path checked)
         array_unshift($paths, APPPATH);
         
         foreach ($paths as $path)
         {
         if (file_exists($path = $path.'libraries/'.$file_path.$library_name.'.php'))
         {
         // Override
         include_once($path);
         if (class_exists($prefix.$library_name, FALSE))
         {
         return $this->_ci_init_library($library_name, $prefix, $params, $object_name);
         }
         
         log_message('debug', $path.' exists, but does not declare '.$prefix.$library_name);
         }
         }
         
         include_once(BASEPATH.'libraries/'.$file_path.$library_name.'.php');
         
         // Check for extensions
         $subclass = config_item('subclass_prefix').$library_name;
         foreach ($paths as $path)
         {
         if (file_exists($path = $path.'libraries/'.$file_path.$subclass.'.php'))
         {
         include_once($path);
         if (class_exists($subclass, FALSE))
         {
         $prefix = config_item('subclass_prefix');
         break;
         }
         
         log_message('debug', $path.' exists, but does not declare '.$subclass);
         }
         }
         
         return $this->_ci_init_library($library_name, $prefix, $params, $object_name);
         */
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Internal CI Library Instantiator
     *
     * @used-by CI_Loader::_ci_load_stock_library()
     * @used-by CI_Loader::_ci_load_library()
     *
     * @param   string      $class      Class name
     * @param   string      $prefix     Class name prefix
     * @param   array|null|bool $config     Optional configuration to pass to the class constructor:
     *                      FALSE to skip;
     *                      NULL to search in config paths;
     *                      array containing configuration data
     * @param   string      $object_name    Optional object name to assign to
     * @return  void
     */
    protected function _ci_init_library($class, $prefix, $config = FALSE, $object_name = NULL)
    {
        //vasanth commented as not required for CP
        /*
         // Is there an associated config file for this class? Note: these should always be lowercase
         if ($config === NULL)
         {
         // Fetch the config paths containing any package paths
         $config_component = $this->_ci_get_component('config');
         
         if (is_array($config_component->_config_paths))
         {
         $found = FALSE;
         foreach ($config_component->_config_paths as $path)
         {
         // We test for both uppercase and lowercase, for servers that
         // are case-sensitive with regard to file names. Load global first,
         // override with environment next
         if (file_exists($path.'config/'.strtolower($class).'.php'))
         {
         include($path.'config/'.strtolower($class).'.php');
         $found = TRUE;
         }
         elseif (file_exists($path.'config/'.ucfirst(strtolower($class)).'.php'))
         {
         include($path.'config/'.ucfirst(strtolower($class)).'.php');
         $found = TRUE;
         }
         
         if (file_exists($path.'config/'.ENVIRONMENT.'/'.strtolower($class).'.php'))
         {
         include($path.'config/'.ENVIRONMENT.'/'.strtolower($class).'.php');
         $found = TRUE;
         }
         elseif (file_exists($path.'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php'))
         {
         include($path.'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php');
         $found = TRUE;
         }
         
         // Break on the first found configuration, thus package
         // files are not overridden by default paths
         if ($found === TRUE)
         {
         break;
         }
         }
         }
         }
         
         $class_name = $prefix.$class;
         
         // Is the class name valid?
         if ( ! class_exists($class_name, FALSE))
         {
         log_message('error', 'Non-existent class: '.$class_name);
         show_error('Non-existent class: '.$class_name);
         }
         
         // Set the variable name we will assign the class to
         // Was a custom class name supplied? If so we'll use it
         if (empty($object_name))
         {
         $object_name = strtolower($class);
         if (isset($this->_ci_varmap[$object_name]))
         {
         $object_name = $this->_ci_varmap[$object_name];
         }
         }
         
         // Don't overwrite existing properties
         $CI =& get_instance();
         if (isset($CI->$object_name))
         {
         if ($CI->$object_name instanceof $class_name)
         {
         log_message('debug', $class_name." has already been instantiated as '".$object_name."'. Second attempt aborted.");
         return;
         }
         
         show_error("Resource '".$object_name."' already exists and is not a ".$class_name." instance.");
         }
         
         // Save the class name and object name
         $this->_ci_classes[$object_name] = $class;
         
         // Instantiate the class
         $CI->$object_name = isset($config)
         ? new $class_name($config)
         : new $class_name();
         
         */
        
    }
    
    // --------------------------------------------------------------------
    
    /**
     * CI Autoloader
     *
     * Loads component listed in the config/autoload.php file.
     *
     * @used-by CI_Loader::initialize()
     * @return  void
     */
    protected function _ci_autoloader()
    {
        //vasanth commented as not required for CP
        /*
         if (file_exists(APPPATH.'config/autoload.php'))
         {
         include(APPPATH.'config/autoload.php');
         }
         
         if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/autoload.php'))
         {
         include(APPPATH.'config/'.ENVIRONMENT.'/autoload.php');
         }
         
         if ( ! isset($autoload))
         {
         return;
         }
         
         // Autoload packages
         if (isset($autoload['packages']))
         {
         foreach ($autoload['packages'] as $package_path)
         {
         $this->add_package_path($package_path);
         }
         }
         
         // Load any custom config file
         if (count($autoload['config']) > 0)
         {
         foreach ($autoload['config'] as $val)
         {
         $this->config($val);
         }
         }
         
         // Autoload helpers and languages
         foreach (array('helper', 'language') as $type)
         {
         if (isset($autoload[$type]) && count($autoload[$type]) > 0)
         {
         $this->$type($autoload[$type]);
         }
         }
         
         // Autoload drivers
         if (isset($autoload['drivers']))
         {
         $this->driver($autoload['drivers']);
         }
         
         // Load libraries
         if (isset($autoload['libraries']) && count($autoload['libraries']) > 0)
         {
         // Load the database driver.
         if (in_array('database', $autoload['libraries']))
         {
         $this->database();
         $autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
         }
         
         // Load all other libraries
         $this->library($autoload['libraries']);
         }
         
         // Autoload models
         if (isset($autoload['model']))
         {
         $this->model($autoload['model']);
         }
         */
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Prepare variables for vars, to be later extract()-ed inside views
     *
     * Converts objects to associative arrays and filters-out internal
     * variable names (i.e. keys prefixed with '_ci_').
     *
     * @param   mixed   $vars
     * @return  array
     */
    protected function _ci_prepare_view_vars($vars)
    {
        if ( ! is_array($vars))
        {
            $vars = is_object($vars)
            ? get_object_vars($vars)
            : array();
        }
        
        //vasanth commented as not required for CP
        /*
         foreach (array_keys($vars) as $key)
         {
         if (strncmp($key, '_ci_', 4) === 0)
         {
         unset($vars[$key]);
         }
         }
         */
        
        return $vars;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * CI Component getter
     *
     * Get a reference to a specific library or model.
     *
     * @param   string  $component  Component name
     * @return  bool
     */
    protected function &_ci_get_component($component)
    {
        $CI =& get_instance();
        return $CI->$component;
    }
}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application Controller Class
 *
 * This class object is the super class that every library in
 * CodeIgniter will be assigned to.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/general/controllers.html
 */
#[\AllowDynamicProperties]
class CI_Controller {

    /**
     * Reference to the CI singleton
     *
     * @var	object
     */
    private static $instance;
    
    /**
     * CI_Loader
     *
     * @var	CI_Loader
     */
    public $load;

    /**
     * CI Objects
     */
    public $loader;
    public $hooks;
    public $config;
    public $uri;
    public $router;
    public $output;
    public $security;
    public $input;

    /**
     * CP specific objects loaded as part of CI
     */
    public $rnow;
    public $themes;
    public $agent;
    public $cpwrapper;
    public $session;
    public $developmentHeader;

    /**
     * Class constructor
     *
     * @return	void
     */
    public function __construct()
    {
        if (self::$instance === null) {
            self::$instance =& $this;
        }
    
    	// Assign all the class objects that were instantiated by the
    	// bootstrap file (CodeIgniter.php) to local class variables
    	// so that CI can run as one big super object.
    	foreach (is_loaded() as $var => $class)
    	{
            if (IS_UNITTEST && (stripos($class, 'themes') !== false || stripos($class, 'rnow') !== false  || stripos($class, 'rntphpwrapper') !== false )) {
                continue;
            }
    		$this->$var =& load_class($class);
    	}
    	
    	//addtional classes to load
    	$classes = array(
    	    'agent' => 'User_agent',
            'cpwrapper' => 'Rntphpwrapper'
    	);
    	
    	foreach ($classes as $var => $class)
    	{
    	    $this->$var =& load_class($class);
    	}
    
    	$this->load =& load_class('Loader', 'core');
    	$this->load->initialize();
    	log_message('info', 'Controller Class Initialized');
    }

	// --------------------------------------------------------------------

    /**
     * Get the CI singleton
     *
     * @static
     * @return	object
     */
    public static function &get_instance()
    {
        return self::$instance;
    }

}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Exceptions Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Exceptions
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/libraries/exceptions.html
 */
class CI_Exceptions {

    /**
     * Nesting level of the output buffering mechanism
     *
     * @var int
     */
    public $ob_level;

    /**
     * List of available error levels
     *
     * @var array
     */
    public $levels = array(
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Runtime Notice'
    );

    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->ob_level = ob_get_level();
        // Note: Do not log messages from this constructor.
    }

    // --------------------------------------------------------------------
    
    /**
     * Exception Logger
     *
     * Logs PHP generated error messages
     *
     * @param	int	$severity	Log level
     * @param	string	$message	Error message
     * @param	string	$filepath	File path
     * @param	int	$line		Line number
     * @return	void
     */
    public function log_exception($severity, $message, $filepath, $line)
    {
        $severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;
        log_message('error', 'Severity: '.$severity.' --> '.$message.' '.$filepath.' '.$line);
    }

        // --------------------------------------------------------------------

    /**
     * 404 Error Handler
     *
     * @uses CI_Exceptions::show_error()
     *      
     * @param string $page
     *            Page URI
     * @param bool $log_error
     *            Whether to log the error
     * @return void
     */
    public function show_404($page = '', $log_error = TRUE)
    {
        $heading = '404 Page Not Found';
        $message = 'The page you requested was not found.';

        if ($page === '' && $_SERVER['REQUEST_URI'] != '') {
            $page = $_SERVER['REQUEST_URI'];
        }

        // By default we log this, but allow a dev to skip it
        if ($log_error) {
            log_message('error', $heading . ': ' . $page);
        }

        echo $this->show_error($heading, $message, 'error_404', htmlspecialchars($page));
        exit(); // EXIT_UNKNOWN_FILE
    }

    // --------------------------------------------------------------------

    /**
     * General Error Page
     *
     * Takes an error message as input (either as a string or an array)
     * and displays it using the specified template.
     *
     * @param string $heading
     *            Page heading
     * @param string|string[] $message
     *            Error message
     * @param string $template
     *            Template name
     * @param string $status_code
     *            (default: 500)
     *            
     * @return string Error page output
     */
    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        if (IS_HOSTED && IS_OPTIMIZED) {
            return;
        }

        $message = '<p>' . (is_array($message) ? implode('</p><p>', $message) : $message) . '</p>';

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }
        ob_start();
        include (APPPATH . 'errors/' . $template . EXT);
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

    // --------------------------------------------------------------------
    public function show_exception($exception)
    {
        if (IS_HOSTED && IS_OPTIMIZED) {
            return;
        }

        $templates_path = APPPATH . 'errors' . DIRECTORY_SEPARATOR;
        $message = $exception->getMessage();
        if (empty($message)) {
            $message = '(null)';
        }

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        ob_start();
        include ($templates_path . 'error_exception' . EXT);
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }

    // --------------------------------------------------------------------

    /**
     * Native PHP error handler
     *
     * @param int $severity
     *            Error level
     * @param string $message
     *            Error message
     * @param string $filepath
     *            File path
     * @param int $line
     *            Line number
     * @return void
     */
    public function show_php_error($severity, $message, $filepath, $line)
    {
        if (IS_HOSTED && IS_OPTIMIZED) {
            return;
        }

        $templates_path = APPPATH . 'errors' . DIRECTORY_SEPARATOR;
        $severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;

        // For safety reasons we don't show the full file path in non-CLI requests
        $filepath = str_replace('\\', '/', $filepath);
        if (FALSE !== strpos($filepath, '/')) {
            $x = explode('/', $filepath);
            $filepath = $x[count($x) - 2] . '/' . end($x);
        }
        $template = 'error_php';

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }
        ob_start();
        include ($templates_path . $template . EXT);
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }

}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Security Class
 *
 * @package CodeIgniter
 * @subpackage Libraries
 * @category Security
 * @author EllisLab Dev Team
 * @link https://codeigniter.com/userguide3/libraries/security.html
 */
class CI_Security
{

    /**
     * List of sanitize filename strings
     *
     * @var array
     */
    public $filename_bad_chars = array(
        '../',
        '<!--',
        '-->',
        '<',
        '>',
        "'",
        '"',
        '&',
        '$',
        '#',
        '{',
        '}',
        '[',
        ']',
        '=',
        ';',
        '?',
        '%20',
        '%22',
        '%3c', // <
        '%253c', // <
        '%3e', // >
        '%0e', // >
        '%28', // (
        '%29', // )
        '%2528', // (
        '%26', // &
        '%24', // $
        '%3f', // ?
        '%3b', // ;
        '%3d' // =
    );

    /**
     * Character set
     *
     * Will be overridden by the constructor.
     *
     * @var string
     */
    public $charset = 'UTF-8';

    /**
     * XSS Hash
     *
     * Random Hash for protecting URLs.
     *
     * @var string
     */
    protected $_xss_hash;

    /**
     * CSRF Hash
     *
     * Random hash for Cross Site Request Forgery protection cookie
     *
     * @var string
     */
    protected $_csrf_hash;

    /**
     * CSRF Expire time
     *
     * Expiration time for Cross Site Request Forgery protection cookie.
     * Defaults to two hours (in seconds).
     *
     * @var int
     */
    protected $_csrf_expire = 7200;

    /**
     * CSRF Token name
     *
     * Token name for Cross Site Request Forgery protection cookie.
     *
     * @var string
     */
    protected $_csrf_token_name = 'ci_csrf_token';

    /**
     * CSRF Cookie name
     *
     * Cookie name for Cross Site Request Forgery protection cookie.
     *
     * @var string
     */
    protected $_csrf_cookie_name = 'ci_csrf_token';

    /**
     * List of never allowed strings
     *
     * @var array
     */
    protected $_never_allowed_str = array(
        'document.cookie' => '[removed]',
        '(document).cookie' => '[removed]',
        'document.write' => '[removed]',
        '(document).write' => '[removed]',
        '.parentNode' => '[removed]',
        '.innerHTML' => '[removed]',
        '-moz-binding' => '[removed]',
        '<!--' => '&lt;!--',
        '-->' => '--&gt;',
        '<![CDATA[' => '&lt;![CDATA[',
        '<comment>' => '&lt;comment&gt;',
        '<%' => '&lt;&#37;'
    );

    /**
     * List of never allowed regex replacements
     *
     * @var array
     */
    protected $_never_allowed_regex = array(
        'javascript\s*:',
        '(\(?document\)?|\(?window\)?(\.document)?)\.(location|on\w*)',
        'expression\s*(\(|&\#40;)', // CSS and IE
        'vbscript\s*:', // IE, surprise!
        'wscript\s*:', // IE
        'jscript\s*:', // IE
        'vbs\s*:', // IE
        'Redirect\s+30\d',
        "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
    );

    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->charset = strtoupper((string) config_item('charset'));

        log_message('info', 'Security Class Initialized');
    }

    // --------------------------------------------------------------------

    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented. This method does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts. Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: Should only be used to deal with data upon submission.
     * It's not something that should be used for general
     * runtime processing.
     *
     * @link http://channel.bitflux.ch/wiki/XSS_Prevention
     *       Based in part on some code and ideas from Bitflux.
     *      
     * @link http://ha.ckers.org/xss.html
     *       To help develop this script I used this great list of
     *       vulnerabilities along with a few other hacks I've
     *       harvested from examining vulnerabilities in other programs.
     *      
     * @param string|string[] $str
     *            Input data
     * @param bool $is_image
     *            Whether the input is an image
     * @return string
     */
    public function xss_clean($str, $is_image = FALSE)
    {
        // Is the string an array?
        if (is_array($str)) {
            foreach ($str as $key => &$value) {
                $str[$key] = $this->xss_clean($value);
            }

            return $str;
        }

        // Remove Invisible Characters
        $str = remove_invisible_characters($str);

        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Use rawurldecode() so it does not remove plus signs
         */
        if (stripos($str, '%') !== false) {
            do {
                $oldstr = $str;
                $str = rawurldecode($str);
                $str = preg_replace_callback('#%(?:\s*[0-9a-f]){2,}#i', array(
                    $this,
                    '_urldecodespaces'
                ), $str);
            } while ($oldstr !== $str);
            unset($oldstr);
        }

        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         */
        $str = preg_replace_callback("/[^a-z0-9>]+[a-z0-9]+=([\'\"]).*?\\1/si", array(
            $this,
            '_convert_attribute'
        ), $str);
        $str = preg_replace_callback('/<\w+.*/si', array(
            $this,
            '_decode_entity'
        ), $str);

        // Remove Invisible Characters Again!
        $str = remove_invisible_characters($str);

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on
         * large blocks of data, so we use str_replace.
         */
        $str = str_replace("\t", ' ', $str);

        // Capture converted string for later comparison
        $converted_string = $str;

        // Remove Strings that are never allowed
        $str = $this->_do_never_allowed($str);

        /*
         * Makes PHP tags safe
         *
         * Note: XML tags are inadvertently replaced too:
         *
         * <?xml
         *
         * But it doesn't seem to pose a problem.
         */
        if ($is_image === TRUE) {
            // Images have a tendency to have the PHP short opening and
            // closing tags every so often so we skip those and only
            // do the long opening tags.
            $str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
        } else {
            $str = str_replace(array(
                '<?',
                '?' . '>'
            ), array(
                '&lt;?',
                '?&gt;'
            ), $str);
        }

        /*
         * Compact any exploded words
         *
         * This corrects words like: j a v a s c r i p t
         * These words are compacted back to their correct state.
         */
        $words = array(
            'javascript',
            'expression',
            'vbscript',
            'jscript',
            'wscript',
            'vbs',
            'script',
            'base64',
            'applet',
            'alert',
            'document',
            'write',
            'cookie',
            'window',
            'confirm',
            'prompt',
            'eval'
        );

        foreach ($words as $word) {
            $word = implode('\s*', str_split($word)) . '\s*';

            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback('#(' . substr($word, 0, - 3) . ')(\W)#is', array(
                $this,
                '_compact_exploded_words'
            ), $str);
        }

        /*
         * Remove disallowed Javascript in links or img tags
         * We used to do some version comparisons and use of stripos(),
         * but it is dog slow compared to these simplified non-capturing
         * preg_match(), especially if the pattern exists in the string
         *
         * Note: It was reported that not only space characters, but all in
         * the following pattern can be parsed as separators between a tag name
         * and its attributes: [\d\s"\'`;,\/\=\(\x00\x0B\x09\x0C]
         * ... however, remove_invisible_characters() above already strips the
         * hex-encoded ones, so we'll skip them below.
         */
        do {
            $original = $str;

            if (preg_match('/<a/i', $str)) {
                $str = preg_replace_callback('#<a(?:rea)?[^a-z0-9>]+([^>]*?)(?:>|$)#si', array(
                    $this,
                    '_js_link_removal'
                ), $str);
            }

            if (preg_match('/<img/i', $str)) {
                $str = preg_replace_callback('#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si', array(
                    $this,
                    '_js_img_removal'
                ), $str);
            }

            if (preg_match('/script|xss/i', $str)) {
                $str = preg_replace('#</*(?:script|xss).*?>#si', '[removed]', $str);
            }
        } while ($original !== $str);
        unset($original);

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         */
        $pattern = '#' . '<((?<slash>/*\s*)((?<tagName>[a-z0-9]+)(?=[^a-z0-9]|$)|.+)' . // tag start and name, followed by a non-tag character
        '[^\s\042\047a-z0-9>/=]*' . // a valid attribute character immediately after the tag would count as a separator
                                      // optional attributes
        '(?<attributes>(?:[\s\042\047/=]*' . // non-attribute characters, excluding > (tag close) for obvious reasons
        '[^\s\042\047>/=]+' . // attribute characters
                                // optional attribute-value
        '(?:\s*=' . // attribute-value separator
        '(?:[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*))' . // single, double or non-quoted value
        ')?' . // end optional attribute-value group
        ')*)' . // end optional attributes group
        '[^>]*)(?<closeTag>\>)?#isS';

        // Note: It would be nice to optimize this for speed, BUT
        // only matching the naughty elements here results in
        // false positives and in turn - vulnerabilities!
        do {
            $old_str = $str;
            $str = preg_replace_callback($pattern, array(
                $this,
                '_sanitize_naughty_html'
            ), $str);
        } while ($old_str !== $str);
        unset($old_str);

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed. Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example: eval('some code')
         * Becomes: eval&#40;'some code'&#41;
         */
        $str = preg_replace('#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', '\\1\\2&#40;\\3&#41;', $str);

        // Same thing, but for "tag functions" (e.g. eval`some code`)
        // See https://github.com/bcit-ci/CodeIgniter/issues/5420
        $str = preg_replace('#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)`(.*?)`#si', '\\1\\2&#96;\\3&#96;', $str);

        // Final clean up
        // This adds a bit of extra precaution in case
        // something got through the above filters
        $str = $this->_do_never_allowed($str);

        /*
         * Images are Handled in a Special Way
         * - Essentially, we want to know that after all of the character
         * conversion is done whether any unwanted, likely XSS, code was found.
         * If not, we return TRUE, as the image is clean.
         * However, if the string post-conversion does not matched the
         * string post-removal of XSS, then it fails, as there was unwanted XSS
         * code found and removed/changed during processing.
         */
        if ($is_image === TRUE) {
            return ($str === $converted_string);
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * XSS Hash
     *
     * Generates the XSS hash if needed and returns it.
     *
     * @see CI_Security::$_xss_hash
     * @return string XSS hash
     */
    public function xss_hash()
    {
        if ($this->_xss_hash === NULL) {
            $rand = FALSE;
            $this->_xss_hash = ($rand === FALSE) ? md5(uniqid(mt_rand(), TRUE)) : bin2hex($rand);
        }

        return $this->_xss_hash;
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entities Decode
     *
     * A replacement for html_entity_decode()
     *
     * The reason we are not using html_entity_decode() by itself is because
     * while it is not technically correct to leave out the semicolon
     * at the end of an entity most browsers will still interpret the entity
     * correctly. html_entity_decode() does not convert entities without
     * semicolons, so we are left with our own little solution here. Bummer.
     *
     * @link http://php.net/html-entity-decode
     *      
     * @param string $str
     *            Input
     * @param string $charset
     *            Character set
     * @return string
     */
    public function entity_decode($str, $charset = NULL)
    {
        if (strpos($str, '&') === FALSE) {
            return $str;
        }

        static $_entities;

        isset($charset) or $charset = $this->charset;
        $flag = is_php('5.4') ? ENT_COMPAT | ENT_HTML5 : ENT_COMPAT;

        if (! isset($_entities)) {
            $_entities = array_map('strtolower', get_html_translation_table(HTML_ENTITIES, $flag, $charset));

            // If we're not on PHP 5.4+, add the possibly dangerous HTML 5
            // entities to the array manually
            if ($flag === ENT_COMPAT) {
                $_entities[':'] = '&colon;';
                $_entities['('] = '&lpar;';
                $_entities[')'] = '&rpar;';
                $_entities["\n"] = '&NewLine;';
                $_entities["\t"] = '&Tab;';
            }
        }

        do {
            $str_compare = $str;

            // Decode standard entities, avoiding false positives
            if (preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches)) {
                $replace = array();
                $matches = array_unique(array_map('strtolower', $matches[0]));
                foreach ($matches as &$match) {
                    if (($char = array_search($match . ';', $_entities, TRUE)) !== FALSE) {
                        $replace[$match] = $char;
                    }
                }

                $str = str_replace(array_keys($replace), array_values($replace), $str);
            }

            // Decode numeric & UTF16 two byte entities
            $str = html_entity_decode(preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str), $flag, $charset);

            if ($flag === ENT_COMPAT) {
                $str = str_replace(array_values($_entities), array_keys($_entities), $str);
            }
        } while ($str_compare !== $str);
        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Sanitize Filename
     *
     * @param string $str
     *            Input file name
     * @param bool $relative_path
     *            Whether to preserve paths
     * @return string
     */
    public function sanitize_filename($str, $relative_path = FALSE)
    {
        $bad = $this->filename_bad_chars;

        if (! $relative_path) {
            $bad[] = './';
            $bad[] = '/';
        }

        $str = remove_invisible_characters($str, FALSE);

        do {
            $old = $str;
            $str = str_replace($bad, '', $str);
        } while ($old !== $str);

        return stripslashes($str);
    }

    // ----------------------------------------------------------------

    /**
     * Strip Image Tags
     *
     * @param string $str
     * @return string
     */
    public function strip_image_tags($str)
    {
        return preg_replace(array(
            '#<img[\s/]+.*?src\s*=\s*(["\'])([^\\1]+?)\\1.*?\>#i',
            '#<img[\s/]+.*?src\s*=\s*?(([^\s"\'=<>`]+)).*?\>#i'
        ), '\\2', $str);
    }

    // ----------------------------------------------------------------

    /**
     * URL-decode taking spaces into account
     *
     * @see https://github.com/bcit-ci/CodeIgniter/issues/4877
     * @param array $matches
     * @return string
     */
    protected function _urldecodespaces($matches)
    {
        $input = $matches[0];
        $nospaces = preg_replace('#\s+#', '', $input);
        return ($nospaces === $input) ? $input : rawurldecode($nospaces);
    }

    // ----------------------------------------------------------------

    /**
     * Compact Exploded Words
     *
     * Callback method for xss_clean() to remove whitespace from
     * things like 'j a v a s c r i p t'.
     *
     * @used-by	CI_Security::xss_clean()
     * @param array $matches
     * @return string
     */
    protected function _compact_exploded_words($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]) . $matches[2];
    }

    // --------------------------------------------------------------------

    /**
     * Sanitize Naughty HTML
     *
     * Callback method for xss_clean() to remove naughty HTML elements.
     *
     * @used-by	CI_Security::xss_clean()
     * @param array $matches
     * @return string
     */
    protected function _sanitize_naughty_html($matches)
    {
        static $naughty_tags = array(
            'alert',
            'area',
            'prompt',
            'confirm',
            'applet',
            'audio',
            'basefont',
            'base',
            'behavior',
            'bgsound',
            'blink',
            'body',
            'embed',
            'expression',
            'form',
            'frameset',
            'frame',
            'head',
            'html',
            'ilayer',
            'iframe',
            'input',
            'button',
            'select',
            'isindex',
            'layer',
            'link',
            'meta',
            'keygen',
            'object',
            'plaintext',
            'style',
            'script',
            'textarea',
            'title',
            'math',
            'video',
            'svg',
            'xml',
            'xss'
        );

        static $evil_attributes = array(
            'on\w+',
            'style',
            'xmlns',
            'formaction',
            'form',
            'xlink:href',
            'FSCommand',
            'seekSegmentTime'
        );

        // First, escape unclosed tags
        if (empty($matches['closeTag'])) {
            return '&lt;' . $matches[1];
        } // Is the element that we caught naughty? If so, escape it
        elseif (in_array(strtolower($matches['tagName']), $naughty_tags, TRUE)) {
            return '&lt;' . $matches[1] . '&gt;';
        } // For other tags, see if their attributes are "evil" and strip those
        elseif (isset($matches['attributes'])) {
            // We'll store the already filtered attributes here
            $attributes = array();

            // Attribute-catching pattern
            $attributes_pattern = '#' . '(?<name>[^\s\042\047>/=]+)' . // attribute characters
                                                                         // optional attribute-value
            '(?:\s*=(?<value>[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*)))' . // attribute-value separator
            '#i';

            // Blacklist pattern for evil attribute names
            $is_evil_pattern = '#^(' . implode('|', $evil_attributes) . ')$#i';

            // Each iteration filters a single attribute
            do {
                // Strip any non-alpha characters that may precede an attribute.
                // Browsers often parse these incorrectly and that has been a
                // of numerous XSS issues we've had.
                $matches['attributes'] = preg_replace('#^[^a-z]+#i', '', $matches['attributes']);

                if (! preg_match($attributes_pattern, $matches['attributes'], $attribute, PREG_OFFSET_CAPTURE)) {
                    // No (valid) attribute found? Discard everything else inside the tag
                    break;
                }

                if (
                // Is it indeed an "evil" attribute?
                preg_match($is_evil_pattern, $attribute['name'][0]) or 
                // Or does it have an equals sign, but no value and not quoted? Strip that too!
                (trim($attribute['value'][0]) === '')) {
                    $attributes[] = 'xss=removed';
                } else {
                    $attributes[] = $attribute[0][0];
                }

                $matches['attributes'] = substr($matches['attributes'], $attribute[0][1] + strlen($attribute[0][0]));
            } while ($matches['attributes'] !== '');

            $attributes = empty($attributes) ? '' : ' ' . implode(' ', $attributes);
            return '<' . $matches['slash'] . $matches['tagName'] . $attributes . '>';
        }

        return $matches[0];
    }

    // --------------------------------------------------------------------

    /**
     * JS Link Removal
     *
     * Callback method for xss_clean() to sanitize links.
     *
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings.
     *
     * @used-by	CI_Security::xss_clean()
     * @param array $match
     * @return string
     */
    protected function _js_link_removal($match)
    {
        return str_replace($match[1], preg_replace('#href=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;|`|&\#96;)|javascript:|livescript:|mocha:|charset=|window\.|\(?document\)?\.|\.cookie|<script|<xss|d\s*a\s*t\s*a\s*:)#si', '', $this->_filter_attributes($match[1])), $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * JS Image Removal
     *
     * Callback method for xss_clean() to sanitize image tags.
     *
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings.
     *
     * @used-by	CI_Security::xss_clean()
     * @param array $match
     * @return string
     */
    protected function _js_img_removal($match)
    {
        return str_replace($match[1], preg_replace('#src=.*?(?:(?:alert|prompt|confirm|eval)(?:\(|&\#40;|`|&\#96;)|javascript:|livescript:|mocha:|charset=|window\.|\(?document\)?\.|\.cookie|<script|<xss|base64\s*,)#si', '', $this->_filter_attributes($match[1])), $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * Attribute Conversion
     *
     * @used-by	CI_Security::xss_clean()
     * @param array $match
     * @return string
     */
    protected function _convert_attribute($match)
    {
        return str_replace(array(
            '>',
            '<',
            '\\'
        ), array(
            '&gt;',
            '&lt;',
            '\\\\'
        ), $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * Filter Attributes
     *
     * Filters tag attributes for consistency and safety.
     *
     * @used-by	CI_Security::_js_img_removal()
     * @used-by	CI_Security::_js_link_removal()
     * @param string $str
     * @return string
     */
    protected function _filter_attributes($str)
    {
        $out = '';
        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches)) {
            foreach ($matches[0] as $match) {
                $out .= preg_replace('#/\*.*?\*/#s', '', $match);
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entity Decode Callback
     *
     * @used-by	CI_Security::xss_clean()
     * @param array $match
     * @return string
     */
    protected function _decode_entity($match)
    {
        // Protect GET variables in URLs
        // 901119URL5918AMP18930PROTECT8198
        $match = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-/]+)|i', $this->xss_hash() . '\\1=\\2', $match[0]);

        // Decode, then un-protect URL GET vars
        return str_replace($this->xss_hash(), '&', $this->entity_decode($match, $this->charset));
    }

    // --------------------------------------------------------------------

    /**
     * Do Never Allowed
     *
     * @used-by	CI_Security::xss_clean()
     * @param
     *            string
     * @return string
     */
    protected function _do_never_allowed($str)
    {
        $str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);

        foreach ($this->_never_allowed_regex as $regex) {
            $str = preg_replace('#' . $regex . '#is', '[removed]', $str);
        }

        return $str;
    }
}


/**
 * Selects which theme a page will be rendered with.
 */
class Themes {
    const standardThemePath = '/euf/assets/themes/standard';
    const mobileThemePath = '/euf/assets/themes/mobile';
    const basicThemePath = '/euf/assets/themes/basic';

    private $allowSettingTheme = true;
    private $theme;
    private $themePath;
    private $availableThemes;

    /**
     * Returns reference path to standard theme
     * @return string
     */
    public static function getReferenceThemePath() {
        return self::getSpecificReferencePath('standard');
    }

    /**
     * Returns reference path to mobile theme
     * @return string
     */
    public static function getReferenceMobileThemePath() {
        return self::getSpecificReferencePath('mobile');
    }

    /**
     * Returns reference path to basic theme
     * @return string
     */
    public static function getReferenceBasicThemePath() {
        return self::getSpecificReferencePath('basic');
    }

    /**
     * This function is intended for use by the Customer Portal framework.
     * @private
     */
    public function disableSettingTheme() {
        $this->allowSettingTheme = false;
    }

    /**
     * Selects which theme will be used.  Must be called in a pre_page_render hook.
     * @param $theme A string containing the value of the path attribute of an
     * rn:theme tag present in the page or template.
     */
    public function setTheme($theme)
    {
        if (!$this->allowSettingTheme) {
            if (IS_OPTIMIZED) {
                // Silently fail in production or staging.
                return;
            }
            throw new Exception(\RightNow\Utils\Config::getMessage(ATTEMPTED_SET_THEME_PRE_PG_RENDER_MSG));
        }

        if (!array_key_exists($theme, $this->availableThemes))
        {
            $availableThemes = $this->getAvailableThemes();
            if (count($availableThemes) > 0) {
                $message = sprintf(\RightNow\Utils\Config::getMessage(ATTEMPTED_SET_THEME_PCT_S_DECLARED_MSG), $theme);
                $message .= "<ul>";
                foreach ($availableThemes as $availableTheme) {
                    $message .= "<li>$availableTheme</li>";
                }
                $message .= "</ul>";
            }
            else {
                $message = sprintf(\RightNow\Utils\Config::getMessage(ATTEMPTED_SET_THEME_PCT_S_RN_THEME_MSG), $theme);
            }
            throw new Exception($message);
        }

        $this->theme = $theme;
        $this->themePath = $this->availableThemes[$theme];
    }

    /**
     * Gets the currently selected theme.
     *
     * The default value is the first theme declared on the page or, if the
     * page has no theme declared, the first theme on the template.  If no
     * rn:theme tag is present on the page or template, then the default is
     * '/euf/assets/themes/standard'.
     *
     * @returns A string containing the currently selected theme.
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Gets the URL path that the selected theme's assets are served from.
     *
     * The returned value does not include the URL's protocol or hostname.  In
     * development mode, this value will be the same as getTheme(); however, it
     * will differ in production mode.  On the filesystem, this path is
     * relative to the HTMLROOT define.
     *
     * @returns A string containing the URL path that the selected theme's assets are served from.
     */
    public function getThemePath()
    {
        return $this->themePath;
    }

    /**
     * Lists the themes which were declared on the page or template.
     *
     * Values returned are similar to getTheme().
     *
     * @returns An array of strings containing the value of path attribute of the rn:theme tags on the page and template.
     */
    public function getAvailableThemes()
    {
        return array_keys($this->availableThemes);
    }

    /**
     * This function is intended for use by the Customer Portal framework.
     * @private
     */
    public function setRuntimeThemeData($runtimeThemeData)
    {
        assert(is_string($runtimeThemeData[0]));
        assert(is_string($runtimeThemeData[1]));
        assert(is_array($runtimeThemeData[2]));
        list($this->theme, $this->themePath, $this->availableThemes) = $runtimeThemeData;
    }

    /**
     * Utility method to retrieve path to reference mode theme, provided the theme name
     * @param string $themeName Name of theme to retrieve
     * @return string Path to reference theme assets
     */
    private static function getSpecificReferencePath($themeName){
        $localThemeVariable = "{$themeName}ThemePath";
        return IS_HOSTED ? '/euf/core/' . CP_FRAMEWORK_VERSION . "/default/themes/$themeName" : constant("self::{$localThemeVariable}");
    }
}
// This file needs a line at the end because createCoreCodeIgniter.sh removes the first and last line of the file.



use RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Api,
    RightNow\Connect\v1_4 as Connect;

class Rnow
{
    //Misc Variables
    private $isSpider;
    private static $cgiRoot;
    private $protocol = '//';
    private static $updatedConfigs = array();

    function __construct($fullInitialization = true)
    {
        // init.phph include starts here

        self::$cgiRoot = get_cfg_var('rnt.cgi_root');
        putenv(sprintf("CGI_ROOT=%s", self::$cgiRoot));
        define('LANG_DIR', get_cfg_var('rnt.language'));
        putenv(sprintf("LANG_DIR=%s", LANG_DIR));

        // ---------------------------------------------------------------
        // This nasty bit pulls in the copy of mod_info.phph which has all of the defines
        // (i.e., the non-script-compiled copy) if the request is for a non-production CP
        // page.  Otherwise we get the normal one.
        if (IS_HOSTED && !IS_OPTIMIZED){
            require_once(DOCROOT . '/cp/src/mod_info.phph');
        }
        else {
            require_once(DOCROOT . '/cp/mod_info.phph');
        }

        // In production, the defines in mod_info.phph are hard coded into CP, and mod_info.phph
        // is not included. Thus MOD_ACCESS must be defined here so its value can change.
        if (USES_ADMIN_IP_ACCESS_RULES) {
            define("MOD_ACCESS", MOD_ADMIN);
        }
        else {
            define("MOD_ACCESS", MOD_PUBLIC);
        }

        //CP always sends a UTF-8 content type
        header("Content-Type: text/html; charset=UTF-8");
        dl('libcmnapi-'.CP_PHP_VERSION . sprintf(DLLVERSFX, MOD_CMN_BUILD_VER));

        //We need to include each file separately since in order to track things correctly, we want the initConnectAPI call to happen
        //from core CP code. The kf_init file will attempt to include Connect_init, but it uses require_once so there isn't much impact.
        //It also has an additional call to initConnectAPI, but that is also very fast.
        require_once(DOCROOT . '/include/ConnectPHP/Connect_init.phph');
        initConnectAPI();
        $context = Connect\ConnectAPI::getCurrentContext();
        $context->ApplicationContext = "Calling Connect from CP";
        $context->DateAsString = true;
        if(RightNow\Connect\v1_4\ConnectAPI::isErrorRetryable()) {
            //In case of resource contention, redirection happens only for HTTP GET requests starting with /app and domain root
            $cpConfigs = \RightNow\Utils\Framework::readCPConfigsFromFile();
            if($cpConfigs['CP.ServiceRetryable.Enable'] === "true" && !get_instance()->isAjaxRequest() && ($_SERVER['REQUEST_URI'] ==='/' || strcasecmp(substr($_SERVER['REQUEST_URI'], 0, 4), '/app') === 0)) {
                \RightNow\Utils\Framework::sendRetryHeadersAndRedirect($cpConfigs);
            }
            else {
                self::loadMessagebaseDefines();
                \RightNow\Utils\Framework::writeContentWithLengthAndExit(self::getMessage(UNABLE_COMPLETE_REQUEST_TRY_AGAIN_MSG));
            }
        }
        require_once(DOCROOT . '/include/ConnectPHP/Connect_kf_init.phph');

        // Connect turns off error reporting; turn it back on.
        if (IS_HOSTED && IS_DEVELOPMENT)
            error_reporting(E_ALL & ~E_NOTICE); // PHP's default: All errors except E_STRICT and E_NOTICE
        else if (!IS_HOSTED)
            error_reporting(~E_NOTICE); // All errors except E_NOTICE

        //Tell Connect which mode we're running in so that they can bill things accordingly
        $cpMode = Connect\CustomerPortalMode::Production;
        if(IS_DEVELOPMENT || IS_STAGING || IS_REFERENCE){
            $cpMode = Connect\CustomerPortalMode::Development;
        }
        else if(IS_ADMIN){
            $cpMode = Connect\CustomerPortalMode::Admin;
        }
        Connect\CustomerPortal::setCustomerPortalMode($cpMode);

        self::postCommonApiInit($fullInitialization);

        //IE doesn't allow 3rd party cookies (e.g. when CP is used within an iFrame) unless a P3P
        //header is sent. Because of that, we're conditionaly going to send the header for IE only.
        if (MOD_ACCESS == MOD_PUBLIC && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false){
            header('P3P: CP="CAO CURa ADMa DEVa OUR BUS IND UNI COM NAV"');
        }

        if ($fullInitialization) {
            \RightNow\Libraries\AbuseDetection::sendSpeculativeRequests();
        }
        dl('librnwapi-'.CP_PHP_VERSION . sprintf(DLLVERSFX, MOD_BUILD_VER));
        self::validateRemoteAddress();

        if (!Api::sql_open_db())
            exit;

        // init.phph include ends here

        $this->isSpider = $this->isSpiderInit();

        if (!IS_ADMIN && Url::isRequestHttps() && $this->getConfig(SEC_END_USER_HTTPS)) {
            header("Strict-Transport-Security: max-age=15724800"); // 6 months
        }

        $this->redirectToHttpsIfNeeded();
        $this->validateRedirectHost();
        $this->ensureOptimizedDirectoryExists();
    }

    /**
     * Throws an exception if the function calling ensureCallerIsInternal was not
     * called by standard RightNow code.
     *
     * @private
     */
    public static function ensureCallerIsInternal($stack = null) {
        if (!$stack) {
            $stack = debug_backtrace(false);
        }
        // Internal/Base uses the __call and __callStatic functions for method overloading,
        //  so we have to look back one more to find the calling file
        $stackIndex = (($function = $stack[1]['function']) && ($function === '__call' || $function === '__callStatic')) ? 2 : 1;
        $errorReportingStackIndex = $stackIndex;

        //In some cases, the file isn't reported. This should happen because the function is being invoked from a callback (usually
        //through the use of a preg_replace_callback). In that case, go back into the stack one level further and find the file
        //where the callback was invoked from since it has to be core code.
        if($stack[$stackIndex]['file'] === null){
            $stackIndex++;
        }
        $callingFile = $stack[$stackIndex]['file'];
        $className = $stack[$stackIndex]['class'];
        $functionName = $stack[$stackIndex]['function'];

        $coreFrameworkPrefix = IS_HOSTED ? ".cfg/scripts/cp/core/framework/" : "/rnw/scripts/cp/core/framework/";
        $callingFileIndex = strpos($callingFile, $coreFrameworkPrefix);
        if ($callingFileIndex === false && (
            stripos($callingFile, ".cfg/scripts/cp/core/framework/") !== false || // CruiseControl isn't IS_HOSTED but its file structure is the same as hosted sites.
            stripos($callingFile, "/rnw/scripts/cp/core/util/tarball/") !== false  ||  // Tarball deploy tasks and tests are in core/util (non-HOSTED).
            stripos($callingFile, ".cfg/scripts/cp/core/util/tarball/") !== false      // Tarball deploy tasks are in core/util (HOSTED, however IS_HOSTED is false during tarball creation).
            )) {
            return;
        }
        if ($callingFileIndex !== false) {
            $pathAfterCore = substr($callingFile, $callingFileIndex + strlen($coreFrameworkPrefix));
        }
        //Disallow calls from the following locations:
        //  - Code not under /core/framework
        //  - Code executed during an eval()
        if(!$pathAfterCore || Text::stringContains($pathAfterCore, "eval()'d code")){
            throw new Exception("{$stack[$errorReportingStackIndex]['class']}::{$stack[$errorReportingStackIndex]['function']} may only be called by standard RightNow code. PATH - " . var_export($stack[$errorReportingStackIndex], true));
        }
    }

    private static function ensureOptimizedDirectoryExists() {
        // I use hooks.php as the means to determine if the inteface has been
        // successfully deployed because we require it to be present in order
        // to deploy.
        if ((IS_OPTIMIZED) && !is_file(APPPATH . '/config/hooks.php')) {
            exit(self::getMessage(INTERFACE_SUCCESSFULLY_DEPLOYED_MSG));
        }
    }

    private static function validateRemoteAddress() {
        $forceModPublic = func_num_args() > 0 ? func_get_arg(0) : false;
        if ((MOD_ACCESS === MOD_ADMIN) ||
            (MOD_ACCESS === MOD_PUBLIC) || $forceModPublic) {
            $avi['ip_addr'] = $_SERVER['REMOTE_ADDR'];
            $avi['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : '';
            $avi['source'] = (MOD_ACCESS === MOD_ADMIN && !$forceModPublic) ?
                              intval(ACCESS_VALIDATE_SRC_PHP_ADMIN) :
                              intval(ACCESS_VALIDATE_SRC_PHP_PUBLIC);
            $rv = Api::access_validate($avi);
        }
        if (isset($rv) && $rv !== RET_ACCESS_VALIDATE_SUCCESS &&
            $rv !== RET_USER_AGENT_NOT_AUTHORIZED && $rv !== RET_CLIENT_ADDR_NOT_AUTH) {
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $avi['ip_addr'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if ($avi['source'] === ACCESS_VALIDATE_SRC_PHP_PUBLIC) {
                    $avi['source'] = ACCESS_VALIDATE_SRC_PHP_PUBLIC_FORWARD;
                }
            }
            $rv = Api::access_validate($avi);
        }
        if (isset($rv) && $rv !== RET_ACCESS_VALIDATE_SUCCESS) {
            if ($rv === RET_NO_CLIENT_ADDR_SPEC) {
                $errorMessage = self::getMessage(NO_CLIENT_ADDR_SPEC_MSG);
            }
            elseif ($rv === RET_CLIENT_ADDR_NOT_AUTH) {
                $errorMessage = self::getMessage(CLIENT_ADDR_NOT_AUTH_MSG);
            }
            else {
                $errorMessage = self::getMessage(USER_AGENT_NOT_AUTHORIZED_MSG);
            }
            $errorTemplate = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>%s</title>
    <style>body { font-family: sans-serif } div { font-size: larger }</style>
</head>
<body>
    <h1>%s</h1>
    <br><hr size=1><br>
    <div>
        %s
        <p>
            <b>%s: </b>%s (%s)
        </p>
    </div>
</body>
</html>
HTML;
            $errorMessage = sprintf(
                $errorTemplate,
                self::getMessage(RNT_FATAL_ERROR_LBL),
                self::getMessage(FATAL_ERROR_LBL),
                self::getMessage(ACCESS_DENIED_LBL),
                self::getMessage(REASON_LBL),
                $errorMessage,
                $_SERVER['REMOTE_ADDR'] ?: ''
            );
            header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
            \RightNow\Utils\Framework::writeContentWithLengthAndExit($errorMessage, 'text/html');
        }
    }

    /**
     * Inspect URI for 'redirect' parameter and validate associated host against local domain, community domain, and CP_REDIRECT_HOSTS.
     * If a disallowed host found, redirect to 403 page in dev mode, else strip out bad host in production mode.
     * If CP_REDIRECT_HOSTS contains a '*', allow all hosts.
     * If CP_REDIRECT_HOSTS is empty, allow no hosts (other than local and community)
     *
     * $return [null]
     */
    private function validateRedirectHost() {
        //Adding ternary check to verify first character of $fragment is / and to strip
        //if there are two // we leave it alone
        if (!($uri = strtolower(urldecode(ORIGINAL_REQUEST_URI))) ||
            !($fragment = Text::getSubstringAfter($uri, '/redirect/')) ||
            !($fragment = $fragment[0] === '/' && $fragment[1] !== '/' ? substr($fragment, 1) : $fragment) || 
             (!Text::beginsWith($fragment, 'http') && !Text::beginsWith($fragment, '//')))
        {
            return;
        }

        if (!Url::isRedirectAllowedForHost($fragment)) {
            if (IS_PRODUCTION) {
                header("Location: " . $this->protocol . $_SERVER['HTTP_HOST'] . str_replace("/redirect/$fragment", '', $uri));
                exit;
            }
            else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
                \RightNow\Utils\Framework::writeContentWithLengthAndExit('Host not allowed');
            }
        }
    }

    private function redirectToHttpsIfNeeded() {
        $secHttpConfig = (IS_ADMIN || IS_DEPLOYABLE_ADMIN) ? SEC_ADMIN_HTTPS : SEC_END_USER_HTTPS;
        if (!((isset($_SERVER['HTTP_RNT_SSL']) && $_SERVER['HTTP_RNT_SSL'] === 'yes') || (!IS_HOSTED && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) && $this->getConfig($secHttpConfig)) {
            $this->protocol = 'https://';
            header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
            header("Location: {$this->protocol}" . $_SERVER['HTTP_HOST'] . ORIGINAL_REQUEST_URI);
            exit;
        }
    }

    private static function postCommonApiInit($fullInitialization) {
        $currentInterfaceName = substr(self::getCfgDir(), 0, -4);
        Api::set_intf_name($currentInterfaceName);

        //Conditionally swap out the messagebases being used for requests from the CX console or CP Admin pages.
        if (($langData = get_instance()->_getRequestedAdminLangData()) && $langData[0] !== $currentInterfaceName) {
            Api::msgbase_switch($langData[0]);
        }

        if ($fullInitialization) {
            self::loadConfigDefines();
            self::loadMessagebaseDefines();
        }
        else {
            self::loadConfigDefines();
        }

        $controllerClassName = strtolower(get_instance()->router->fetch_class());
        if (self::cpDisabledAndShouldExit($controllerClassName)) {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            //Expand the error message by duplicating spaces so that we actually display this message
            //and the browser doesn't show its default 404 page
            exit(self::getMessage(CUSTOMER_PORTAL_ENABLED_INTERFACE_MSG) . str_repeat(" ", 512));
        }
        if(self::getConfig(CP_MAINTENANCE_MODE_ENABLED) && IS_PRODUCTION && !CUSTOM_CONTROLLER_REQUEST && in_array($controllerClassName, array('page', 'facebook', 'widgetservice'))) {
             //Only display the splash page for page requests
             if($controllerClassName !== 'widgetservice'){
                 echo file_get_contents(OPTIMIZED_FILES . "/production/optimized/errors/error500.html");
             }
             exit;
        }

        if (!IS_HOSTED) {
            require_once(DOCROOT . '/include/rnwintf.phph');
        }
        else if (!IS_OPTIMIZED) {
            require_once(DOCROOT . '/include/src/rnwintf.phph');
        }
    }

    /*
     * Indicates if Customer Portal is not enabled, and the request and general state of configs
     * warrants an exit with the 'Customer Portal is not enabled for this interface' message.
     *
     * @param string $className The controller's class name.
     * @param null|string $methodName The name of the method being run. If null, defaults to router->fetch_method()
     * @param null|boolean $isCustomController If null, defaults to CUSTOM_CONTROLLER_REQUEST
     * @return boolean True if an exit is warranted.
     */
    private static function cpDisabledAndShouldExit($className, $methodName = null, $isCustomController = null) {
        if (!self::getConfig(MOD_CP_ENABLED) &&
            // Allow when MOD_CP_DEVELOPEMENT_ENABLED and coming from a production/optimized type request
            !(self::getConfig(MOD_CP_DEVELOPMENT_ENABLED) && (IS_ADMIN || IS_DEVELOPMENT || IS_STAGING || IS_REFERENCE)) &&
            // class and/or method name exceptions, when not coming from a custom controller
            !(!($isCustomController === null ? CUSTOM_CONTROLLER_REQUEST : $isCustomController) && (
                // Allow inlineImage and answerPreview requests
                ($className === 'inlineimage' || $className === 'answerpreview' || $className === 'inlineimg') ||
                // Allow marketing requests if either of the MOD_*_ENABLED configs enabled.
                (($className === 'documents' || $className === 'friend') && (self::getConfig(MOD_FEEDBACK_ENABLED) || self::getConfig(MOD_MA_ENABLED))) ||
                // Allow service pack deploys
                ($className === 'deploy' && ($methodName ?: strtolower(get_instance()->router->fetch_method())) === 'servicepackdeploy')
            ))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Allows us to get the necessary information to load the config bases from the
     * test directory if current request is a test request.
     */
    private static function getConfigBaseInterfaceDirectory() {
        $cfgdir = self::getCfgDir();
        if (!self::isTestRequest())
            return $cfgdir;

        preg_match_all("/([^,:]+):([^,:]+)/", self::getTestOptions(), $results);
        $opts = array_combine($results[1], $results[2]);
        if (array_key_exists("suffix", $opts))
            return str_replace(".cfg", $opts["suffix"] . ".cfg", $cfgdir);

        return $cfgdir;
    }

    public static function getCfgDir() {
        static $cfgDir = null;
        if ($cfgDir === null) {
            // The "4" tells explode() to stop exploding after the first 3 strings have been seperated.
            // This is done because we only want the third string.
            $scriptNameSegments = explode('/', $_SERVER['SCRIPT_NAME'], 4);
            $cfgDir = $scriptNameSegments[2];
        }
        return $cfgDir;
    }

    private static function isTestRequest()
    {
        return isset($_ENV['rn_test_valid']) && ($_ENV['rn_test_valid'] === '1');
        //return $_COOKIE['rn_test_valid'] === '1';
    }

    private static function getTestOptions()
    {
        //return "suffix:_test2,db:jvswgit_test2,foo:bar";
        return $_ENV['rn_test_opts'];
        //return $_COOKIE['rn_test_opts'];
    }

    public static function getTestCookieData()
    {
        if(!self::isTestRequest())
            return "";

        return "location=" . str_replace("~", "%7E", $_COOKIE['location']) . ";rn_test_opts=" . $_COOKIE['rn_test_opts'] . ";";
        //return "rn_test_valid=" . $_COOKIE['rn_test_valid'] . ";rn_test_opts=" . $_COOKIE['rn_test_opts'] . ";";
    }

    /**
     * Gets a messageBase value given the slot name and the base's ID
     *
     * @return string The value of the messageBase slot
     * @param $slotID int The slot in the message base
     */
    static function getMessage($slotID)
    {
        static $canCallMessageGetApiMethod = null;
        if($canCallMessageGetApiMethod === null){
            $canCallMessageGetApiMethod = function_exists('msg_get');
        }
        if(!$canCallMessageGetApiMethod){
            return "msg #$slotID";
        }
        return Api::msg_get_compat($slotID);
    }

    /**
     * Specialized function to compare passed in value to admin password config setting since
     * we cannot access that config directly.
     * @param string $password Encrypted password to check
     * @return boolean
     */
    static function isValidAdminPassword($password) {
        if ($password === '')
            $password = Api::pw_encrypt($password, ENCRYPT_IP);
        return ($password === Api::pw_encrypt(Api::cfg_get_compat(SEC_CONFIG_PASSWD), ENCRYPT_IP));
    }

    /**
     * Gets a configbase value given the slot name and the
     * base ID's
     * @return mixed The value of the config in the correct form
     * @param $slotID int The config base slot ID
     */
    static function getConfig($slotID)
    {
        static $canCallConfigGetApiMethod = null;
        if($canCallConfigGetApiMethod === null){
            $canCallConfigGetApiMethod = function_exists('cfg_get_casted');
        }
        if(!$canCallConfigGetApiMethod){
            if($slotID === CP_DEPRECATION_BASELINE || $slotID === CP_CONTACT_LOGIN_REQUIRED)
                return 0;
            throw new Exception("Cannot retrieve config $slotID, $configBase during tarballDeploy. You probably need to add a case for it in Rnow.php.");
        }

        //Block all access to these configs for security reasons
        if(in_array($slotID, array(SEC_CONFIG_PASSWD, DB_PASSWD, PROD_DB_PASSWD, rep_db_passwd)))
            return null;

        // return default config values for url-related configs when in reference mode
        if(IS_REFERENCE && ($overrideValue = self::getReferenceModeConfigValue($slotID)) !== null)
            return $overrideValue;

            if (!IS_HOSTED && isset(self::$updatedConfigs[$slotID]) && ($unsavedValue = self::$updatedConfigs[$slotID]) !== null) {
            return $unsavedValue;
        }

        return Api::cfg_get_compat($slotID);
    }

    /**
     * Updates a configbase value
     *
     * @param string $slotName The config base slot name
     * @param string|bool|int $newValue The value to set the slot
     * @param bool $doNotSave Whether the config value is actually updated or just
     *        persisted for the rest of the process
     * @return string|bool|int The old value of the config in the correct form
     */
    static function updateConfig($slotName, $newValue, $doNotSave = false) {
        if(IS_HOSTED){
            throw new Exception("Configs cannot be updated from within CP.");
        }

        if(!is_string($slotName)) {
            throw new Exception("Expected a string for config slot ID, but got '" . var_export($slotName, true) . "' instead.");
        }
        if(!defined($slotName) || !$slotValue = constant($slotName)) {
            throw new Exception("Expected to find a define for $slotName, but there's no such config slot.");
        }

        if ($doNotSave) {
            self::$updatedConfigs[$slotValue] = $newValue;
            return self::getConfig($slotValue);
        }

        $interfaceName = Api::intf_name();
        $setConfigScriptPath = get_cfg_var('rnt.cgi_root') . "/$interfaceName.cfg/bin/set_config";

        if($newValue === false)
            $newValue = "0";
        else
            $newValue = "\"$newValue\"";

        $oldValue = self::getConfig($slotValue);
        exec("CX_ENVIRONMENT=DEV $setConfigScriptPath $interfaceName $slotName $newValue 2>&1", $output);
        if(count($output)){
            throw new Exception("Tried to execute: $setConfigScriptPath $interfaceName $slotName $newValue and got this error: " . implode('\n', $output));
        }
        return $oldValue;
    }

    /**
     * Gets the override value of a configbase value in reference mode
     * @return mixed The value of the config in the correct form or null
     * if the value is not overridden in reference mode
     * @param $slotID int The config base slot ID
     */
    private static function getReferenceModeConfigValue($slotID) {
        if (in_array($slotID, array(CP_404_URL, CP_ACCOUNT_ASSIST_URL,
                    CP_ANSWERS_DETAIL_URL, CP_ANS_NOTIF_UNSUB_URL,
                    CP_CHAT_URL, CP_HOME_URL, CP_INCIDENT_RESPONSE_URL,
                    CP_LOGIN_URL, CP_WEBSEARCH_DETAIL_URL))) {
            switch($slotID) {
                case CP_404_URL:
                    return 'error404';
                case CP_ACCOUNT_ASSIST_URL:
                    return 'utils/account_assistance';
                case CP_ANSWERS_DETAIL_URL:
                    return IS_OKCS_REFERENCE ? 'answers/answer_view' : 'answers/detail';
                case CP_ANS_NOTIF_UNSUB_URL:
                    return 'account/notif/unsubscribe';
                case CP_CHAT_URL:
                    return 'chat/chat_launch';
                case CP_HOME_URL:
                    return 'home';
                case CP_INCIDENT_RESPONSE_URL:
                    return 'account/questions/detail';
                case CP_LOGIN_URL:
                    return 'utils/login_form';
                case CP_WEBSEARCH_DETAIL_URL:
                    return 'answers/detail';
            }
        }
        return null;
    }

    /**
     * Returns if the user-agent is determined to be a known spider
     * @return boolean Whether the user agent is a spider or not
     */
    function isSpider()
    {
        return $this->isSpider;
    }

    private function isSpiderInit()
    {
        return Api::check_spider(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', NULL, $_SERVER['REMOTE_ADDR']);
    }


    /**
     * Returns an array of escape characters for SQL queries
     * @return array List of escape characters
     */
    function getSqlEscapeCharacters()
    {
        return array('\'' => '\\\'',
                     '\\' => '\\\\',
                     );
    }

    /**
     * Returns an array of escape characters for file attachment uploads
     * @return array List of escape characters
     */
    function getFileNameEscapeCharacters()
    {
        return array('<' => '-',
                     '>' => '-',
                     '&lt;' => '-',
                     '&gt;' => '-',
                     '%' => '-',
                     );
    }

    private static function loadConfigDefines(){
        self::loadDefinesFile('config');
    }
    private static function loadMessagebaseDefines(){
        self::loadDefinesFile('msgbase');
    }

    private static function loadDefinesFile($type){
        if (IS_HOSTED && !IS_OPTIMIZED)
            require_once(DOCROOT . "/include/src/$type/$type.phph");
        else if (!IS_HOSTED)
            require_once(DOCROOT . "/include/$type/$type.phph");
    }

    /**
     * Returns a list of core PHP files that are always loaded within CP. These files
     * are loaded individually on a non-hosted site within development mode, and this
     * list of files is combined to create the optimized_includes.php file for hosted sites
     * and those in production mode.
    */
    public static function getCorePhpIncludes()
    {
        $cpcore = CPCORE;
        return array(
            "{$cpcore}Controllers/Base.php",
            "{$cpcore}Controllers/Admin/Base.php",
            "{$cpcore}Decorators/Base.php",
            "{$cpcore}Models/Base.php",
            "{$cpcore}Models/Clickstream.php",
            "{$cpcore}Models/Pageset.php",
            "{$cpcore}Models/PrimaryObjectBase.php",
            "{$cpcore}Models/CommunityObjectBase.php",
            "{$cpcore}Models/SearchSourceBase.php",
            "{$cpcore}Internal/Exception.php",
            "{$cpcore}Internal/Libraries/Search.php",
            "{$cpcore}Internal/Libraries/Widget/Base.php",
            "{$cpcore}Internal/Libraries/Widget/Locator.php",
            "{$cpcore}Internal/Libraries/Widget/ExtensionLoader.php",
            "{$cpcore}Internal/Libraries/Widget/Helpers/Loader.php",
            "{$cpcore}Internal/Libraries/Widget/ViewPartials/Handler.php",
            "{$cpcore}Internal/Libraries/Widget/ViewPartials/Partial.php",
            "{$cpcore}Internal/Libraries/Widget/ViewPartials/WidgetPartial.php",
            "{$cpcore}Internal/Libraries/Widget/ViewPartials/SharedPartial.php",
            "{$cpcore}Libraries/Widget/Helper.php",
            "{$cpcore}Libraries/Widget/Base.php",
            "{$cpcore}Libraries/Widget/Input.php",
            "{$cpcore}Libraries/Widget/Output.php",
            "{$cpcore}Internal/Libraries/ClientLoader.php",
            "{$cpcore}Libraries/ClientLoader.php",
            "{$cpcore}Libraries/Decorator.php",
            "{$cpcore}Libraries/SearchResult.php",
            "{$cpcore}Libraries/SearchResults.php",
            "{$cpcore}Libraries/SearchMappers/BaseMapper.php",
            "{$cpcore}Libraries/Search.php",
            "{$cpcore}Libraries/Session.php",
            "{$cpcore}Libraries/Hooks.php",
            "{$cpcore}Libraries/SEO.php",
            "{$cpcore}Libraries/AbuseDetection.php",
            "{$cpcore}Libraries/PageSetMapping.php",
            "{$cpcore}Libraries/Formatter.php",
            "{$cpcore}Libraries/ResponseObject.php",
            "{$cpcore}Libraries/Cache/ReadThroughCache.php",
            "{$cpcore}Libraries/Cache/PersistentReadThroughCache.php",
            "{$cpcore}Libraries/ConnectTabular.php",
            "{$cpcore}Internal/Utils/Url.php",
            "{$cpcore}Internal/Utils/SearchSourceConfiguration.php",
            "{$cpcore}Internal/Utils/FileSystem.php",
            "{$cpcore}Internal/Utils/Config.php",
            "{$cpcore}Internal/Utils/Connect.php",
            "{$cpcore}Internal/Utils/Framework.php",
            "{$cpcore}Internal/Utils/Tags.php",
            "{$cpcore}Internal/Utils/Text.php",
            "{$cpcore}Internal/Utils/Widgets.php",
            "{$cpcore}Internal/Utils/WidgetViews.php",
            "{$cpcore}Internal/Utils/Version.php",
            "{$cpcore}Utils/Permissions/Social.php",
            "{$cpcore}Utils/Tags.php",
            "{$cpcore}Utils/Text.php",
            "{$cpcore}Utils/VersionBump.php",
            "{$cpcore}Utils/Widgets.php",
            "{$cpcore}Utils/Framework.php",
            "{$cpcore}Utils/Url.php",
            "{$cpcore}Utils/Connect.php",
            "{$cpcore}Utils/Config.php",
            "{$cpcore}Utils/FileSystem.php",
            "{$cpcore}Utils/Chat.php",
            "{$cpcore}Utils/Validation.php",
            "{$cpcore}Utils/Date.php",
            "{$cpcore}Utils/OpenLoginUserInfo.php",
            "{$cpcore}Internal/Libraries/Widget/PathInfo.php",
            "{$cpcore}Internal/Libraries/Widget/Registry.php",
            "{$cpcore}Internal/Libraries/MetaParser.php",
            "{$cpcore}Internal/Libraries/SandboxedConfigs.php",
            "{$cpcore}Hooks/CleanseData.php",
            "{$cpcore}Hooks/Clickstream.php",
            "{$cpcore}Hooks/SqlMailCommit.php",
            "{$cpcore}Hooks/Acs.php",
        );
    }

    /**
     * Returns a list of core compatibility PHP files that are always loaded within CP. These files
     * are loaded individually on a non-hosted site within development mode, and this
     * list of files is combined to create the compatibility optimized_includes.php file for hosted sites
     * and those in production mode.
    */
    public static function getCoreCompatibilityPhpIncludes()
    {
        $coreFiles = CORE_FILES;
        $fileList = array(
            "{$coreFiles}compatibility/Internal/Api.php",
            "{$coreFiles}compatibility/Api.php",
            "{$coreFiles}compatibility/ActionCapture.php",
            "{$coreFiles}compatibility/Internal/Sql/Clickstream.php",
            "{$coreFiles}compatibility/Internal/Sql/Pageset.php",
        );
        if(IS_HOSTED || IS_TARBALL_DEPLOY){
            $fileList[] = "{$coreFiles}compatibility/Mappings/Classes.php";
            $fileList[] = "{$coreFiles}compatibility/Mappings/Functions.php";
        }
        return $fileList;
    }
}



/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Parser Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Parser
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/libraries/parser.html
 */
class CI_Parser {

	/**
	 * Left delimiter character for pseudo vars
	 *
	 * @var string
	 */
	public $l_delim = '{';

	/**
	 * Right delimiter character for pseudo vars
	 *
	 * @var string
	 */
	public $r_delim = '}';

	/**
	 * Reference to CodeIgniter instance
	 *
	 * @var object
	 */
	protected $CI;

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->CI =& get_instance();
		log_message('info', 'Parser Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Parse a template
	 *
	 * Parses pseudo-variables contained in the specified template view,
	 * replacing them with the data in the second param
	 *
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	public function parse($template, $data, $return = FALSE)
	{
		$template = $this->CI->load->view($template, $data, TRUE);

		return $this->_parse($template, $data, $return);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse a String
	 *
	 * Parses pseudo-variables contained in the specified string,
	 * replacing them with the data in the second param
	 *
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	public function parse_string($template, $data, $return = FALSE)
	{
		return $this->_parse($template, $data, $return);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse a template
	 *
	 * Parses pseudo-variables contained in the specified template,
	 * replacing them with the data in the second param
	 *
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	protected function _parse($template, $data, $return = FALSE)
	{
		if ($template === '')
		{
			return FALSE;
		}

		$replace = array();
		foreach ($data as $key => $val)
		{
			$replace = array_merge(
				$replace,
				is_array($val)
					? $this->_parse_pair($key, $val, $template)
					: $this->_parse_single($key, (string) $val, $template)
			);
		}

		unset($data);
		$template = strtr($template, $replace);

		if ($return === FALSE)
		{
		    $this->CI->output->set_output($template);
		}

		return $template;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the left/right variable delimiters
	 *
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function set_delimiters($l = '{', $r = '}')
	{
		$this->l_delim = $l;
		$this->r_delim = $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Parse a single key/value
	 *
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	protected function _parse_single($key, $val, $string)
	{
		return array($this->l_delim.$key.$this->r_delim => (string) $val);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse a tag pair
	 *
	 * Parses tag pairs: {some_tag} string... {/some_tag}
	 *
	 * @param	string
	 * @param	array
	 * @param	string
	 * @return	string
	 */
	protected function _parse_pair($variable, $data, $string)
	{
		$replace = array();
		preg_match_all(
			'#'.preg_quote($this->l_delim.$variable.$this->r_delim).'(.+?)'.preg_quote($this->l_delim.'/'.$variable.$this->r_delim).'#s',
			$string,
			$matches,
			PREG_SET_ORDER
		);

		foreach ($matches as $match)
		{
			$str = '';
			foreach ($data as $row)
			{
				$temp = array();
				foreach ($row as $key => $val)
				{
					if (is_array($val))
					{
						$pair = $this->_parse_pair($key, $val, $match[1]);
						if ( ! empty($pair))
						{
							$temp = array_merge($temp, $pair);
						}

						continue;
					}

					$temp[$this->l_delim.$key.$this->r_delim] = $val;
				}

				$str .= strtr($match[1], $temp);
			}

			$replace[$match[0]] = $str;
		}

		return $replace;
	}

}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User Agent Class
 *
 * Identifies the platform, browser, robot, or mobile device of the browsing agent
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	User Agent
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/libraries/user_agent.html
 */
class CI_User_agent {

	/**
	 * Current user-agent
	 *
	 * @var string
	 */
	public $agent = NULL;

	/**
	 * Flag for if the user-agent belongs to a browser
	 *
	 * @var bool
	 */
	public $is_browser = FALSE;

	/**
	 * Flag for if the user-agent is a robot
	 *
	 * @var bool
	 */
	public $is_robot = FALSE;

	/**
	 * Flag for if the user-agent is a mobile browser
	 *
	 * @var bool
	 */
	public $is_mobile = FALSE;

	/**
	 * Languages accepted by the current user agent
	 *
	 * @var array
	 */
	public $languages = array();

	/**
	 * Character sets accepted by the current user agent
	 *
	 * @var array
	 */
	public $charsets = array();

	/**
	 * List of platforms to compare against current user agent
	 *
	 * @var array
	 */
	public $platforms = array(
	    'windows nt 10.0'	=> 'Windows 10',
	    'windows nt 6.3'	=> 'Windows 8.1',
	    'windows nt 6.2'	=> 'Windows 8',
	    'windows nt 6.1'	=> 'Windows 7',
	    'windows nt 6.0'	=> 'Windows Vista',
	    'windows nt 5.2'	=> 'Windows 2003',
	    'windows nt 5.1'	=> 'Windows XP',
	    'windows nt 5.0'	=> 'Windows 2000',
	    'windows nt 4.0'	=> 'Windows NT 4.0',
	    'winnt4.0'			=> 'Windows NT 4.0',
	    'winnt 4.0'			=> 'Windows NT',
	    'winnt'				=> 'Windows NT',
	    'windows 98'		=> 'Windows 98',
	    'win98'				=> 'Windows 98',
	    'windows 95'		=> 'Windows 95',
	    'win95'				=> 'Windows 95',
	    'windows phone'		=> 'Windows Phone',
	    'windows'			=> 'Unknown Windows OS',
	    'android'			=> 'Android',
	    'blackberry'		=> 'BlackBerry',
	    'iphone'			=> 'iOS',
	    'ipad'				=> 'iOS',
	    'ipod'				=> 'iOS',
	    'os x'				=> 'Mac OS X',
	    'ppc mac'			=> 'Power PC Mac',
	    'freebsd'			=> 'FreeBSD',
	    'ppc'				=> 'Macintosh',
	    'linux'				=> 'Linux',
	    'debian'			=> 'Debian',
	    'sunos'				=> 'Sun Solaris',
	    'beos'				=> 'BeOS',
	    'apachebench'		=> 'ApacheBench',
	    'aix'				=> 'AIX',
	    'irix'				=> 'Irix',
	    'osf'				=> 'DEC OSF',
	    'hp-ux'				=> 'HP-UX',
	    'netbsd'			=> 'NetBSD',
	    'bsdi'				=> 'BSDi',
	    'openbsd'			=> 'OpenBSD',
	    'gnu'				=> 'GNU/Linux',
	    'unix'				=> 'Unknown Unix OS',
	    'symbian' 			=> 'Symbian OS'
	);

	/**
	 * List of browsers to compare against current user agent
	 *
	 * @var array
	 */
	public $browsers = array(
	    'OPR'			=> 'Opera',
	    'Flock'			=> 'Flock',
	    'Edge'			=> 'Edge',
	    'Chrome'		=> 'Chrome',
	    // Opera 10+ always reports Opera/9.80 and appends Version/<real version> to the user agent string
	    'Opera.*?Version'	=> 'Opera',
	    'Opera'			=> 'Opera',
	    'MSIE'			=> 'Internet Explorer',
	    'Internet Explorer'	=> 'Internet Explorer',
	    'Trident.* rv'	=> 'Internet Explorer',
	    'Shiira'		=> 'Shiira',
	    'Firefox'		=> 'Firefox',
	    'Chimera'		=> 'Chimera',
	    'Phoenix'		=> 'Phoenix',
	    'Firebird'		=> 'Firebird',
	    'Camino'		=> 'Camino',
	    'Netscape'		=> 'Netscape',
	    'OmniWeb'		=> 'OmniWeb',
	    'Safari'		=> 'Safari',
	    'Mozilla'		=> 'Mozilla',
	    'Konqueror'		=> 'Konqueror',
	    'icab'			=> 'iCab',
	    'Lynx'			=> 'Lynx',
	    'Links'			=> 'Links',
	    'hotjava'		=> 'HotJava',
	    'amaya'			=> 'Amaya',
	    'IBrowse'		=> 'IBrowse',
	    'Maxthon'		=> 'Maxthon',
	    'Ubuntu'		=> 'Ubuntu Web Browser'
	);

	/**
	 * List of mobile browsers to compare against current user agent
	 *
	 * @var array
	 */
	public $mobiles = array(
	// legacy array, old values commented out
	    'mobileexplorer'	=> 'Mobile Explorer',
	    //  'openwave'			=> 'Open Wave',
	//	'opera mini'		=> 'Opera Mini',
	//	'operamini'			=> 'Opera Mini',
	//	'elaine'			=> 'Palm',
	    'palmsource'		=> 'Palm',
	    //	'digital paths'		=> 'Palm',
	//	'avantgo'			=> 'Avantgo',
	//	'xiino'				=> 'Xiino',
	    'palmscape'			=> 'Palmscape',
	    //	'nokia'				=> 'Nokia',
	//	'ericsson'			=> 'Ericsson',
	//	'blackberry'		=> 'BlackBerry',
	//	'motorola'			=> 'Motorola'
	    
	    // Phones and Manufacturers
	    'motorola'		=> 'Motorola',
	    'nokia'			=> 'Nokia',
	    'nexus'			=> 'Nexus',
	    'palm'			=> 'Palm',
	    'iphone'		=> 'Apple iPhone',
	    'ipad'			=> 'iPad',
	    'ipod'			=> 'Apple iPod Touch',
	    'sony'			=> 'Sony Ericsson',
	    'ericsson'		=> 'Sony Ericsson',
	    'blackberry'	=> 'BlackBerry',
	    'cocoon'		=> 'O2 Cocoon',
	    'blazer'		=> 'Treo',
	    'lg'			=> 'LG',
	    'amoi'			=> 'Amoi',
	    'xda'			=> 'XDA',
	    'mda'			=> 'MDA',
	    'vario'			=> 'Vario',
	    'htc'			=> 'HTC',
	    'samsung'		=> 'Samsung',
	    'sharp'			=> 'Sharp',
	    'sie-'			=> 'Siemens',
	    'alcatel'		=> 'Alcatel',
	    'benq'			=> 'BenQ',
	    'ipaq'			=> 'HP iPaq',
	    'mot-'			=> 'Motorola',
	    'playstation portable'	=> 'PlayStation Portable',
	    'playstation 3'		=> 'PlayStation 3',
	    'playstation vita'  	=> 'PlayStation Vita',
	    'hiptop'		=> 'Danger Hiptop',
	    'nec-'			=> 'NEC',
	    'panasonic'		=> 'Panasonic',
	    'philips'		=> 'Philips',
	    'sagem'			=> 'Sagem',
	    'sanyo'			=> 'Sanyo',
	    'spv'			=> 'SPV',
	    'zte'			=> 'ZTE',
	    'sendo'			=> 'Sendo',
	    'nintendo dsi'	=> 'Nintendo DSi',
	    'nintendo ds'	=> 'Nintendo DS',
	    'nintendo 3ds'	=> 'Nintendo 3DS',
	    'wii'			=> 'Nintendo Wii',
	    'open web'		=> 'Open Web',
	    'openweb'		=> 'OpenWeb',
	    'meizu'                 => 'Meizu',
	    'huawei'                => 'Huawei',
	    'xiaomi'                => 'Xiaomi',
	    'oppo'                  => 'Oppo',
	    'vivo'                  => 'Vivo',
	    'infinix'               => 'Infinix',
	    
	    // Operating Systems
	    'android'		=> 'Android',
	    'symbian'		=> 'Symbian',
	    'SymbianOS'		=> 'SymbianOS',
	    'elaine'		=> 'Palm',
	    'series60'		=> 'Symbian S60',
	    'windows ce'	=> 'Windows CE',
	    
	    // Browsers
	    'obigo'			=> 'Obigo',
	    'netfront'		=> 'Netfront Browser',
	    'openwave'		=> 'Openwave Browser',
	    'mobilexplorer'	=> 'Mobile Explorer',
	    'operamini'		=> 'Opera Mini',
	    'opera mini'	=> 'Opera Mini',
	    'opera mobi'	=> 'Opera Mobile',
	    'fennec'		=> 'Firefox Mobile',
	    
	    // Other
	    'digital paths'	=> 'Digital Paths',
	    'avantgo'		=> 'AvantGo',
	    'xiino'			=> 'Xiino',
	    'novarra'		=> 'Novarra Transcoder',
	    'vodafone'		=> 'Vodafone',
	    'docomo'		=> 'NTT DoCoMo',
	    'o2'			=> 'O2',
	    
	    // Fallback
	    'mobile'		=> 'Generic Mobile',
	    'wireless'		=> 'Generic Mobile',
	    'j2me'			=> 'Generic Mobile',
	    'midp'			=> 'Generic Mobile',
	    'cldc'			=> 'Generic Mobile',
	    'up.link'		=> 'Generic Mobile',
	    'up.browser'	=> 'Generic Mobile',
	    'smartphone'	=> 'Generic Mobile',
	    'cellphone'		=> 'Generic Mobile'
	);

	/**
	 * List of robots to compare against current user agent
	 *
	 * @var array
	 */
	public $robots = array(
	    'googlebot'		=> 'Googlebot',
	    'msnbot'		=> 'MSNBot',
	    'baiduspider'		=> 'Baiduspider',
	    'bingbot'		=> 'Bing',
	    'slurp'			=> 'Inktomi Slurp',
	    'yahoo'			=> 'Yahoo',
	    'ask jeeves'		=> 'Ask Jeeves',
	    'fastcrawler'		=> 'FastCrawler',
	    'infoseek'		=> 'InfoSeek Robot 1.0',
	    'lycos'			=> 'Lycos',
	    'yandex'		=> 'YandexBot',
	    'mediapartners-google'	=> 'MediaPartners Google',
	    'CRAZYWEBCRAWLER'	=> 'Crazy Webcrawler',
	    'adsbot-google'		=> 'AdsBot Google',
	    'feedfetcher-google'	=> 'Feedfetcher Google',
	    'curious george'	=> 'Curious George',
	    'ia_archiver'		=> 'Alexa Crawler',
	    'MJ12bot'		=> 'Majestic-12',
	    'Uptimebot'		=> 'Uptimebot',
	    'UptimeRobot'		=> 'UptimeRobot'
	);

	/**
	 * Current user-agent platform
	 *
	 * @var string
	 */
	public $platform = '';

	/**
	 * Current user-agent browser
	 *
	 * @var string
	 */
	public $browser = '';

	/**
	 * Current user-agent version
	 *
	 * @var string
	 */
	public $version = '';

	/**
	 * Current user-agent mobile name
	 *
	 * @var string
	 */
	public $mobile = '';

	/**
	 * Current user-agent robot name
	 *
	 * @var string
	 */
	public $robot = '';

	/**
	 * HTTP Referer
	 *
	 * @var	mixed
	 */
	public $referer;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * Sets the User Agent and runs the compilation routine
	 *
	 * @return	void
	 */
	public function __construct()
	{
		if (isset($_SERVER['HTTP_USER_AGENT']))
		{
			$this->agent = trim($_SERVER['HTTP_USER_AGENT']);
			$this->_compile_data();
		}

		log_message('info', 'User Agent Class Initialized');
	}



	// --------------------------------------------------------------------

	/**
	 * Compile the User Agent Data
	 *
	 * @return	bool
	 */
	protected function _compile_data()
	{
		$this->_set_platform();

		foreach (array('_set_robot', '_set_browser', '_set_mobile') as $function)
		{
			if ($this->$function() === TRUE)
			{
				break;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set the Platform
	 *
	 * @return	bool
	 */
	protected function _set_platform()
	{
		if (is_array($this->platforms) && count($this->platforms) > 0)
		{
			foreach ($this->platforms as $key => $val)
			{
				if (preg_match('|'.preg_quote($key).'|i', $this->agent))
				{
					$this->platform = $val;
					return TRUE;
				}
			}
		}

		$this->platform = 'Unknown Platform';
		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the Browser
	 *
	 * @return	bool
	 */
	protected function _set_browser()
	{
		if (is_array($this->browsers) && count($this->browsers) > 0)
		{
			foreach ($this->browsers as $key => $val)
			{
				if (preg_match('|'.$key.'.*?([0-9\.]+)|i', $this->agent, $match))
				{
					$this->is_browser = TRUE;
					$this->version = $match[1];
					$this->browser = $val;
					$this->_set_mobile();
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the Robot
	 *
	 * @return	bool
	 */
	protected function _set_robot()
	{
		if (is_array($this->robots) && count($this->robots) > 0)
		{
			foreach ($this->robots as $key => $val)
			{
				if (preg_match('|'.preg_quote($key).'|i', $this->agent))
				{
					$this->is_robot = TRUE;
					$this->robot = $val;
					$this->_set_mobile();
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the Mobile Device
	 *
	 * @return	bool
	 */
	protected function _set_mobile()
	{
		if (is_array($this->mobiles) && count($this->mobiles) > 0)
		{
			foreach ($this->mobiles as $key => $val)
			{
				if (FALSE !== (stripos($this->agent, $key)))
				{
					$this->is_mobile = TRUE;
					$this->mobile = $val;
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the accepted languages
	 *
	 * @return	void
	 */
	protected function _set_languages()
	{
		if ((count($this->languages) === 0) && ! empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$this->languages = explode(',', preg_replace('/(;\s?q=[0-9\.]+)|\s/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE']))));
		}

		if (count($this->languages) === 0)
		{
			$this->languages = array('Undefined');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set the accepted character sets
	 *
	 * @return	void
	 */
	protected function _set_charsets()
	{
		if ((count($this->charsets) === 0) && ! empty($_SERVER['HTTP_ACCEPT_CHARSET']))
		{
			$this->charsets = explode(',', preg_replace('/(;\s?q=.+)|\s/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET']))));
		}

		if (count($this->charsets) === 0)
		{
			$this->charsets = array('Undefined');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Is Browser
	 *
	 * @param	string	$key
	 * @return	bool
	 */
	public function is_browser($key = NULL)
	{
		if ( ! $this->is_browser)
		{
			return FALSE;
		}

		// No need to be specific, it's a browser
		if ($key === NULL)
		{
			return TRUE;
		}

		// Check for a specific browser
		return (isset($this->browsers[$key]) && $this->browser === $this->browsers[$key]);
	}

	// --------------------------------------------------------------------

	/**
	 * Is Robot
	 *
	 * @param	string	$key
	 * @return	bool
	 */
	public function is_robot($key = NULL)
	{
		if ( ! $this->is_robot)
		{
			return FALSE;
		}

		// No need to be specific, it's a robot
		if ($key === NULL)
		{
			return TRUE;
		}

		// Check for a specific robot
		return (isset($this->robots[$key]) && $this->robot === $this->robots[$key]);
	}

	// --------------------------------------------------------------------

	/**
	 * Is Mobile
	 *
	 * @param	string	$key
	 * @return	bool
	 */
	public function is_mobile($key = NULL)
	{
		if ( ! $this->is_mobile)
		{
			return FALSE;
		}

		// No need to be specific, it's a mobile
		if ($key === NULL)
		{
			return TRUE;
		}

		// Check for a specific robot
		return (isset($this->mobiles[$key]) && $this->mobile === $this->mobiles[$key]);
	}

	// --------------------------------------------------------------------

	/**
	 * Is this a referral from another site?
	 *
	 * @return	bool
	 */
	public function is_referral()
	{
		if ( ! isset($this->referer))
		{
			if (empty($_SERVER['HTTP_REFERER']))
			{
				$this->referer = FALSE;
			}
			else
			{
				$referer_host = @parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
				$own_host = parse_url((string) config_item('base_url'), PHP_URL_HOST);

				$this->referer = ($referer_host && $referer_host !== $own_host);
			}
		}

		return $this->referer;
	}

	// --------------------------------------------------------------------

	/**
	 * Agent String
	 *
	 * @return	string
	 */
	public function agent_string()
	{
		return $this->agent;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Platform
	 *
	 * @return	string
	 */
	public function platform()
	{
		return $this->platform;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Browser Name
	 *
	 * @return	string
	 */
	public function browser()
	{
		return $this->browser;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the Browser Version
	 *
	 * @return	string
	 */
	public function version()
	{
		return $this->version;
	}

	// --------------------------------------------------------------------

	/**
	 * Get The Robot Name
	 *
	 * @return	string
	 */
	public function robot()
	{
		return $this->robot;
	}
	// --------------------------------------------------------------------

	/**
	 * Get the Mobile Device
	 *
	 * @return	string
	 */
	public function mobile()
	{
		return $this->mobile;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the referrer
	 *
	 * @return	bool
	 */
	public function referrer()
	{
		return empty($_SERVER['HTTP_REFERER']) ? '' : trim($_SERVER['HTTP_REFERER']);
	}

	// --------------------------------------------------------------------

	/**
	 * Get the accepted languages
	 *
	 * @return	array
	 */
	public function languages()
	{
		if (count($this->languages) === 0)
		{
			$this->_set_languages();
		}

		return $this->languages;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the accepted Character Sets
	 *
	 * @return	array
	 */
	public function charsets()
	{
		if (count($this->charsets) === 0)
		{
			$this->_set_charsets();
		}

		return $this->charsets;
	}

	// --------------------------------------------------------------------

	/**
	 * Test for a particular language
	 *
	 * @param	string	$lang
	 * @return	bool
	 */
	public function accept_lang($lang = 'en')
	{
		return in_array(strtolower($lang), $this->languages(), TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Test for a particular character set
	 *
	 * @param	string	$charset
	 * @return	bool
	 */
	public function accept_charset($charset = 'utf-8')
	{
		return in_array(strtolower($charset), $this->charsets(), TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse a custom user-agent string
	 *
	 * @param	string	$string
	 * @return	void
	 */
	public function parse($string)
	{
		// Reset values
		$this->is_browser = FALSE;
		$this->is_robot = FALSE;
		$this->is_mobile = FALSE;
		$this->browser = '';
		$this->version = '';
		$this->mobile = '';
		$this->robot = '';

		// Set the new user-agent string and parse it, unless empty
		$this->agent = $string;

		if ( ! empty($string))
		{
			$this->_compile_data();
		}
	}
	
	/**
	 * Returns the matching browser string if the current user agent is one of the RightNow
	 * supported mobile browsers (iphone, ipod, android, webos)
	 * or false if the current user agent is not one of the
	 * RightNow supported mobile browsers.
	 *
	 * @access   public
	 * @return   bool
	 */
	public function supportedMobileBrowser()
	{
	    if(preg_match('/\b(iphone|ipod|android|webos)\b/i', $this->agent, $mobileBrowserMatch))
	    {
	        return strtolower($mobileBrowserMatch[1]);
	    }
	    return false;
	}

}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System Initialization File
 *
 * Loads the base classes and executes the request.
 *
 * @package		CodeIgniter
 * @subpackage	CodeIgniter
 * @category	Front-controller
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/
 */

/**
 * CodeIgniter Version
 *
 * @var	string
 *
 */
const CI_VERSION = '3.1.13';
if (IS_HOSTED || IS_OPTIMIZED) {
    require_once(CPCORE . 'optimized_includes.php');
    require_once(CORE_FILES . 'compatibility/optimized_includes.php');
}
else {
    
    foreach(Rnow::getCorePhpIncludes() as $filepath) {
        require_once($filepath);
    }
    foreach(Rnow::getCoreCompatibilityPhpIncludes() as $filepath) {
        require_once($filepath);
    }
}

/*
 * ------------------------------------------------------
 *  Load the global functions
 * ------------------------------------------------------
 */

require_once(BASEPATH.'core/Common'. EXT);


/*
 * ------------------------------------------------------
 * Security procedures
 * ------------------------------------------------------
 */

if (! is_php('5.4')) {
    ini_set('magic_quotes_runtime', 0);

    if ((bool) ini_get('register_globals')) {
        $_protected = array(
            '_SERVER',
            '_GET',
            '_POST',
            '_FILES',
            '_REQUEST',
            '_SESSION',
            '_ENV',
            '_COOKIE',
            'GLOBALS',
            'HTTP_RAW_POST_DATA',
            'system_path',
            'application_folder',
            'view_folder',
            '_protected',
            '_registered'
        );

        $_registered = ini_get('variables_order');
        foreach (array(
            'E' => '_ENV',
            'G' => '_GET',
            'P' => '_POST',
            'C' => '_COOKIE',
            'S' => '_SERVER'
        ) as $key => $superglobal) {
            if (strpos($_registered, $key) === FALSE) {
                continue;
            }

            foreach (array_keys($$superglobal) as $var) {
                if (isset($GLOBALS[$var]) && ! in_array($var, $_protected, TRUE)) {
                    $GLOBALS[$var] = NULL;
                }
            }
        }
    }
}


/*
 * ------------------------------------------------------
 *  Define a custom error handler so we can log PHP errors
 * ------------------------------------------------------
 */
    set_error_handler('_error_handler');
    set_exception_handler('_exception_handler');
    register_shutdown_function('_shutdown_handler');

/*
 * ------------------------------------------------------
 *  Instantiate the hooks class
 * ------------------------------------------------------
 */
    $EXT =& load_class('Hooks', 'core');

/*
 * ------------------------------------------------------
 *  Is there a "pre_system" hook?
 * ------------------------------------------------------
 */
    $EXT->call_hook('pre_system');

/*
 * ------------------------------------------------------
 *  Instantiate the config class
 * ------------------------------------------------------
 *
 * Note: It is important that Config is loaded first as
 * most other classes depend on it either directly or by
 * depending on another class that uses it.
 *
 */
    $CFG =& load_class('Config', 'core');

/*
 * ------------------------------------------------------
 *  Instantiate the routing class and set the routing
 * ------------------------------------------------------
 */
    $RTR =& load_class('Router', 'core', isset($routing) ? $routing : NULL);
	
/*
 * ------------------------------------------------------
 *  Instantiate the URI class
 * ------------------------------------------------------
 */
    $URI =& load_class('URI', 'core');

/*
 * ------------------------------------------------------
 *  Instantiate the output class
 * ------------------------------------------------------
 */
    $OUT =& load_class('Output', 'core');

/*
 * -----------------------------------------------------
 * Load the security class for xss and csrf support
 * -----------------------------------------------------
 */
    $SEC =& load_class('Security', 'core');

/*
 * ------------------------------------------------------
 *  Load the Input class and sanitize globals
 * ------------------------------------------------------
 */
    $IN	=& load_class('Input', 'core');

/*
 * ------------------------------------------------------
 *  Load the app controller and local controller
 * ------------------------------------------------------
 *
 */

    /**
     * Reference to the CI_Controller method.
     *
     * Returns current CI instance object
     *
     * @return CI_Controller
     */
    function &get_instance()
    {
        return CI_Controller::get_instance();
    }


/*
 * ------------------------------------------------------
 * CP CUSTOMISATIONS ON CORE CI 
 * ------------------------------------------------------
 */

eval('$GLOBALS["CFG"]  = $CFG  =& load_class("Config", "core");');
eval('$GLOBALS["RTR"]  = $RTR  =& load_class("Router", "core");');
eval('$GLOBALS["OUT"]  = $OUT  =& load_class("Output", "core");');
eval('$GLOBALS["IN"]   = $IN   =& load_class("Input", "core");');
eval('$GLOBALS["URI"]  = $URI  =& load_class("URI", "core");');
eval('$GLOBALS["SEC"]  = $SEC  =& load_class("Security", "core");');

// Load the local application controller
// Note: The Router class automatically validates the controller path.  If this include fails it
// means that the default controller in the Routes.php file is not resolving to something valid.
$className = $RTR->fetch_class();
$method = $RTR->fetch_method();
$subDirectory = $RTR->fetch_directory();
$controllerFullPath = $RTR->fetchFullControllerPath();
if ($controllerFullPath) {
    if($RTR->foundControllerInCpCore){
        if(in_array($className, array('base', 'syndicatedWidgetDataServiceBase')))
	    exit("Direct URL access to methods within controller base classes is not allowed.");
        $className = ucfirst($className);
        $namespacePrefix = "RightNow\\Controllers\\";
        //If standard controller is within a sub directory, it'll be capitalized on disk as well
        //as part of the namespace
        if($subDirectory){
            $namespacePrefix .= str_replace("/", "", $subDirectory) . "\\";
        }
        $className = $namespacePrefix . $className;

        require_once($controllerFullPath);
    }
    else {
        $namespacePrefix = "Custom\\Controllers\\";
        require_once($controllerFullPath);
        //Look for namespaced class name. Class names are case insensitive within PHP
        //so this will handle both camel and Pascal case.
        if(class_exists($namespacePrefix . $className)){
            $className = $namespacePrefix . $className;
        }
        //Controller class is in the global scope
        else if(class_exists($className)) {
            if(IS_DEVELOPMENT){
                exit("Custom controller classes must be namespaced under the Custom\Controllers namespace. This controller is not.");
            }
            //Unset the class name so that we generate a 404 below
            $className = null;
        }
    }
}
// If !$controllerFullPath then a controller segment that doesn't exist is being requested. The 404 code below will handle that.


/*
 * ------------------------------------------------------
 * Security check
 * ------------------------------------------------------
 * None of the functions in the app controller or the
 * loader class can be called via the URI, nor can
 * controller functions that begin with an underscore. Also
 * make sure that the function requested is actually callable
 */

$isCallable = false;
if (class_exists($className) && method_exists($className, $method)) {
    $reflection = new ReflectionMethod($className, $method);
    if ($reflection->isPublic()) {
        $isCallable = true;
    }
}

if (!class_exists($className) ||
    $method === 'controller' ||
    substr($method, 0, 1) === '_' ||
    !$isCallable ||
    in_array($method, get_class_methods('\RightNow\Controllers\Base'), true))
{
    if(IS_ADMIN) {
        $className = "RightNow\\Controllers\\Admin\\Overview";
        $method = 'admin404';
        $file = CPCORE . 'Controllers/Admin/Overview.php';
    }
    else {
        $className = "RightNow\\Controllers\\Page";
        $method = "render";
        $file = CPCORE . 'Controllers/Page.php';
    }
    $segments = $RTR->setVariablesFor404Page();
    $RTR->_reindex_segments();
    require_once($file);
}

/*
 * ------------------------------------------------------
 *  Is there a "pre_controller" hook?
 * ------------------------------------------------------
 */
    $EXT->call_hook('pre_controller');

 /*
 * ------------------------------------------------------
 * Instantiate the requested controller
 * ------------------------------------------------------
 */

    // If the controller is in a subdirectory then we need to correctly set the
    // parm_segment config so that getParameter() works. We defaulted that config to
    // 3, which is correct for a controller which is not in a subdirectory.
    if ($RTR->fetch_directory()) {
        $controllerDirectorySegments = count(explode('/', $RTR->fetch_directory())) - 1;
        $CFG->set_item('parm_segment', $controllerDirectorySegments + 3); // One for the controller name; one for the method name; one for the stupid 1-based indexing. And one for my homies. And one for the road. And one for the ditch.
    }
    
    if (CUSTOM_CONTROLLER_REQUEST) {
        \RightNow\Utils\Framework::installPathRestrictions();
        // Ensure that custom controllers finish executing their constructors
        ob_start(function ($buffer) use ($CFG) {
            if ($CFG->item('completedConstructor'))
                return $buffer;
            exit('You are not allowed to exit within your controller constructor.');
        });
    }
    
    $GLOBALS['CI'] = $CI = new $className();
    
    if (CUSTOM_CONTROLLER_REQUEST) {
        $CFG->set_item('completedConstructor', true);
        $constructorContent = ob_get_clean();
        if (strlen($constructorContent))
            echo $constructorContent;
    }
    
    if (! ($CI instanceof \RightNow\Controllers\Base) && ! ($CI instanceof \RightNow\Controllers\Admin\Base)) {
        exit(sprintf("Controller classes must derive from the \RightNow\Controllers\Base class. The '%s' controller does not.", $subDirectory . $className));
    }
    
    $getInstanceResult = get_instance();
    if (! $getInstanceResult) {
        exit(sprintf("Controller classes must call the parent class constructor. The '%s' controller does not.", $subDirectory . $className));
    }
    
    if (CUSTOM_CONTROLLER_REQUEST || ! IS_OPTIMIZED) {
        if ($CI instanceof \RightNow\Controllers\Base) {
            if (! \RightNow\Controllers\Base::checkConstructor($CI)) {
                exit(sprintf("Controller class must call the \RightNow\Controllers\Base parent class constructor, not the Controller parent class constructor. The '%s' controller does not.", $subDirectory . $className));
            }
        } else if (! \RightNow\Controllers\Admin\Base::checkConstructor($CI)) {
            exit(sprintf("Controller class must call the \RightNow\Controllers\Admin\Base parent class constructor, not the Controller parent class constructor. The '%s' controller does not.", $subDirectory . $className));
        }
    }

/*
 * ------------------------------------------------------
 *  Is there a "post_controller_constructor" hook?
 * ------------------------------------------------------
 */
    $EXT->call_hook('post_controller_constructor');

/*
 * ------------------------------------------------------
 *  Call the requested method
 * ------------------------------------------------------
 */

    $CI->_ensureContactIsAllowed();
    call_user_func_array(array(&$CI, $method), array_slice($RTR->rsegments, (($RTR->fetch_directory() == '') ? 2 : 3)));

/*
 * ------------------------------------------------------
 *  Is there a "post_controller" hook?
 * ------------------------------------------------------
 */
    $EXT->call_hook('post_controller');

/*
 * ------------------------------------------------------
 *  Send the final rendered output to the browser
 * ------------------------------------------------------
 */
    if ($EXT->call_hook('display_override') === FALSE)
    {
        $OUT->_display();
    }

/*
 * ------------------------------------------------------
 *  Is there a "post_system" hook?
 * ------------------------------------------------------
 */
    $EXT->call_hook('post_system');
