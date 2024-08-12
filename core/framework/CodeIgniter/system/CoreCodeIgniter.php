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
 *           system/codeigniter/Common.php
 *           system/libraries/Hooks.php
 *           system/libraries/Config.php
 *           system/libraries/Router.php
 *           system/libraries/Output.php
 *           system/libraries/Input.php
 *           system/libraries/URI.php
 *           system/libraries/Loader.php
 *           system/codeigniter/Base5.php
 *           system/libraries/Controller.php
 *           system/libraries/Exceptions.php
 *           system/libraries/Themes.php
 *           system/libraries/Rnow.php
 *           system/libraries/User_agent.php
 *           system/libraries/Encrypt.php
 *           system/codeigniter/CodeIgniter.php
 *
 * ------------------------------------------------------------------------*/


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Common Functions
 *
 * Loads the base classes and executes the request.
 *
 * @package        CodeIgniter
 * @subpackage    codeigniter
 * @category    Common Functions
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/
 */

// ------------------------------------------------------------------------

/**
* Class registry
*
* This function acts as a singleton.  If the requested class does not
* exist it is instantiated and set to a static variable.  If it has
* previously been instantiated the variable is returned.
*
* @access    public
* @param    string    the class name being requested
* @param    bool    optional flag that lets classes get loaded but not instantiated
* @return    object
*/
function &load_class($class, $instantiate = TRUE)
{
    static $objects = array();

    // Does the class exist?  If so, we're done...
    if (isset($objects[$class]))
    {
        return $objects[$class];
    }

    $className = ($class != 'Controller') ? 'CI_'.$class : $class;
    if(!class_exists($className))
    {
        require(BASEPATH.'libraries/'.$class.EXT);
    }

    if ($instantiate == FALSE)
    {
        $objects[$class] = TRUE;
        return $objects[$class];
    }

    $name = ($class != 'Controller') ? 'CI_'.$class : $class;

    $objects[$class] = new $name();
    return $objects[$class];
}

/**
* Error Handler
*
* This function lets us invoke the exception class and
* display errors using the standard error template located
* in application/errors/errors.php
* This function will send the error page directly to the
* browser and exit.
*
* @access    public
* @return    void
*/
function show_error($message)
{
    $error =& load_class('Exceptions');
    echo $error->show_error('An Error Was Encountered', $message);
    exit;
}


/**
* 404 Page Handler
*
* This function is similar to the show_error() function above
* However, instead of the standard error template it displays
* 404 errors.
*
* @access    public
* @return    void
*/
function show_404($page = '')
{
    // Admin controller requests to unfound resources
    // render the admin 404 page. If this method is called
    // programmatically from an admin controller
    // then simply output the 404 header.

    if (!IS_ADMIN)
    {
        //Attempt to load the old, deprecated error_404 page. If it
        //isn't there, then just send a 404 header and exit.
        $oldCI404Page = APPPATH . 'errors/error_404' . EXT;
        if(is_file($oldCI404Page) && is_readable($oldCI404Page))
        {
            $error =& load_class('Exceptions');
            $error->show_404($page);
            exit;
        }
    }
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    exit(\RightNow\Utils\Config::getMessage(NUM_404_PAGE_NOT_FOUND_LBL) . str_repeat(' ', 512));
}


/**
* Error Logging Interface
*
* We use this as a simple mechanism to access the logging
* class and send messages to be logged.
*
* @access    public
* @return    void
*/
function log_message($level = 'error', $message, $php_error = FALSE)
{
    return;
}

/**
* Exception Handler
*
* This is the custom exception handler that is declaired at the top
* of Codeigniter.php.  The main reason we use this is permit
* PHP errors to be logged in our own log files since we may
* not have access to server logs. Since this function
* effectively intercepts PHP errors, however, we also need
* to display errors based on the current error_reporting level.
* We do that with the use of a PHP error template.
*
* @access    private
* @return    void
*/
function _exception_handler($severity, $message, $filepath, $line)
{
     // We don't bother with "strict" notices since they will fill up
     // the log file with information that isn't normally very
     // helpful.  For example, if you are running PHP 5 and you
     // use version 4 style class functions (without prefixes
     // like "public", "private", etc.) you'll get notices telling
     // you that these have been deprecated.

    if ($severity === E_STRICT || (IS_HOSTED && IS_OPTIMIZED))
    {
        return;
    }

    // Should we display the error?
    // We'll get the current error_reporting level and add its bits
    // with the severity bits to find out.

    if (($severity & error_reporting()) === $severity)
    {
        $error = load_class('Exceptions');
        $error->show_php_error($severity, $message, $filepath, $line);
    }
    return;
}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Hooks Class
 *
 * Provides a mechanism to extend the base system without hacking.  Most of
 * this class is borrowed from Paul's Extension class in ExpressionEngine.
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Libraries
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/encryption.html
 */
class CI_Hooks {

    public $enabled = false;
    public $in_progress = false;
    public $hooks = array(
        'post_controller_constructor' => array(
            array(
                'class' => 'RightNow\Hooks\CleanseData',
                'function' => 'cleanse',
                'filename' => 'CleanseData.php',
                'filepath' => 'Hooks',
            ),
            array(
                'class' => 'RightNow\Hooks\Clickstream',
                'function' => 'trackSession',
                'filename' => 'Clickstream.php',
                'filepath' => 'Hooks',
                'params' => 'normal',
            ),
            array(
                'class' => 'RightNow\\Hooks\\Acs',
                'function' => 'initialize',
                'filename' => 'Acs.php',
                'filepath' => 'Hooks',
            )
        ),
        'post_controller' => array(
            array(
                'class' => 'RightNow\Hooks\SqlMailCommit',
                'function' => 'commit',
                'filename' => 'SqlMailCommit.php',
                'filepath' => 'Hooks',
                'params' => true,
            ),
        ),
    );
    /**
     * Constructor
     *
     */
    function __construct()
    {
        $this->_initialize();
        log_message('debug', "Hooks Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Initialize the Hooks Preferences
     *
     * @access    private
     * @return    void
     */
      function _initialize()
      {
        $CFG =& load_class('Config');

        // If hooks are not enabled in the config file
        // there is nothing else to do

        if ($CFG->item('enable_hooks') == FALSE)
        {
            return;
        }

        // Grab the "hooks" definition file.
        // If there are no hooks, we're done.

        /* Normally CodeIngiter would slurp in hooks.php here, but we're trying
         * to minimize the number of files included, so I stuck the 3 hooks we
           define directly into this class above.
        @include(CPCORE.'config/hooks'.EXT);

        if ( ! isset($hook) OR ! is_array($hook))
        {
            return;
        }
         */
        $this->enabled = TRUE;
      }

    // --------------------------------------------------------------------

    /**
     * Call Hook
     *
     * Calls a particular hook
     *
     * @access    private
     * @param    string    the hook name
     * @return    mixed
     */
    function _call_hook($which = '')
    {
        if ( ! $this->enabled OR ! isset($this->hooks[$which]))
        {
            return FALSE;
        }

        if (isset($this->hooks[$which][0]) AND is_array($this->hooks[$which][0]))
        {
            foreach ($this->hooks[$which] as $val)
            {
                $this->_run_hook($val);
            }
        }
        else
        {
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
     * @access    private
     * @param    array    the hook details
     * @return    bool
     */
    function _run_hook($data)
    {
        if ( ! is_array($data))
        {
            return FALSE;
        }

        // -----------------------------------
        // Safety - Prevents run-away loops
        // -----------------------------------

        // If the script being called happens to have the same
        // hook call within it a loop can happen

        if ($this->in_progress == TRUE)
        {
            return;
        }

        // -----------------------------------
        // Set file path
        // -----------------------------------

        if ( ! isset($data['filepath']) OR ! isset($data['filename']))
        {
            return FALSE;
        }

        $filepath = CPCORE.$data['filepath'].'/'.$data['filename'];

        if ( ! is_readable($filepath))
        {
            return FALSE;
        }

        // -----------------------------------
        // Set class/function name
        // -----------------------------------

        $class        = FALSE;
        $function    = FALSE;
        $params        = '';

        if (isset($data['class']) AND $data['class'] != '')
        {
            $class = $data['class'];
        }

        if (isset($data['function']))
        {
            $function = $data['function'];
        }

        if (isset($data['params']))
        {
            $params = $data['params'];
        }

        if ($class === FALSE AND $function === FALSE)
        {
            return FALSE;
        }

        // -----------------------------------
        // Set the in_progress flag
        // -----------------------------------

        $this->in_progress = TRUE;

        // -----------------------------------
        // Call the requested class and/or function
        // -----------------------------------
        if ($class !== FALSE)
        {
            if ( ! class_exists($class))
            {
                require($filepath);
            }

            $HOOK = new $class;
            $HOOK->$function($params);
        }
        else
        {
            if ( ! function_exists($function))
            {
                require($filepath);
            }

            $function($params);
        }

        $this->in_progress = FALSE;
        return TRUE;
    }

}

// END CI_Hooks class


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Config Class
 *
 * This class contains functions that enable config files to be managed
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Libraries
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/config.html
 */
class CI_Config {

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

        /*****RNT CONFIG SECTION*****/

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
        'widgetInstanceIDs' => array(),
    );

    public $is_loaded = array();

    /**
     * Constructor
     *
     * Sets the $config data from the primary config.php file as a class variable
     *
     * @access   public
     * @param   string    the config file name
     * @param   boolean  if configuration values should be loaded into their own section
     * @param   boolean  true if errors should just return false, false if an error message should be displayed
     * @return  boolean  if the file was successfully loaded or not
     */
    function __construct()
    {

        /* Ernie: Since we've already started editing CI source code, theres no need to have the
           config details in another file so I've set them above except for index_page, which requires
           the use of an expression to set.
        $this->config =& get_config();
        */
        $this->config['index_page'] = SELF . '?';
        log_message('debug', "Config Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Load Config File
     *
     * @access    public
     * @param    string    the config file name
     * @return    boolean    if the file was loaded correctly
     */
    function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE)
    {
        $file = ($file == '') ? 'config' : str_replace(EXT, '', $file);

        if (in_array($file, $this->is_loaded, TRUE))
        {
            return TRUE;
        }

        if ( ! is_readable(CPCORE.'config/'.$file.EXT))
        {
            if ($fail_gracefully === TRUE)
            {
                return FALSE;
            }
            show_error('The configuration file '.$file.EXT.' does not exist.');
        }

        include(CPCORE.'config/'.$file.EXT);

        if ( ! isset($config) OR ! is_array($config))
        {
            if ($fail_gracefully === TRUE)
            {
                return FALSE;
            }
            show_error('Your '.$file.EXT.' file does not appear to contain a valid configuration array.');
        }

        if ($use_sections === TRUE)
        {
            if (isset($this->config[$file]))
            {
                $this->config[$file] = array_merge($this->config[$file], $config);
            }
            else
            {
                $this->config[$file] = $config;
            }
        }
        else
        {
            $this->config = array_merge($this->config, $config);
        }

        $this->is_loaded[] = $file;
        unset($config);

        log_message('debug', 'Config file loaded: config/'.$file.EXT);
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch a config file item
     *
     *
     * @access    public
     * @param    string    the config item name
     * @param    string    the index name
     * @param    bool
     * @return    string
     */
    function item($item, $index = '')
    {
        if ($index == '')
        {
            if ( ! isset($this->config[$item]))
            {
                return FALSE;
            }

            $pref = $this->config[$item];
        }
        else
        {
            if ( ! isset($this->config[$index]))
            {
                return FALSE;
            }

            if ( ! isset($this->config[$index][$item]))
            {
                return FALSE;
            }

            $pref = $this->config[$index][$item];
        }

        return $pref;
    }

      // --------------------------------------------------------------------

    // I replaced this entire function with its equivalent from CI v 1.7.2 in order to avoid calls to functions which were deprecated in PHP 5.3.
    /**
     * Fetch a config file item - adds slash after item
     *
     * The second parameter allows a slash to be added to the end of
     * the item, in the case of a path.
     *
     * @access  public
     * @param   string  the config item name
     * @param   bool
     * @return  string
     */
    function slash_item($item)
    {
        if ( ! isset($this->config[$item]))
        {
            return FALSE;
        }

        $pref = $this->config[$item];

        if ($pref != '' && substr($pref, -1) != '/')
        {
            $pref .= '/';
        }

        return $pref;
    }


    // --------------------------------------------------------------------

    /**
     * Site URL
     *
     * @access    public
     * @param    string    the URI string
     * @return    string
     */
    function site_url($uri = '')
    {
        if (is_array($uri))
        {
            $uri = implode('/', $uri);
        }

        if ($uri == '')
        {
            return $this->slash_item('base_url').$this->item('index_page');
        }
        else
        {
            $suffix = ($this->item('url_suffix') == FALSE) ? '' : $this->item('url_suffix');
            return $this->slash_item('base_url').$this->slash_item('index_page').preg_replace("|^/*(.+?)/*$|", "\\1", $uri).$suffix;
        }
    }

    // --------------------------------------------------------------------

    /**
     * System URL
     *
     * @access    public
     * @return    string
     */
    function system_url()
    {
        $x = explode("/", preg_replace("|/*(.+?)/*$|", "\\1", BASEPATH));
        return $this->slash_item('base_url').end($x).'/';
    }

    // --------------------------------------------------------------------

    /**
     * Set a config file item
     *
     * @access    public
     * @param    string    the config item key
     * @param    string    the config item value
     * @return    void
     */
    function set_item($item, $value)
    {
        $this->config[$item] = $value;
    }

}

// END CI_Config class


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Router Class
 *
 * Parses URIs and determines routing
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @author        Rick Ellis
 * @category    Libraries
 * @link        http://www.codeigniter.com/user_guide/general/routing.html
 */
class CI_Router {

    public $config;
    public $uri_string        = '';
    public $segments        = array();
    public $rsegments        = array();
    public $error_routes    = array();
    public $class            = '';
    public $method            = 'index';
    public $directory        = '';
    public $uri_protocol     = 'auto';
    public $default_controller;
    public $foundControllerInCpCore = true;
    private $fullPath = '';

    /**
     * Constructor
     *
     * Runs the route mapping function.
     */
    function __construct()
    {
        $this->config =& load_class('Config');
        $this->_set_route_mapping();
        log_message('debug', "Router Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Set the route mapping
     *
     * This function determines what should be served based on the URI request,
     * as well as any "routes" that have been set in the routing config file.
     *
     * @access    private
     * @return    void
     */
    function _set_route_mapping()
    {
        $this->default_controller = 'page';

        // Fetch the complete URI string
        $this->uri_string = $this->_get_uri_string();

        // If the URI contains only a slash we'll kill it
        if ($this->uri_string === '/')
        {
            $this->uri_string = '';
        }

        // Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
        if ($this->uri_string === '')
        {
            $this->set_class($this->default_controller);
            $this->set_method('index');

            log_message('debug', "No URI present. Default controller set.");
            return;
        }

        // Do we need to remove the suffix specified in the config file?
        if  ($this->config->item('url_suffix') !== "")
        {
            $this->uri_string = preg_replace("|".preg_quote($this->config->item('url_suffix'))."$|", "", $this->uri_string);
        }

        // Explode the URI Segments. The individual segments will
        // be stored in the $this->segments array.
        foreach(explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->uri_string)) as $val)
        {
            // Filter segments for security
            $val = trim($this->_filter_uri($val));

            if ($val !== '')
                $this->segments[] = $val;
        }

        // Parse any custom routing that may exist
        $this->_parse_routes();

        // Re-index the segment array so that it starts with 1 rather than 0
        $this->_reindex_segments();
    }

    // --------------------------------------------------------------------

    /**
     * Compile Segments
     *
     * This function takes an array of URI segments as
     * input, and puts it into the $this->segments array.
     * It also sets the current class/method
     *
     * @access    private
     * @param    array
     * @param    bool
     * @return    void
     */
    function _compile_segments($segments = array())
    {
        $segments = $this->_preventAgentSessionIdFromBeingTheMethod($this->_validate_segments($segments));
        if (count($segments) == 0)
        {
            return;
        }

        $this->set_class($segments[0]);

        if (isset($segments[1]))
        {
            $this->set_method($segments[1]);
        }

        // Update our "routed" segment array to contain the segments.
        // Note: If there is no custom routing, this array will be
        // identical to $this->segments
        $this->rsegments = $segments;
    }

    /**
     * If the method segment of the URI was going to be "session_id" then kill it and the next segment
     * since those are really authenitcation parameters being passed in.
     *
     * This change to the segments means that CI's URI segments are going to be different
     * from the value in REQUEST_URI.  Then again, they were already different in some cases,
     * so it's no big deal.
     */
    private function _preventAgentSessionIdFromBeingTheMethod($segments) {
        if (count($segments) >= 3 && $segments[1] === \RightNow\Controllers\Base::agentSessionIdKey) {
            array_splice($segments, 1, 2);
        }
        return $segments;
    }

    // --------------------------------------------------------------------

    /**
     * Validates the supplied segments.  Attempts to determine the path to
     * the controller.
     *
     * @access    private
     * @param    array
     * @return    array
     */
    function _validate_segments($segments)
    {
        if(!CUSTOM_CONTROLLER_REQUEST)
        {
            $possibleLocations = array(
                CPCORE. 'Controllers/' . ucfirst($segments[0]),
                CORE_FILES . 'compatibility/Controllers/' . ucfirst($segments[0])
            );
            foreach($possibleLocations as $controllerPath){
                //First check if the controller exists as specified, then check if it's in a sub directory.
                if (is_readable($controllerPath . EXT))
                {
                    $this->fullPath = $controllerPath . EXT;
                    return $segments;
                }
                if(is_dir($controllerPath))
                {
                    $this->set_directory(ucfirst($segments[0]));
                    $directorySegments = array_slice($segments, 1);
                    $expectedPath = "$controllerPath/" . ucfirst($directorySegments[0]) . EXT;
                    if (count($directorySegments) === 0 || !is_readable($expectedPath)){
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
        if(is_readable($customControllerBasePath . $segments[0] . EXT)){
            $this->fullPath = $customControllerBasePath . $segments[0] . EXT;
            return $segments;
        }
        if(is_readable($customControllerBasePath . ucfirst($segments[0]) . EXT)){
            $this->fullPath = $customControllerBasePath . ucfirst($segments[0]) . EXT;
            return $segments;
        }

        // Is the controller in a sub-folder?
        if (is_dir($customControllerBasePath . $segments[0]))
        {
            // Set the directory and remove it from the segment array
            $this->set_directory($segments[0]);
            array_shift($segments);

            // Does the requested controller exist in the sub-folder?
            if (count($segments) > 0){
                $expectedPathBasePath = $customControllerBasePath . $this->fetch_directory();
                if(is_readable($expectedPathBasePath . $segments[0] . EXT)){
                    $this->fullPath = $expectedPathBasePath . $segments[0] . EXT;
                    return $segments;
                }
                if(is_readable($expectedPathBasePath . ucfirst($segments[0]) . EXT)){
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
     * Re-index Segments
     *
     * This function re-indexes the $this->segment array so that it
     * starts at 1 rather then 0.  Doing so makes it simpler to
     * use functions like $this->uri->segment(n) since there is
     * a 1:1 relationship between the segment array and the actual segments.
     *
     * @access    private
     * @return    void
     */
    function _reindex_segments()
    {
        // Is the routed segment array different then the main segment array?
        $diff = (count(array_diff($this->rsegments, $this->segments)) == 0) ? FALSE : TRUE;

        $i = 1;
        foreach ($this->segments as $val)
        {
            $this->segments[$i++] = $val;
        }
        unset($this->segments[0]);

        if ($diff == FALSE)
        {
            $this->rsegments = $this->segments;
        }
        else
        {
            $i = 1;
            foreach ($this->rsegments as $val)
            {
                $this->rsegments[$i++] = $val;
            }
            unset($this->rsegments[0]);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get the URI String
     *
     * @access    private
     * @return    string
     */
    function _get_uri_string()
    {
        return (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
    }

    // --------------------------------------------------------------------

    /**
     * Parse the REQUEST_URI
     *
     * Due to the way REQUEST_URI works it usually contains path info
     * that makes it unusable as URI data.  We'll trim off the unnecessary
     * data, hopefully arriving at a valid URI that we can use.
     *
     * @access    private
     * @return    string
     */
    function _parse_request_uri()
    {
        if ( ! isset($_SERVER['REQUEST_URI']) OR $_SERVER['REQUEST_URI'] == '')
        {
            return '';
        }

        $request_uri = preg_replace("|/(.*)|", "\\1", str_replace("\\", "/", $_SERVER['REQUEST_URI']));

        if ($request_uri == '' OR $request_uri == SELF)
        {
            return '';
        }

        $fc_path = FCPATH;
        if (strpos($request_uri, '?') !== FALSE)
        {
            $fc_path .= '?';
        }

        $parsed_uri = explode("/", $request_uri);

        $i = 0;
        foreach(explode("/", $fc_path) as $segment)
        {
            if (isset($parsed_uri[$i]) AND $segment == $parsed_uri[$i])
            {
                $i++;
            }
        }

        $parsed_uri = implode("/", array_slice($parsed_uri, $i));

        if ($parsed_uri != '')
        {
            $parsed_uri = '/'.$parsed_uri;
        }

        return $parsed_uri;
    }

    // --------------------------------------------------------------------

    /**
     * Filter segments for malicious characters
     *
     * @access    private
     * @param    string
     * @return    string
     */
    function _filter_uri($str)
    {
        if ($this->config->item('permitted_uri_chars') != '')
        {
            if ( ! preg_match("|^[".preg_quote($this->config->item('permitted_uri_chars'))."]+$|i", $str))
            {
                exit('The URI you submitted has disallowed characters.');
            }
        }
            return $str;
    }

    // --------------------------------------------------------------------

    /**
     *  Parse Routes
     *
     * This function matches any routes that may exist in
     * the config/routes.php file against the URI to
     * determine if the class/method need to be remapped.
     *
     * @access    private
     * @return    void
     */
    function _parse_routes()
    {
        $this->_compile_segments($this->segments);
    }

    // --------------------------------------------------------------------

    /**
     * Set the class name
     *
     * @access    public
     * @param    string
     * @return    void
     */
    function set_class($class)
    {
        //From CodeIgniter 1.7.3: Prevent directory traversal using class name
        $this->class = str_replace(array('/', '.'), '', $class);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the current class
     *
     * @access    public
     * @return    string
     */
    function fetch_class()
    {
        return $this->class;
    }

    // --------------------------------------------------------------------

    /**
     *  Set the method name
     *
     * @access    public
     * @param    string
     * @return    void
     */
    function set_method($method)
    {
        $this->method = $method;
    }

    // --------------------------------------------------------------------

    /**
     *  Fetch the current method
     *
     * @access    public
     * @return    string
     */
    function fetch_method()
    {
        if ($this->method == $this->fetch_class())
        {
            return 'index';
        }

        return $this->method;
    }

    // --------------------------------------------------------------------

    /**
     *  Set the directory name
     *
     * @access    public
     * @param    string
     * @return    void
     */
    function set_directory($dir)
    {
        //From CodeIgniter 1.7.3: Prevent directory traversal using class name
        $this->directory = str_replace(array('/', '.'), '', $dir).'/';
    }

    // --------------------------------------------------------------------

    /**
     *  Fetch the sub-directory (if any) that contains the requested controller class
     *
     * @access    public
     * @return    string
     */
    function fetch_directory()
    {
        return $this->directory;
    }

    function fetchFullControllerPath(){
        return $this->fullPath;
    }

    /**
     * Hotswap out variables to load the 404 page. This allows us to keep the URL
     * the same as what the user typed in in addition to loading a normal page which
     * can render rn: tags. We are using the %error404% placeholder here because at
     * the point this executes, we haven't opened the config bases yet. So instead
     * we're denoting a placeholder which will be replaced with the config value within
     * the page controller.
     * @return Array The modified segment array
     */
    function setVariablesFor404Page()
    {
        $currentUriSegments = explode('/', $this->uri_string);
        if(IS_ADMIN) {
            $this->fullPath = CPCORE . 'Controllers/Admin/Overview.php';
            $this->uri_string = 'overview/admin404';
        }
        else {
            $this->fullPath = CPCORE . 'Controllers/Page.php';
            $this->uri_string = "page/render/%error404%";
        }
        $this->directory = '';
        $this->segments = $this->rsegments = explode('/', $this->uri_string);
        //Since we're swapping in the page controller, we need to denote that we're going to a
        //controller in the CPCORE directory.
        $this->foundControllerInCpCore = true;
        //Even though we're going to render the 404 page, we still want to persist the session
        //parameter if it exists in the URL. That way we don't create a new session for non-cookied
        //users when hitting the 404 page. There might be other URL parameters specified for the page
        //they attempted to access, but we're not going to persist those through since we don't know what they are.
        $sessionParameterSegment = array_search('session', $currentUriSegments, true);
        $sessionValue = null;
        //If the session parameter was found, grab the value in the next segment
        if($sessionParameterSegment !== false)
            $sessionValue = $currentUriSegments[$sessionParameterSegment + 1];
        if($sessionValue)
            array_push($this->segments, 'session', $sessionValue);
        return $this->segments;
    }

}
// END Router Class


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Output Class
 *
 * Responsible for sending final output to browser
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Output
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/output.html
 */
class CI_Output {

    public $final_output;
    public $cache_expiration    = 0;
    public $headers             = array();
    public $enable_profiler     = FALSE;


    function __construct()
    {
        log_message('debug', "Output Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Get Output
     *
     * Returns the current output string
     *
     * @access    public
     * @return    string
     */
    function get_output()
    {
        return $this->final_output;
    }

    // --------------------------------------------------------------------

    /**
     * Set Output
     *
     * Sets the output string
     *
     * @access    public
     * @param    string
     * @return    void
     */
    function set_output($output)
    {
        $this->final_output = $output;
    }

    // --------------------------------------------------------------------

    /**
     * Set Header
     *
     * Lets you set a server header which will be outputted with the final display.
     *
     * Note:  If a file is cached, headers will not be sent.  We need to figure out
     * how to permit header data to be saved with the cache data...
     *
     * @access    public
     * @param    string
     * @return    void
     */
    function set_header($header)
    {
        $this->headers[] = $header;
    }

    // --------------------------------------------------------------------

    /**
     * Enable/disable Profiler
     *
     * @access    public
     * @param    bool
     * @return    void
     */
    function enable_profiler($val = TRUE)
    {
        $this->enable_profiler = (is_bool($val)) ? $val : TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Set Cache
     *
     * @access    public
     * @param    integer
     * @return    void
     */
    function cache($time)
    {
        $this->cache_expiration = ( ! is_numeric($time)) ? 0 : $time;
    }

    // --------------------------------------------------------------------

    /**
     * Display Output
     *
     * All "view" data is automatically put into this variable by the controller class:
     *
     * $this->final_output
     *
     * This function sends the finalized output data to the browser along
     * with any server headers and profile data.  It also stops the
     * benchmark timer so the page rendering speed and memory usage can be shown.
     *
     * @access    public
     * @return    mixed
     */
    function _display($output = '')
    {
        // Note:  We use globals because we can't use $CI = get_instance()
        // since this function is sometimes called by the caching mechanism,
        // which happens before the CI super object is available.
        global $CFG;

        // --------------------------------------------------------------------

        // Set the output data
        if ($output == '')
        {
            $output =& $this->final_output;
        }

        // --------------------------------------------------------------------

        // Do we need to write a cache file?
        if ($this->cache_expiration > 0)
        {
            $this->_write_cache($output);
        }

        // --------------------------------------------------------------------

        // Is compression requested?
        if ($CFG->item('compress_output') === TRUE)
        {
            if (extension_loaded('zlib'))
            {
                if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) AND strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE)
                {
                    ob_start('ob_gzhandler');
                }
            }
        }

        // --------------------------------------------------------------------

        // Are there any server headers to send?
        if (count($this->headers) > 0)
        {
            foreach ($this->headers as $header)
            {
                @header($header);
            }
        }

        // --------------------------------------------------------------------

        // Does the get_instance() function exist?
        // If not we know we are dealing with a cache file so we'll
        // simply echo out the data and exit.
        if ( ! function_exists('get_instance'))
        {
            echo $output;
            log_message('debug', "Final output sent to browser");
            return TRUE;
        }

        // --------------------------------------------------------------------

        // Grab the super object.  We'll need it in a moment...
        $CI = get_instance();

        // --------------------------------------------------------------------

        // Does the controller contain a function named _output()?
        // If so send the output there.  Otherwise, echo it.
        if (method_exists($CI, '_output'))
        {
            $CI->_output($output);
        }
        else
        {
            echo $output;  // Send it to the browser!
        }

        log_message('debug', "Final output sent to browser");
    }

    // --------------------------------------------------------------------

    /**
     * Write a Cache File
     *
     * @access    public
     * @return    void
     */
    function _write_cache($output)
    {
        $CI = get_instance();
        $path = $CI->config->item('cache_path');

        $cache_path = ($path == '') ? BASEPATH.'cache/' : $path;

        if ( ! is_dir($cache_path) OR ! is_writable($cache_path))
        {
            return;
        }

        $uri =    $CI->config->item('base_url').
                $CI->config->item('index_page').
                $CI->uri->uri_string();

        $cache_path .= md5($uri);

        if ( ! $fp = @fopen($cache_path, 'wb'))
        {
            log_message('error', "Unable to write cache file: ".$cache_path);
            return;
        }

        $expire = time() + ($this->cache_expiration * 60);

        flock($fp, LOCK_EX);
        fwrite($fp, $expire.'TS--->'.$output);
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod($cache_path, 0777);

        log_message('debug', "Cache file written: ".$cache_path);
    }

    // --------------------------------------------------------------------

    /**
     * Update/serve a cached file
     *
     * @access    public
     * @return    void
     */
    function _display_cache(&$CFG, &$RTR)
    {
        $CFG =& load_class('Config');
        $RTR =& load_class('Router');

        $cache_path = ($CFG->item('cache_path') == '') ? BASEPATH.'cache/' : $CFG->item('cache_path');

        if ( ! is_dir($cache_path) OR ! is_writable($cache_path))
        {
            return FALSE;
        }

        // Build the file path.  The file name is an MD5 hash of the full URI
        $uri =    $CFG->item('base_url').
                $CFG->item('index_page').
                $RTR->uri_string;

        $filepath = $cache_path.md5($uri);

        if ( ! @is_readable($filepath))
        {
            return FALSE;
        }

        if ( ! $fp = @fopen($filepath, 'rb'))
        {
            return FALSE;
        }

        flock($fp, LOCK_SH);

        $cache = '';
        if (filesize($filepath) > 0)
        {
            $cache = fread($fp, filesize($filepath));
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        // Strip out the embedded timestamp
        if ( ! preg_match("/(\d+TS--->)/", $cache, $match))
        {
            return FALSE;
        }

        // Has the file expired? If so we'll delete it.
        if (time() >= trim(str_replace('TS--->', '', $match['1'])))
        {
            @unlink($filepath);
            log_message('debug', "Cache file has expired. File deleted");
            return FALSE;
        }

        // Display the cache
        $this->_display(str_replace($match['0'], '', $cache));
        log_message('debug', "Cache file is current. Sending it to browser.");
        return TRUE;
    }


}
// END Output Class


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Input Class
 *
 * Pre-processes global input data for security
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Input
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/input.html
 */
class CI_Input {
    public $use_xss_clean        = FALSE;
    public $ip_address            = FALSE;
    public $user_agent            = FALSE;
    public $allow_get_array    = FALSE;

    /**
     * Constructor
     *
     * Sets whether to globally enable the XSS processing
     * and whether to allow the $_GET array
     *
     * @access    public
     */
    function __construct()
    {
        log_message('debug', "Input Class Initialized");

        $CFG =& load_class('Config');
        $this->use_xss_clean    = ($CFG->item('global_xss_filtering') === TRUE) ? TRUE : FALSE;
        $this->allow_get_array    = ($CFG->item('enable_query_strings') === TRUE) ? TRUE : FALSE;
    }

    // --------------------------------------------------------------------

    // --------------------------------------------------------------------

    /**
     * Clean Input Data
     *
     * This is a helper function. It escapes data and
     * standardizes newline characters to \n
     *
     * @access    private
     * @param    string
     * @return string
     */
    function _clean_input_data($str)
    {
        if (is_array($str))
        {
            $new_array = array();
            foreach ($str as $key => $val)
            {
                $new_array[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }

            return $new_array;
        }

        // We strip slashes if magic quotes is on to keep things consistent
        if (get_magic_quotes_gpc())
        {
            $str = stripslashes($str);
        }

        // Should we filter the input data?
        if ($this->use_xss_clean === TRUE)
        {
            $str = $this->xss_clean($str);
        }

        // Standardize newlines
        return preg_replace("/\015\012|\015|\012/", "\n", $str);
    }

    // --------------------------------------------------------------------

    /**
     * Clean Keys
     *
     * This is a helper function. To prevent malicious users
     * from trying to exploit keys we make sure that keys are
     * only named with alpha-numeric text and a few other items.
     *
     * @access    private
     * @param    string
     * @return string
     */
    function _clean_input_keys($str)
    {
         if ( ! preg_match("/^[a-z0-9:_\/-\/!]+$/i", $str))
         {
            exit('Disallowed Key Characters: '.$str);
         }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the GET array
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string
     */
    function get($index = '', $xss_clean = FALSE)
    {
        if ( ! isset($_GET[$index]))
        {
            return FALSE;
        }

        if ($xss_clean === TRUE)
        {
            if (is_array($_GET[$index]))
            {
                foreach($_GET[$index] as $key => $val)
                {
                    $_GET[$index][$key] = $this->xss_clean($val);
                }
            }
            else
            {
                return $this->xss_clean($_GET[$index]);
            }
        }

        return $_GET[$index];
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the POST array
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string|false
     */
    function post($index = '', $xss_clean = FALSE)
    {
        if ( ! isset($_POST[$index]))
        {
            return FALSE;
        }

        if ($xss_clean === TRUE)
        {
            if (is_array($_POST[$index]))
            {
                foreach($_POST[$index] as $key => $val)
                {
                    $_POST[$index][$key] = $this->xss_clean($val);
                }
            }
            else
            {
                return $this->xss_clean($_POST[$index]);
            }
        }

        return $_POST[$index];
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the COOKIE array
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string
     */
    function cookie($index = '', $xss_clean = FALSE)
    {
        if ( ! isset($_COOKIE[$index]))
        {
            return FALSE;
        }

        if ($xss_clean === TRUE)
        {
            if (is_array($_COOKIE[$index]))
            {
                $cookie = array();
                foreach($_COOKIE[$index] as $key => $val)
                {
                    $cookie[$key] = $this->xss_clean($val);
                }

                return $cookie;
            }
            else
            {
                return $this->xss_clean($_COOKIE[$index]);
            }
        }
        else
        {
            return $_COOKIE[$index];
        }
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from either POST or GET
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string|boolean String value or False if not found
     */
    function request($index = '', $xssClean = true)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            // #post returns false if not found
            if (($result = $this->post($index)) === false) return false;
            return ($xssClean) ? str_replace('"', '&quot;', $result) : $result;
        }

        // #getParameter returns null if not found
        if (($result = \RightNow\Utils\Url::getParameter($index)) === null) return false;
        return ($xssClean) ? $result : str_replace("&quot;", '"', $result);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the SERVER array
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string
     */
    function server($index = '', $xss_clean = FALSE)
    {
        if ( ! isset($_SERVER[$index]))
        {
            return FALSE;
        }

        if ($xss_clean === TRUE)
        {
            return $this->xss_clean($_SERVER[$index]);
        }

        return $_SERVER[$index];
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the IP Address
     *
     * @access    public
     * @return string
     */
    function ip_address()
    {
        if ($this->ip_address !== FALSE)
        {
            return $this->ip_address;
        }

        if ($this->server('REMOTE_ADDR') AND $this->server('HTTP_CLIENT_IP'))
        {
             $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif ($this->server('REMOTE_ADDR'))
        {
             $this->ip_address = $_SERVER['REMOTE_ADDR'];
        }
        elseif ($this->server('HTTP_CLIENT_IP'))
        {
             $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif ($this->server('HTTP_X_FORWARDED_FOR'))
        {
             $this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if ($this->ip_address === FALSE)
        {
            $this->ip_address = '0.0.0.0';

            return $this->ip_address;
        }

        if (strstr($this->ip_address, ','))
        {
            $x = explode(',', $this->ip_address);
            $this->ip_address = end($x);
        }

        if ( ! $this->valid_ip($this->ip_address))
        {
            $this->ip_address = '0.0.0.0';
        }

        return $this->ip_address;
    }

    // --------------------------------------------------------------------

    /**
     * Validate IP Address
     *
     * Updated version suggested by Geert De Deckere
     *
     * @access    public
     * @param    string
     * @return string
     */
    function valid_ip($ip)
    {
        $ip_segments = explode('.', $ip);

        // Always 4 segments needed
        if (count($ip_segments) != 4)
        {
            return FALSE;
        }
        // IP can not start with 0
        if (substr($ip_segments[0], 0, 1) == '0')
        {
            return FALSE;
        }
        // Check each segment
        foreach ($ip_segments as $segment)
        {
            // IP segments must be digits and can not be
            // longer than 3 digits or greater then 255
            if (preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
            {
                return FALSE;
            }
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * User Agent
     *
     * @access    public
     * @return string
     */
    function user_agent()
    {
        if ($this->user_agent !== FALSE)
        {
            return $this->user_agent;
        }

        $this->user_agent = ( ! isset($_SERVER['HTTP_USER_AGENT'])) ? FALSE : $_SERVER['HTTP_USER_AGENT'];

        return $this->user_agent;
    }

    // --------------------------------------------------------------------

    /**
     * Filename Security
     *
     * @access    public
     * @param    string
     * @return string
     */
    function filename_security($str)
    {
        $bad = array(
                        "../",
                        "./",
                        "<!--",
                        "-->",
                        "<",
                        ">",
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
                        "%20",
                        "%22",
                        "%3c",        // <
                        "%253c",     // <
                        "%3e",         // >
                        "%0e",         // >
                        "%28",         // (
                        "%29",         // )
                        "%2528",     // (
                        "%26",         // &
                        "%24",         // $
                        "%3f",         // ?
                        "%3b",         // ;
                        "%3d"        // =
                    );

        return stripslashes(str_replace($bad, '', $str));
    }

    // --------------------------------------------------------------------

    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.  This function does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.  Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: This function should only be used to deal with data
     * upon submission.  It's not something that should
     * be used for general runtime processing.
     *
     * This function was based in part on some code and ideas I
     * got from Bitflux: http://blog.bitflux.ch/wiki/XSS_Prevention
     *
     * To help develop this script I used this great list of
     * vulnerabilities along with a few other hacks I've
     * harvested from examining vulnerabilities in other programs:
     * http://ha.ckers.org/xss.html
     *
     * @access    public
     * @param    string
     * @return string
     */
    function xss_clean($str)
    {
        /*
         * Remove Null Characters
         *
         * This prevents sandwiching null characters
         * between ascii characters, like Java\0script.
         *
         */
        $str = preg_replace('/\0+/', '', $str);
        $str = preg_replace('/(\\\\0)+/', '', $str);

        /*
         * Validate standard character entities
         *
         * Add a semicolon if missing.  We do this to enable
         * the conversion of entities to ASCII later.
         *
         */
        $str = preg_replace('#(&\#?[0-9a-z]+)[\x00-\x20]*;?#i', "\\1;", $str);

        /*
         * Validate UTF16 two byte encoding (x00)
         *
         * Just as above, adds a semicolon if missing.
         *
         */
        $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);

        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Normally urldecode() would be easier but it removes plus signs
         *
         */
        $str = preg_replace("/(%20)+/", '9u3iovBnRThju941s89rKozm', $str);
        $str = preg_replace("/%u0([a-z0-9]{3})/i", "&#x\\1;", $str);
        $str = preg_replace("/%([a-z0-9]{2})/i", "&#x\\1;", $str);
        $str = str_replace('9u3iovBnRThju941s89rKozm', "%20", $str);

        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         *
         */

        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_attribute_conversion'), $str);

        $str = preg_replace_callback("/<([\w]+)[^>]*>/si", array($this, '_html_entity_decode_callback'), $str);

        /*

        Old Code that when modified to use preg_replace()'s above became more efficient memory-wise

        if (preg_match_all("/[a-z]+=([\'\"]).*?\\1/si", $str, $matches))
        {
            for ($i = 0; $i < count($matches[0]); $i++)
            {
                if (stristr($matches[0][$i], '>'))
                {
                    $str = str_replace(    $matches['0'][$i],
                                        str_replace('>', '&lt;', $matches[0][$i]),
                                        $str);
                }
            }
        }

        if (preg_match_all("/<([\w]+)[^>]*>/si", $str, $matches))
        {
            for ($i = 0; $i < count($matches[0]); $i++)
            {
                $str = str_replace($matches[0][$i],
                                    $this->_html_entity_decode($matches[0][$i], $charset),
                                    $str);
            }
        }
        */

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja    vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
         * so we use str_replace.
         *
         */

        $str = str_replace("\t", " ", $str);

        /*
         * Not Allowed Under Any Conditions
         */
        $bad = array(
                        'document.cookie'    => '[removed]',
                        'document.write'    => '[removed]',
                        '.parentNode'        => '[removed]',
                        '.innerHTML'        => '[removed]',
                        'window.location'    => '[removed]',
                        '-moz-binding'        => '[removed]',
                        '<!--'                => '&lt;!--',
                        '-->'                => '--&gt;',
                        '<!CDATA['            => '&lt;![CDATA['
                    );

        foreach ($bad as $key => $val)
        {
            $str = str_replace($key, $val, $str);
        }

        $bad = array(
                        "javascript\s*:"    => '[removed]',
                        "expression\s*\("    => '[removed]', // CSS and IE
                        "Redirect\s+302"    => '[removed]'
                    );

        foreach ($bad as $key => $val)
        {
            $str = preg_replace("#".$key."#i", $val, $str);
        }

        /*
         * Makes PHP tags safe
         *
         *  Note: XML tags are inadvertently replaced too:
         *
         *    <?xml
         *
         * But it doesn't seem to pose a problem.
         *
         */
        $str = str_replace(array('<?php', '<?PHP', '<?', '?'.'>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);

        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         * This was updated with latest CodeIgniter code from
         * https://github.com/bcit-ci/CodeIgniter/blob/1e35792cc2d231cba11c2faefd71717ab67a46d2/system/core/Security.php
         * to fix the deprecated "e" modifier use in preg_replace()
         */
        $words = array(
            'javascript', 'expression', 'vbscript', 'jscript', 'wscript',
            'vbs', 'script', 'base64', 'applet', 'alert', 'document',
            'write', 'cookie', 'window', 'confirm', 'prompt'
        );
        foreach ($words as $word)
        {
            $word = implode('\s*', str_split($word)).'\s*';
            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback('#('.substr($word, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
        }

        /*
         * Remove disallowed Javascript in links or img tags
         */
        do
        {
            $original = $str;

            if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '</a>') !== FALSE) OR
                 preg_match("/<\/a>/i", $str))
            {
                $str = preg_replace_callback("#<a.*?</a>#si", array($this, '_js_link_removal'), $str);
            }

            if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '<img') !== FALSE) OR
                 preg_match("/img/i", $str))
            {
                $str = preg_replace_callback("#<img.*?".">#si", array($this, '_js_img_removal'), $str);
            }

            if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && (stripos($str, 'script') !== FALSE OR stripos($str, 'xss') !== FALSE)) OR
                 preg_match("/(script|xss)/i", $str))
            {
                $str = preg_replace("#</*(script|xss).*?\>#si", "", $str);
            }
        }
        while($original != $str);

        unset($original);

        /*
         * Remove JavaScript Event Handlers
         *
         * Note: This code is a little blunt.  It removes
         * the event handler and anything up to the closing >,
         * but it's unlikely to be a problem.
         *
         */
        $event_handlers = array('onblur','onchange','onclick','onfocus','onload','onmouseover','onmouseup','onmousedown','onselect','onsubmit','onunload','onkeypress','onkeydown','onkeyup','onresize', 'xmlns');
        $str = preg_replace("#<([^>]+)(".implode('|', $event_handlers).")([^>]*)>#iU", "&lt;\\1\\2\\3&gt;", $str);

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         *
         */
        $str = preg_replace('#<(/*\s*)(alert|applet|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|layer|link|meta|object|plaintext|style|script|textarea|title|xml|xss)([^>]*)>#is', "&lt;\\1\\2\\3&gt;", $str);

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed.  Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example:    eval('some code')
         * Becomes:        eval&#40;'some code'&#41;
         *
         */
        $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);

        /*
         * Final clean up
         *
         * This adds a bit of extra precaution in case
         * something got through the above filters
         *
         */
        $bad = array(
                        'document.cookie'    => '[removed]',
                        'document.write'    => '[removed]',
                        '.parentNode'        => '[removed]',
                        '.innerHTML'        => '[removed]',
                        'window.location'    => '[removed]',
                        '-moz-binding'        => '[removed]',
                        '<!--'                => '&lt;!--',
                        '-->'                => '--&gt;',
                        '<!CDATA['            => '&lt;![CDATA['
                    );

        foreach ($bad as $key => $val)
        {
            $str = str_replace($key, $val, $str);
        }

        $bad = array(
                        "javascript\s*:"    => '[removed]',
                        "expression\s*\("    => '[removed]', // CSS and IE
                        "Redirect\s+302"    => '[removed]'
                    );

        foreach ($bad as $key => $val)
        {
            $str = preg_replace("#".$key."#i", $val, $str);
        }

        log_message('debug', "XSS Filtering completed");

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * JS Link Removal
     *
     * Callback function for xss_clean() to sanitize links
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings
     *
     * @access    private
     * @param    array
     * @return string
     */
    function _js_link_removal($match)
    {
        return preg_replace("#<a.+?href=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>.*?</a>#si", "", $match[0]);
    }

    /**
     * JS Image Removal
     *
     * Callback function for xss_clean() to sanitize image tags
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings
     *
     * @access    private
     * @param    array
     * @return string
     */
    function _js_img_removal($match)
    {
        return preg_replace("#<img.+?src=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>#si", "", $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * Attribute Conversion
     *
     * Used as a callback for XSS Clean
     *
     * @access    public
     * @param    array
     * @return string
     */
    function _attribute_conversion($match)
    {
        return str_replace('>', '&lt;', $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entity Decode Callback
     *
     * Used as a callback for XSS Clean
     *
     * @access    public
     * @param    array
     * @return string
     */
    function _html_entity_decode_callback($match)
    {
        $CI = get_instance();
        $charset = $CI->config->item('charset');

        return $this->_html_entity_decode($match[0], strtoupper($charset));
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entities Decode
     *
     * This function is a replacement for html_entity_decode()
     * This was updated with latest CodeIgniter code from
     * https://github.com/bcit-ci/CodeIgniter/blob/1e35792cc2d231cba11c2faefd71717ab67a46d2/system/core/Security.php
     * to fix the deprecated "e" modifier use in preg_replace()
     *
     * In some versions of PHP the native function does not work
     * when UTF-8 is the specified character set, so this gives us
     * a work-around.  More info here:
     * http://bugs.php.net/bug.php?id=25670
     *
     * @access    private
     * @param    string
     * @param    string
     * @return string
     */
    /* -------------------------------------------------
    /*  Replacement for html_entity_decode()
    /* -------------------------------------------------*/

    /*
    NOTE: html_entity_decode() has a bug in some PHP versions when UTF-8 is the
    character set, and the PHP developers said they were not back porting the
    fix to versions other than PHP 5.x.
    */

    public function _html_entity_decode($str, $charset = NULL)
    {
        if (strpos($str, '&') === FALSE)
        {
            return $str;
        }
        static $_entities;
        isset($charset) OR $charset = $this->charset;
        $flag = (phpversion() === '5.4')
            ? ENT_COMPAT | ENT_HTML5
            : ENT_COMPAT;
        do
        {
            $str_compare = $str;
            // Decode standard entities, avoiding false positives
            if ($c = preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches))
            {
                if ( ! isset($_entities))
                {
                    $_entities = array_map(
                        'strtolower',
                        (phpversion() === '5.3.4')
                            ? get_html_translation_table(HTML_ENTITIES, $flag, $charset)
                            : get_html_translation_table(HTML_ENTITIES, $flag)
                    );
                    // If we're not on PHP 5.4+, add the possibly dangerous HTML 5
                    // entities to the array manually
                    if ($flag === ENT_COMPAT)
                    {
                        $_entities[':'] = '&colon;';
                        $_entities['('] = '&lpar;';
                        $_entities[')'] = '&rpar';
                        $_entities["\n"] = '&newline;';
                        $_entities["\t"] = '&tab;';
                    }
                }
                $replace = array();
                $matches = array_unique(array_map('strtolower', $matches[0]));
                for ($i = 0; $i < $c; $i++)
                {
                    if (($char = array_search($matches[$i].';', $_entities, TRUE)) !== FALSE)
                    {
                        $replace[$matches[$i]] = $char;
                    }
                }
                $str = str_ireplace(array_keys($replace), array_values($replace), $str);
            }
            // Decode numeric & UTF16 two byte entities
            $str = html_entity_decode(
                preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str),
                $flag,
                $charset
            );
        }
        while ($str_compare !== $str);
        return $str;
    }

    /**
     * Compact Exploded Words
     *
     * Callback method for xss_clean() to remove whitespace from
     * things like 'j a v a s c r i p t'.
     *
     * @used-by CI_Security::xss_clean()
     * @param   array   $matches
     * @return  string
     */
    protected function _compact_exploded_words($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
    }

}
// END Input class


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * URI Class
 *
 * Parses URIs and determines routing
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    URI
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/uri.html
 */
class CI_URI {

    public $router;
    public $keyval = array();

    /**
     * Constructor
     *
     * Simply globalizes the $RTR object.  The front
     * loads the Router class early on so it's not available
     * normally as other classes are.
     *
     * @access    public
     */
    function __construct()
    {
        $this->router =& load_class('Router');
        log_message('debug', "URI Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Fetch a URI Segment
     *
     * This function returns the URI segment based on the number provided.
     *
     * @access    public
     * @param    integer
     * @param    bool
     * @return    string
     */
    function segment($n, $no_result = FALSE)
    {
        return ( ! isset($this->router->segments[$n])) ? $no_result : $this->router->segments[$n];
    }

    // --------------------------------------------------------------------

    /**
     * Fetch a URI "routed" Segment
     *
     * This function returns the re-routed URI segment (assuming routing rules are used)
     * based on the number provided.  If there is no routing this function returns the
     * same result as $this->segment()
     *
     * @access    public
     * @param    integer
     * @param    bool
     * @return    string
     */
    function rsegment($n, $no_result = FALSE)
    {
        return ( ! isset($this->router->rsegments[$n])) ? $no_result : $this->router->rsegments[$n];
    }

    // --------------------------------------------------------------------

    /**
     * Generate a key value pair from the URI string
     *
     * This function generates and associative array of URI data starting
     * at the supplied segment. For example, if this is your URI:
     *
     *    www.your-site.com/user/search/name/joe/location/UK/gender/male
     *
     * You can use this function to generate an array with this prototype:
     *
     * array (
     *            name => joe
     *            location => UK
     *            gender => male
     *         )
     *
     * @access    public
     * @param    integer    the starting segment number
     * @param    array    an array of default values
     * @return    array
     */
    function uri_to_assoc($n = 3, $default = array())
    {
         return $this->_uri_to_assoc($n, $default, 'segment');
    }
    /**
     * Identical to above only it uses the re-routed segment array
     *
     */
    function ruri_to_assoc($n = 3, $default = array())
    {
         return $this->_uri_to_assoc($n, $default, 'rsegment');
    }

    // --------------------------------------------------------------------

    /**
     * Generate a key value pair from the URI string or Re-routed URI string
     *
     * @access    private
     * @param    integer    the starting segment number
     * @param    array    an array of default values
     * @param    string    which array we should use
     * @return    array
     */
    function _uri_to_assoc($n = 3, $default = array(), $which = 'segment')
    {
        if ($which == 'segment')
        {
            $total_segments = 'total_segments';
            $segment_array = 'segment_array';
        }
        else
        {
            $total_segments = 'total_rsegments';
            $segment_array = 'rsegment_array';
        }

        if ( ! is_numeric($n))
        {
            return $default;
        }

        //Check cache for existing result (except for when we're running unit tests)
        if (isset($this->keyval[$n]) && !CUSTOM_CONTROLLER_REQUEST && $this->router->fetch_class() !== 'phpFunctional' && $this->router->fetch_class() !== 'widgetFunctional')
        {
            return $this->keyval[$n];
        }

        if ($this->$total_segments() < $n)
        {
            if (count($default) == 0)
            {
                return array();
            }

            $retval = array();
            foreach ($default as $val)
            {
                $retval[$val] = FALSE;
            }
            return $retval;
        }

        $segments = array_slice($this->$segment_array(), ($n - 1));

        $i = 0;
        $lastval = '';
        $retval  = array();
        foreach ($segments as $seg)
        {
            if ($i % 2)
            {
                $retval[$lastval] = $seg;
            }
            else
            {
                $retval[$seg] = FALSE;
                $lastval = $seg;
            }

            $i++;
        }

        if (count($default) > 0)
        {
            foreach ($default as $val)
            {
                if ( ! array_key_exists($val, $retval))
                {
                    $retval[$val] = FALSE;
                }
            }
        }

        // Cache the array for reuse
        $this->keyval[$n] = $retval;
        return $retval;
    }

    /**
     * Generate a URI string from an associative array
     *
     *
     * @access    public
     * @param    array    an associative array of key/values
     * @return    array
     */
    function assoc_to_uri($array)
    {
        $temp = array();
        foreach ((array)$array as $key => $val)
        {
            $temp[] = $key;
            $temp[] = $val;
        }

        return implode('/', $temp);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch a URI Segment and add a trailing slash
     *
     * @access    public
     * @param    integer
     * @param    string
     * @return    string
     */
    function slash_segment($n, $where = 'trailing')
    {
        return $this->_slash_segment($n, $where, 'segment');
    }

    // --------------------------------------------------------------------

    /**
     * Fetch a URI Segment and add a trailing slash
     *
     * @access    public
     * @param    integer
     * @param    string
     * @return    string
     */
    function slash_rsegment($n, $where = 'trailing')
    {
        return $this->_slash_segment($n, $where, 'rsegment');
    }

    // --------------------------------------------------------------------

    /**
     * Fetch a URI Segment and add a trailing slash - helper function
     *
     * @access    private
     * @param    integer
     * @param    string
     * @param    string
     * @return    string
     */
    function _slash_segment($n, $where = 'trailing', $which = 'segment')
    {
        if ($where == 'trailing')
        {
            $trailing    = '/';
            $leading    = '';
        }
        elseif ($where == 'leading')
        {
            $leading    = '/';
            $trailing    = '';
        }
        else
        {
            $leading    = '/';
            $trailing    = '/';
        }
        return $leading.$this->$which($n).$trailing;
    }

    // --------------------------------------------------------------------

    /**
     * Segment Array
     *
     * @access    public
     * @return    array
     */
    function segment_array()
    {
        return $this->router->segments;
    }

    // --------------------------------------------------------------------

    /**
     * Routed Segment Array
     *
     * @access    public
     * @return    array
     */
    function rsegment_array()
    {
        return $this->router->rsegments;
    }

    // --------------------------------------------------------------------

    /**
     * Total number of segments
     *
     * @access    public
     * @return    integer
     */
    function total_segments()
    {
        return count($this->router->segments);
    }

    // --------------------------------------------------------------------

    /**
     * Total number of routed segments
     *
     * @access    public
     * @return    integer
     */
    function total_rsegments()
    {
        return count($this->router->rsegments);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the entire URI string
     *
     * @access    public
     * @return    string
     */
    function uri_string()
    {
        return $this->router->uri_string;
    }


    // --------------------------------------------------------------------

    /**
     * Fetch the entire Re-routed URI string
     *
     * @access    public
     * @return    string
     */
    function ruri_string()
    {
        return '/'.implode('/', $this->rsegment_array()).'/';
    }

}
// END URI Class


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Loader Class
 *
 * Loads views and files
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @author        Rick Ellis
 * @category    Loader
 * @link        http://www.codeigniter.com/user_guide/libraries/loader.html
 */
class CI_Loader {

    // All these are set automatically. Don't mess with them.
    public $_ci_ob_level;
    public $_ci_view_path        = '';
    public $_ci_is_php5        = FALSE;
    public $_ci_is_instance     = FALSE; // Whether we should use $this or $CI = get_instance()
    public $_ci_cached_vars    = array();
    public $_ci_classes        = array();
    public $_ci_models            = array();
    public $_ci_helpers        = array();
    public $_ci_plugins        = array();
    public $_ci_scripts        = array();
    public $_ci_varmap            = array('unit_test' => 'unit', 'user_agent' => 'agent');

    private static $classesLoadedByCoreCodeIgniter = array('Themes', 'Rnow', 'User_agent', 'Encrypt',);

    /**
     * Constructor
     *
     * Sets the path to the view files and gets the initial output buffering level
     *
     * @access    public
     */
    function __construct()
    {
        $this->_ci_is_php5 = true;
        $this->_ci_view_path = APPPATH.'views/';
        $this->_ci_ob_level  = ob_get_level();

        log_message('debug', "Loader Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Class Loader
     *
     * This function lets users load and instantiate classes.
     * It is designed to be called from a user's app controllers.
     *
     * @access    public
     * @param    string    the name of the class
     * @param    mixed    the optional parameters
     * @return    void
     */
    function library($library = '', $constructorArguments = null)
    {
        if(!$library){
            return false;
        }

        if (is_array($library))
        {
            foreach ($library as $class){
                $this->loadLibrary($class, $constructorArguments);
            }
        }
        else{
            $this->loadLibrary($library, $constructorArguments);
        }
    }

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
            $filepath = BASEPATH . 'libraries/' . $library . EXT;
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
            get_instance()->$aliasName = new $name($constructorArguments);
        }
        else{
            get_instance()->$aliasName = new $name;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Model Loader
     *
     * This function lets users load and instantiate models. [It now uses our new autoloader in RightNow\Controllers\Base]
     *
     * @param $model [string] - String of the format <standard/custom>/<path>/<model name>
     * @param $name [string] - Alternate variable name with which to reference the model
     * @param return - None
     */
    public function model($model, $name = '')
    {
        if($model === '')
            return;

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
     * Load View
     *
     * This function is used to load a "view" file.  It has three parameters:
     *
     * 1. The name of the "view" file to be included.
     * 2. An associative array of data to be extracted for use in the view.
     * 3. TRUE/FALSE - whether to return the data or load it.  In
     * some cases it's advantageous to be able to return data so that
     * a developer can process it in some way.
     *
     * @access    public
     * @param    string
     * @param    array
     * @param    bool
     * @return    void
     */
    function view($view, $vars = array(), $return = FALSE)
    {
        return $this->_ci_load(array('view' => $view, 'vars' => $this->_ci_object_to_array($vars), 'return' => $return));
    }

    // --------------------------------------------------------------------

    /**
     * Load File
     *
     * This is a generic file loader
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return    string
     */
    function file($path, $return = FALSE)
    {
        return $this->_ci_load(array('path' => $path, 'return' => $return));
    }

    // --------------------------------------------------------------------

    /**
     * Set Variables
     *
     * Once variables are set they become available within
     * the controller class and its "view" files.
     *
     * @access    public
     * @param    array
     * @return    void
     */
    function vars($vars = array())
    {
        $vars = $this->_ci_object_to_array($vars);

        if (is_array($vars) AND count($vars) > 0)
        {
            foreach ($vars as $key => $val)
            {
                $this->_ci_cached_vars[$key] = $val;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Load Helper
     *
     * This function loads the specified helper file.
     *
     * @access    public
     * @param    mixed
     * @return    void
     */
    function helper($helpers = array())
    {
        if ( ! is_array($helpers))
        {
            $helpers = array($helpers);
        }

        foreach ($helpers as $helper)
        {
            $helper = strtolower(str_replace(EXT, '', str_replace('_helper', '', $helper)).'_helper');

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

        log_message('debug', 'Helpers loaded: '.implode(', ', $helpers));
    }

    // --------------------------------------------------------------------

    /**
     * Load Helpers
     *
     * This is simply an alias to the above function in case the
     * user has written the plural form of this function.
     *
     * @access    public
     * @param    array
     * @return    void
     */
    function helpers($helpers = array())
    {
        $this->helper($helpers);
    }

    // --------------------------------------------------------------------

    /**
     * Loader
     *
     * This function is used to load views and files.
     *
     * @access    private
     * @param    array
     * @return    void
     */
    function _ci_load($data)
    {
        // Set the default data variables
        foreach (array('view', 'vars', 'path', 'return') as $val)
        {
            $$val = ( ! isset($data[$val])) ? FALSE : $data[$val];
        }

        // Set the path to the requested file
        if ($path == '')
        {
            $ext = pathinfo($view, PATHINFO_EXTENSION);
            $file = ($ext == '') ? $view.EXT : $view;
            $path = $this->_ci_view_path.$file;
        }
        else
        {
            $x = explode('/', $path);
            $file = end($x);
        }

        if ( ! is_readable($path))
        {
            $path = CPCORE . 'Views/' . $file;
            if ( ! is_readable($path))
            {
                show_error('Unable to load the requested file: '.$file);
            }
        }

        // This allows anything loaded using $this->load (views, files, etc.)
        // to become accessible from within the Controller and Model functions.
        // Only needed when running PHP 5

        if ($this->_ci_is_instance())
        {
            $CI = get_instance();
            foreach (get_object_vars($CI) as $key => $var)
            {
                if ( ! isset($this->$key))
                {
                    $this->$key =& $CI->$key;
                }
            }
        }

        /*
         * Extract and cache variables
         *
         * You can either set variables using the dedicated $this->load_vars()
         * function or via the second parameter of this function. We'll merge
         * the two types and cache them so that views that are embedded within
         * other views can have access to these variables.
         */
        if (is_array($vars))
        {
            $this->_ci_cached_vars = array_merge($this->_ci_cached_vars, $vars);
        }
        extract($this->_ci_cached_vars);

        /*
         * Buffer the output
         *
         * We buffer the output for two reasons:
         * 1. Speed. You get a significant speed boost.
         * 2. So that the final rendered template can be
         * post-processed by the output class.  Why do we
         * need post processing?  For one thing, in order to
         * show the elapsed page load time.  Unless we
         * can intercept the content right before it's sent to
         * the browser and then stop the timer it won't be accurate.
         */
        ob_start();

        include($path);

        log_message('debug', 'File loaded: '.$path);

        // Return the file data if requested
        if ($return === TRUE)
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
         *
         */
        if (ob_get_level() > $this->_ci_ob_level + 1)
        {
            ob_end_flush();
        }
        else
        {
            // PHP 4 requires that we use a global
            global $OUT;
            $OUT->set_output(ob_get_contents());
            @ob_end_clean();
        }
    }

    // --------------------------------------------------------------------

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @access    private
     * @param    object
     * @return    array
     */
    function _ci_object_to_array($object)
    {
        return (is_object($object)) ? get_object_vars($object) : $object;
    }

    // --------------------------------------------------------------------

    /**
     * Determines whether we should use the CI instance or $this
     *
     * @access    private
     * @return    bool
     */
    function _ci_is_instance()
    {
        if ($this->_ci_is_php5 == TRUE)
        {
            return TRUE;
        }

        global $CI;
        return (is_object($CI)) ? TRUE : FALSE;
    }
}


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.3
 *
 * CI_BASE - For PHP 5
 *
 * This file contains some code used only when CodeIgniter is being
 * run under PHP 5.  It allows us to manage the CI super object more
 * gracefully than what is possible with PHP 4.
 *
 * @subpackage    codeigniter
 * @category    front-controller
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/
 * @internal
 */
class CI_Base {

    private static $instance;

    public function __construct()
    {
        if (self::$instance === null) {
            self::$instance =& $this;
        }
    }

    /**
     * Returns existing instance of base controller
     */
    public static function &get_instance()
    {
        return self::$instance;
    }
}

/**
 * Returns the instance of the currently executing controller. Numerous helpful properties and
 * methods can be accessed off the controller.
 *
 * ## Available properties
 * * session: The current session instance which allows access to the \RightNow\Libraries\Session object
 * * input: Reference to the CodeIgniter input class. Provides methods to get input parameters such as get(), post(), cookie().
 * * agent: Information about the current requests user agent.
 *
 * ## Available methods
 * * Look at the \RightNow\Libraries\Base controller for any public methods.
 *
 * @see \RightNow\Controllers\Base
 * @see \RightNow\Libraries\Session
 */
function &get_instance()
{
    return CI_Base::get_instance();
}




/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Application Controller Class
 *
 * This class object is the super class the every library in
 * CodeIgniter will be assigned to.
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Libraries
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/general/controllers.html
 */
class Controller extends CI_Base {

    /**
     * Constructor
     *
     * Calls the initialize() function
     */
    function __construct()
    {
        parent::__construct();
        $this->_ci_initialize();
        log_message('debug', "Controller Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Initialize
     *
     * Assigns all the bases classes loaded by the front controller to
     * variables in this class.  Also calls the autoload routine.
     *
     * @access    private
     * @return    void
     */
    function _ci_initialize()
    {
        // Assign all the class objects that were instantiated by the
        // front controller to local class variables so that CI can be
        // run as one big super object.
        $classes = array(
            'config' => 'Config',
            'input' => 'Input',
            'uri' => 'URI',
            'output' => 'Output',
            'load' => 'Loader',
            'agent' => 'User_agent',
        );

        foreach ($classes as $var => $class)
        {
            $this->$var =& load_class($class);
        }
    }
}
// END _Controller class


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Exceptions Class
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Exceptions
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/exceptions.html
 */
class CI_Exceptions {
    public $action;
    public $severity;
    public $message;
    public $filename;
    public $line;
    public $ob_level;

    public $levels = array(
                        E_ERROR                =>    'Error',
                        E_WARNING            =>    'Warning',
                        E_PARSE                =>    'Parsing Error',
                        E_NOTICE            =>    'Notice',
                        E_CORE_ERROR        =>    'Core Error',
                        E_CORE_WARNING        =>    'Core Warning',
                        E_COMPILE_ERROR        =>    'Compile Error',
                        E_COMPILE_WARNING    =>    'Compile Warning',
                        E_USER_ERROR        =>    'User Error',
                        E_USER_WARNING        =>    'User Warning',
                        E_USER_NOTICE        =>    'User Notice',
                        E_STRICT            =>    'Runtime Notice'
                    );


    /**
     * Constructor
     *
     */
    function __construct()
    {
        $this->ob_level = ob_get_level();
        // Note:  Do not log messages from this constructor.
    }

    // --------------------------------------------------------------------

    /**
     * Exception Logger
     *
     * This function logs PHP generated error messages
     *
     * @access    private
     * @param    string    the error severity
     * @param    string    the error string
     * @param    string    the error filepath
     * @param    string    the error line number
     * @return    string
     */
    function log_exception($severity, $message, $filepath, $line)
    {
        $severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

        log_message('error', 'Severity: '.$severity.'  --> '.$message. ' '.$filepath.' '.$line, TRUE);
    }

    // --------------------------------------------------------------------

    /**
     * 404 Page Not Found Handler
     *
     * @access    private
     * @param    string
     * @return    string
     */
    function show_404($page = '')
    {
        if($page === '' && $_SERVER['REQUEST_URI'] != '')
            $page = $_SERVER['REQUEST_URI'];

        $heading = '404 Page Not Found';
        $message = "The page you requested was not found.";

        log_message('error', '404 Page Not Found --> '.$page);
        echo $this->show_error($heading, $message, 'error_404', htmlspecialchars($page));
        exit;
    }

    // --------------------------------------------------------------------

    /**
     * General Error Page
     *
     * This function takes an error message as input
     * (either as a string or an array) and displays
     * it using the specified template.
     *
     * @access    private
     * @param    string    the heading
     * @param    string    the message
     * @param    string    the template name
     * @return    string
     */
    function show_error($heading, $message, $template = 'error_general', $page = '')
    {
        if (IS_HOSTED && IS_OPTIMIZED)
            return;

        $message = '<p>'.implode('</p><p>', ( ! is_array($message)) ? array($message) : $message).'</p>';

        if (ob_get_level() > $this->ob_level + 1)
        {
            ob_end_flush();
        }
        ob_start();
        include(APPPATH.'errors/'.$template.EXT);
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

    // --------------------------------------------------------------------

    /**
     * Native PHP error handler
     *
     * @access    private
     * @param    string    the error severity
     * @param    string    the error string
     * @param    string    the error filepath
     * @param    string    the error line number
     * @return    string
     */
    function show_php_error($severity, $message, $filepath, $line)
    {
        if (IS_HOSTED && IS_OPTIMIZED)
            return;

        $severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

        $filepath = str_replace("\\", "/", $filepath);

        // For safety reasons we do not show the full file path
        if (FALSE !== strpos($filepath, '/'))
        {
            $x = explode('/', $filepath);
            $filepath = $x[count($x)-2].'/'.end($x);
        }

        if (ob_get_level() > $this->ob_level + 1)
        {
            ob_end_flush();
        }
        ob_start();
        include(APPPATH.'errors/error_php'.EXT);
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }


}
// END Exceptions Class


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

        dl('libcmnapi' . sprintf(DLLVERSFX, MOD_CMN_BUILD_VER));

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
        dl('librnwapi' . sprintf(DLLVERSFX, MOD_BUILD_VER));
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
            $avi['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
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
        if (!((isset($_SERVER['HTTP_RNT_SSL']) && $_SERVER['HTTP_RNT_SSL'] === 'yes') || (!IS_HOSTED && $_SERVER['HTTPS'] === 'on')) && $this->getConfig($secHttpConfig)) {
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

        $controllerClassName = strtolower(get_instance()->uri->router->fetch_class());
        if (self::cpDisabledAndShouldExit($controllerClassName)) {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            //Expand the error message by duplicating spaces so that we actually display this message
            //and the browser doesn't show its default 404 page
            exit(self::getMessage(CUSTOMER_PORTAL_ENABLED_INTERFACE_MSG) . str_repeat(" ", 512));
        }
        if(self::getConfig(CP_MAINTENANCE_MODE_ENABLED) && IS_PRODUCTION && !CUSTOM_CONTROLLER_REQUEST && in_array($controllerClassName, array('page', 'facebook', 'widgetservice'))) {
             //Only display the splash page for page requests
             if($controllerClassName !== 'widgetservice'){
                 echo file_get_contents(DOCROOT . "/euf/config/splash.html");
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
                ($className === 'deploy' && ($methodName ?: strtolower(get_instance()->uri->router->fetch_method())) === 'servicepackdeploy')
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

        if (!IS_HOSTED && ($unsavedValue = self::$updatedConfigs[$slotID]) !== null) {
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
        if(!$slotValue = @constant($slotName)) {
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
        return Api::check_spider($_SERVER['HTTP_USER_AGENT'], NULL, $_SERVER['REMOTE_ADDR']);
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
            "${coreFiles}compatibility/Internal/Sql/Clickstream.php",
            "${coreFiles}compatibility/Internal/Sql/Pageset.php",
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
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * User Agent Class
 *
 * Identifies the platform, browser, robot, or mobile devise of the browsing agent
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    User Agent
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/user_agent.html
 */
class CI_User_agent {

    public $agent = null;

    public $is_browser = false;
    public $is_robot = false;
    public $is_mobile = false;

    public $languages = array();
    public $charsets = array();

    public $platforms = array(
        'windows nt 10.0'   => 'Windows 10',
        'windows nt 6.3'    => 'Windows 8.1',
        'windows nt 6.2'    => 'Windows 8',
        'windows nt 6.1'    => 'Windows 7',
        'windows nt 6.0'    => 'Windows Vista',
        'windows nt 5.2'    => 'Windows 2003',
        'windows nt 5.1'    => 'Windows XP',
        'windows nt 5.0'    => 'Windows 2000',
        'windows nt 4.0'    => 'Windows NT 4.0',
        'winnt4.0'          => 'Windows NT 4.0',
        'winnt 4.0'         => 'Windows NT',
        'winnt'             => 'Windows NT',
        'windows 98'        => 'Windows 98',
        'win98'             => 'Windows 98',
        'windows 95'        => 'Windows 95',
        'win95'             => 'Windows 95',
        'windows phone'     => 'Windows Phone',
        'windows'           => 'Unknown Windows OS',
        'android'           => 'Android',
        'blackberry'        => 'BlackBerry',
        'iphone'            => 'iOS',
        'ipad'              => 'iOS',
        'ipod'              => 'iOS',
        'os x'              => 'Mac OS X',
        'ppc mac'           => 'Power PC Mac',
        'freebsd'           => 'FreeBSD',
        'ppc'               => 'Macintosh',
        'linux'             => 'Linux',
        'debian'            => 'Debian',
        'sunos'             => 'Sun Solaris',
        'beos'              => 'BeOS',
        'apachebench'       => 'ApacheBench',
        'aix'               => 'AIX',
        'irix'              => 'Irix',
        'osf'               => 'DEC OSF',
        'hp-ux'             => 'HP-UX',
        'netbsd'            => 'NetBSD',
        'bsdi'              => 'BSDi',
        'openbsd'           => 'OpenBSD',
        'gnu'               => 'GNU/Linux',
        'unix'              => 'Unknown Unix OS',
        'webOS'             => 'Palm Web OS',
    );
    // The order of this array should NOT be changed. Many browsers return
    // multiple browser types so we want to identify the sub-type first.
    public $browsers = array(
        'Edge'              => 'Edge',
        'Chrome'            => 'Chrome',
        'criOS'             => 'Chrome for iOS',
        'Opera'             => 'Opera',
        'MSIE'              => 'Internet Explorer',
        'Internet Explorer' => 'Internet Explorer',
        'Trident'           => array('browser' => 'Internet Explorer', 'versionKey' => 'rv:'),
        'Shiira'            => 'Shiira',
        'Firefox'           => 'Firefox',
        'Chimera'           => 'Chimera',
        'Phoenix'           => 'Phoenix',
        'Firebird'          => 'Firebird',
        'Camino'            => 'Camino',
        'Netscape'          => 'Netscape',
        'OmniWeb'           => 'OmniWeb',
        'Safari'            => 'Safari',
        'Mozilla'           => 'Mozilla',
        'Konqueror'         => 'Konqueror',
        'icab'              => 'iCab',
        'Lynx'              => 'Lynx',
        'Links'             => 'Links',
        'hotjava'           => 'HotJava',
        'amaya'             => 'Amaya',
        'IBrowse'           => 'IBrowse',
        'Maxthon'           => 'Maxthon',
    );
    public $mobiles = array(
        // Legacy
        'mobileexplorer'       => 'Mobile Explorer',
        'palmsource'           => 'Palm',
        'palmscape'            => 'Palmscape',

        // Phones and Manufacturers
        'motorola'             => 'Motorola',
        'nokia'                => 'Nokia',
        'palm'                 => 'Palm',
        'iphone'               => 'Apple iPhone',
        'ipad'                 => 'Apple iPad',
        'ipod'                 => 'Apple iPod Touch',
        'sony'                 => 'Sony Ericsson',
        'ericsson'             => 'Sony Ericsson',
        'blackberry'           => 'BlackBerry',
        'cocoon'               => 'O2 Cocoon',
        'blazer'               => 'Treo',
        'lg'                   => 'LG',
        'amoi'                 => 'Amoi',
        'xda'                  => 'XDA',
        'mda'                  => 'MDA',
        'vario'                => 'Vario',
        'htc'                  => 'HTC',
        'samsung'              => 'Samsung',
        'sharp'                => 'Sharp',
        'sie-'                 => 'Siemens',
        'alcatel'              => 'Alcatel',
        'benq'                 => 'BenQ',
        'ipaq'                 => 'HP iPaq',
        'mot-'                 => 'Motorola',
        'playstation portable' => 'PlayStation Portable',
        'playstation 3'        => 'PlayStation 3',
        'playstation vita'     => 'PlayStation Vita',
        'hiptop'               => 'Danger Hiptop',
        'nec-'                 => 'NEC',
        'panasonic'            => 'Panasonic',
        'philips'              => 'Philips',
        'sagem'                => 'Sagem',
        'sanyo'                => 'Sanyo',
        'spv'                  => 'SPV',
        'zte'                  => 'ZTE',
        'sendo'                => 'Sendo',
        'nintendo dsi'         => 'Nintendo DSi',
        'nintendo ds'          => 'Nintendo DS',
        'nintendo 3ds'         => 'Nintendo 3DS',
        'wii'                  => 'Nintendo Wii',
        'open web'             => 'Open Web',
        'openweb'              => 'OpenWeb',

        // Operating Systems
        'android'              => 'Android',
        'symbian'              => 'Symbian',
        'SymbianOS'            => 'SymbianOS',
        'elaine'               => 'Palm',
        'series60'             => 'Symbian S60',
        'windows ce'           => 'Windows CE',

        // Browsers
        'obigo'                => 'Obigo',
        'netfront'             => 'Netfront Browser',
        'openwave'             => 'Openwave Browser',
        'mobilexplorer'        => 'Mobile Explorer',
        'operamini'            => 'Opera Mini',
        'opera mini'           => 'Opera Mini',
        'opera mobi'           => 'Opera Mobile',
        'fennec'               => 'Firefox Mobile',

        // Other
        'digital paths'        => 'Digital Paths',
        'avantgo'              => 'AvantGo',
        'xiino'                => 'Xiino',
        'novarra'              => 'Novarra Transcoder',
        'vodafone'             => 'Vodafone',
        'docomo'               => 'NTT DoCoMo',
        'o2'                   => 'O2',

        // Fallback
        'mobile'               => 'Generic Mobile',
        'wireless'             => 'Generic Mobile',
        'j2me'                 => 'Generic Mobile',
        'midp'                 => 'Generic Mobile',
        'cldc'                 => 'Generic Mobile',
        'up.link'              => 'Generic Mobile',
        'up.browser'           => 'Generic Mobile',
        'smartphone'           => 'Generic Mobile',
        'cellphone'            => 'Generic Mobile',
    );
    public $robots = array(
        'googlebot'     => 'Googlebot',
        'msnbot'        => 'MSNBot',
        'baiduspider'   => 'Baiduspider',
        'bingbot'       => 'Bing',
        'slurp'         => 'Inktomi Slurp',
        'yahoo'         => 'Yahoo',
        'askjeeves'     => 'AskJeeves',
        'fastcrawler'   => 'FastCrawler',
        'infoseek'      => 'InfoSeek Robot 1.0',
        'lycos'         => 'Lycos',
        'yandex'        => 'YandexBot',
    );

    public $platform = '';
    public $browser = '';
    public $version = '';
    public $mobile = '';
    public $robot = '';

    /**
     * Constructor
     *
     * Sets the User Agent and runs the compilation routine
     *
     * @access    public
     * @return    void
     */
    function __construct()
    {
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            $this->agent = trim($_SERVER['HTTP_USER_AGENT']);
        }

        if(!is_null($this->agent)){
            $this->_compile_data();
        }
        log_message('debug', "User Agent Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Compile the User Agent Data
     *
     * @access    private
     * @return    bool
     */
    function _compile_data()
    {
        $this->_set_platform();

        foreach (array('_set_browser', '_set_robot', '_set_mobile') as $function)
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
     * @access    private
     * @return    mixed
     */
    function _set_platform()
    {
        if (is_array($this->platforms) AND count($this->platforms) > 0)
        {
            foreach ($this->platforms as $key => $val)
            {
                if (preg_match("|".preg_quote($key)."|i", $this->agent))
                {
                    $this->platform = $val;
                    return TRUE;
                }
            }
        }
        $this->platform = 'Unknown Platform';
    }

    // --------------------------------------------------------------------

    /**
     * Set the Browser
     *
     * @access    private
     * @return    bool
     */
    function _set_browser()
    {
        if (is_array($this->browsers) AND count($this->browsers) > 0)
        {
            foreach ($this->browsers as $key => $val)
            {
                //Some of our more complicated user agent detection has different values to match to get browser type vs browser version
                if(is_array($val)){
                    if(stripos($this->agent, $key) === false){
                        continue;
                    }
                    $key = $val['versionKey'];
                    $val = $val['browser'];
                }
                if (preg_match("|".preg_quote($key).".*?([0-9\.]+)|i", $this->agent, $match))
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
     * @access    private
     * @return    bool
     */
    function _set_robot()
    {
        if (is_array($this->robots) AND count($this->robots) > 0)
        {
            foreach ($this->robots as $key => $val)
            {
                if (preg_match("|".preg_quote($key)."|i", $this->agent))
                {
                    $this->is_robot = TRUE;
                    $this->robot = $val;
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
     * @access    private
     * @return    bool
     */
    function _set_mobile()
    {
        if (is_array($this->mobiles) AND count($this->mobiles) > 0)
        {
            foreach ($this->mobiles as $key => $val)
            {
                if (FALSE !== (strpos(strtolower($this->agent), $key)))
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
     * @access    private
     * @return    void
     */
    function _set_languages()
    {
        if ((count($this->languages) == 0) AND isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) AND $_SERVER['HTTP_ACCEPT_LANGUAGE'] != '')
        {
            $languages = preg_replace('/(;q=.+)/i', '', trim($_SERVER['HTTP_ACCEPT_LANGUAGE']));

            $this->languages = explode(',', $languages);
        }

        if (count($this->languages) == 0)
        {
            $this->languages = array('Undefined');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set the accepted character sets
     *
     * @access    private
     * @return    void
     */
    function _set_charsets()
    {
        if ((count($this->charsets) == 0) AND isset($_SERVER['HTTP_ACCEPT_CHARSET']) AND $_SERVER['HTTP_ACCEPT_CHARSET'] != '')
        {
            $charsets = preg_replace('/(;q=.+)/i', '', trim($_SERVER['HTTP_ACCEPT_CHARSET']));

            $this->charsets = explode(',', $charsets);
        }

        if (count($this->charsets) == 0)
        {
            $this->charsets = array('Undefined');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Is Browser
     *
     * @access    public
     * @return    bool
     */
    function is_browser()
    {
        return $this->is_browser;
    }

    // --------------------------------------------------------------------

    /**
     * Is Robot
     *
     * @access    public
     * @return    bool
     */
    function is_robot()
    {
        return $this->is_robot;
    }

    // --------------------------------------------------------------------

    /**
     * Is Mobile
     *
     * @access    public
     * @return    bool
     */
    function is_mobile()
    {
        return $this->is_mobile;
    }

    // --------------------------------------------------------------------

    /**
     * Is this a referral from another site?
     *
     * @access    public
     * @return    bool
     */
    function is_referral()
    {
        return ( ! isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') ? FALSE : TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Agent String
     *
     * @access    public
     * @return    string
     */
    function agent_string()
    {
        return $this->agent;
    }

    // --------------------------------------------------------------------

    /**
     * Get Platform
     *
     * @access    public
     * @return    string
     */
    function platform()
    {
        return $this->platform;
    }

    // --------------------------------------------------------------------

    /**
     * Get Browser Name
     *
     * @access    public
     * @return    string
     */
    function browser()
    {
        return $this->browser;
    }

    // --------------------------------------------------------------------

    /**
     * Get the Browser Version
     *
     * @access    public
     * @return    string
     */
    function version()
    {
        return $this->version;
    }

    // --------------------------------------------------------------------

    /**
     * Get The Robot Name
     *
     * @access    public
     * @return    string
     */
    function robot()
    {
        return $this->robot;
    }
    // --------------------------------------------------------------------

    /**
     * Get the Mobile Device
     *
     * @access    public
     * @return    string
     */
    function mobile()
    {
        return $this->mobile;
    }

    // --------------------------------------------------------------------

    /**
     * Get the referrer
     *
     * @access    public
     * @return    bool
     */
    function referrer()
    {
        return ( ! isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') ? '' : trim($_SERVER['HTTP_REFERER']);
    }

    // --------------------------------------------------------------------

    /**
     * Get the accepted languages
     *
     * @access    public
     * @return    array
     */
    function languages()
    {
        if (count($this->languages) == 0)
        {
            $this->_set_languages();
        }

        return $this->languages;
    }

    // --------------------------------------------------------------------

    /**
     * Get the accepted Character Sets
     *
     * @access    public
     * @return    array
     */
    function charsets()
    {
        if (count($this->charsets) == 0)
        {
            $this->_set_charsets();
        }

        return $this->charsets;
    }

    // --------------------------------------------------------------------

    /**
     * Test for a particular language
     *
     * @access    public
     * @return    bool
     */
    function accept_lang($lang = 'en')
    {
        return (in_array(strtolower($lang), $this->languages(), TRUE)) ? TRUE : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Test for a particular character set
     *
     * @access    public
     * @return    bool
     */
    function accept_charset($charset = 'utf-8')
    {
        return (in_array(strtolower($charset), $this->charsets(), TRUE)) ? TRUE : FALSE;
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
    function supportedMobileBrowser()
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
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Encryption Class
 *
 * Provides two-way keyed encoding using XOR Hashing and Mcrypt
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Libraries
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/encryption.html
 */
class CI_Encrypt {

    public $encryption_key    = '';
    public $_hash_type    = 'sha1';
    public $_mcrypt_exists = FALSE;
    public $_mcrypt_cipher;
    public $_mcrypt_mode;

    /**
     * Constructor
     *
     * Simply determines whether the mcrypt library exists.
     *
     */
    function __construct()
    {
        $this->_mcrypt_exists = ( ! function_exists('mcrypt_encrypt')) ? FALSE : TRUE;
        //log_message('debug', "Encrypt Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the encryption key
     *
     * Returns it as MD5 in order to have an exact-length 128 bit key.
     * Mcrypt is sensitive to keys that are not the correct length
     *
     * @access    public
     * @param    string
     * @return    string
     */
    function get_key($key = '')
    {
        if ($key == '')
        {
            if ($this->encryption_key != '')
            {
                return $this->encryption_key;
            }

            if(function_exists("get_instance"))
            {
                $CI = get_instance();
                $key = $CI->config->item('encryption_key');
            }

            if ($key === FALSE)
            {
                show_error('In order to use the encryption class requires that you set an encryption key in your config file.');
            }
        }

        return md5($key);
    }

    // --------------------------------------------------------------------

    /**
     * Set the encryption key
     *
     * @access    public
     * @param    string
     * @return    void
     */
    function set_key($key = '')
    {
        $this->encryption_key = $key;
    }

    // --------------------------------------------------------------------

    /**
     * Encode
     *
     * Encodes the message string using bitwise XOR encoding.
     * The key is combined with a random hash, and then it
     * too gets converted using XOR. The whole thing is then run
     * through mcrypt (if supported) using the randomized key.
     * The end result is a double-encrypted message string
     * that is randomized with each call to this function,
     * even if the supplied message and key are the same.
     *
     * @access    public
     * @param    string    the string to encode
     * @param    string    the key
     * @return    string
     */
    function encode($string, $key = '')
    {
        $key = $this->get_key($key);
        $enc = $this->_xor_encode($string, $key);

        if ($this->_mcrypt_exists === TRUE)
        {
            $enc = $this->mcrypt_encode($enc, $key);
        }
        return base64_encode($enc);
    }

    // --------------------------------------------------------------------

    /**
     * Decode
     *
     * Reverses the above process
     *
     * @access    public
     * @param    string
     * @param    string
     * @return    string
     */
    function decode($string, $key = '')
    {
        $key = $this->get_key($key);
        $dec = base64_decode($string);

         if ($dec === FALSE)
         {
             return FALSE;
         }

        if ($this->_mcrypt_exists === TRUE)
        {
            $dec = $this->mcrypt_decode($dec, $key);
        }

        return $this->_xor_decode($dec, $key);
    }

    // --------------------------------------------------------------------

    /**
     * XOR Encode
     *
     * Takes a plain-text string and key as input and generates an
     * encoded bit-string using XOR
     *
     * @access    private
     * @param    string
     * @param    string
     * @return    string
     */
    function _xor_encode($string, $key)
    {
        $rand = '';
        while (strlen($rand) < 32)
        {
            $rand .= mt_rand(0, mt_getrandmax());
        }

        $rand = $this->hash($rand);

        $enc = '';
        for ($i = 0; $i < strlen($string); $i++)
        {
            $enc .= substr($rand, ($i % strlen($rand)), 1).(substr($rand, ($i % strlen($rand)), 1) ^ substr($string, $i, 1));
        }

        return $this->_xor_merge($enc, $key);
    }

    // --------------------------------------------------------------------

    /**
     * XOR Decode
     *
     * Takes an encoded string and key as input and generates the
     * plain-text original message
     *
     * @access    private
     * @param    string
     * @param    string
     * @return    string
     */
    function _xor_decode($string, $key)
    {
        $string = $this->_xor_merge($string, $key);

        $dec = '';
        for ($i = 0; $i < strlen($string); $i++)
        {
            $dec .= (substr($string, $i++, 1) ^ substr($string, $i, 1));
        }

        return $dec;
    }

    // --------------------------------------------------------------------

    /**
     * XOR key + string Combiner
     *
     * Takes a string and key as input and computes the difference using XOR
     *
     * @access    private
     * @param    string
     * @param    string
     * @return    string
     */
    function _xor_merge($string, $key)
    {
        $hash = $this->hash($key);
        $str = '';
        for ($i = 0; $i < strlen($string); $i++)
        {
            $str .= substr($string, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Encrypt using Mcrypt
     *
     * @access    public
     * @param    string
     * @param    string
     * @return    string
     */
    function mcrypt_encode($data, $key)
    {
        $init_size = mcrypt_get_iv_size($this->_get_cipher(), $this->_get_mode());
        $init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
        return mcrypt_encrypt($this->_get_cipher(), $key, $data, $this->_get_mode(), $init_vect);
    }

    // --------------------------------------------------------------------

    /**
     * Decrypt using Mcrypt
     *
     * @access    public
     * @param    string
     * @param    string
     * @return    string
     */
    function mcrypt_decode($data, $key)
    {
        $init_size = mcrypt_get_iv_size($this->_get_cipher(), $this->_get_mode());
        $init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
        return rtrim(mcrypt_decrypt($this->_get_cipher(), $key, $data, $this->_get_mode(), $init_vect), "\0");
    }

    // --------------------------------------------------------------------

    /**
     * Set the Mcrypt Cipher
     *
     * @access    public
     * @param    constant
     * @return    string
     */
    function set_cipher($cipher)
    {
        $this->_mcrypt_cipher = $cipher;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Mcrypt Mode
     *
     * @access    public
     * @param    constant
     * @return    string
     */
    function set_mode($mode)
    {
        $this->_mcrypt_mode = $mode;
    }

    // --------------------------------------------------------------------

    /**
     * Get Mcrypt cipher Value
     *
     * @access    private
     * @return    string
     */
    function _get_cipher()
    {
        if ($this->_mcrypt_cipher == '')
        {
            $this->_mcrypt_cipher = MCRYPT_RIJNDAEL_256;
        }

        return $this->_mcrypt_cipher;
    }

    // --------------------------------------------------------------------

    /**
     * Get Mcrypt Mode Value
     *
     * @access    private
     * @return    string
     */
    function _get_mode()
    {
        if ($this->_mcrypt_mode == '')
        {
            $this->_mcrypt_mode = MCRYPT_MODE_ECB;
        }

        return $this->_mcrypt_mode;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Hash type
     *
     * @access    public
     * @param    string
     * @return    string
     */
    function set_hash($type = 'sha1')
    {
        $this->_hash_type = ($type != 'sha1' AND $type != 'md5') ? 'sha1' : $type;
    }

    // --------------------------------------------------------------------

    /**
     * Hash encode a string
     *
     * @access    public
     * @param    string
     * @return    string
     */
    function hash($str)
    {
        return ($this->_hash_type == 'sha1') ? $this->sha1($str) : md5($str);
    }

    // --------------------------------------------------------------------

    /**
     * Generate an SHA1 Hash
     *
     * @access    public
     * @param    string
     * @return    string
     */
    function sha1($str)
    {
        if ( ! function_exists('sha1'))
        {
            if ( ! function_exists('mhash'))
            {
                require_once(BASEPATH.'libraries/Sha1'.EXT);
                $SH = new CI_SHA;
                return $SH->generate($str);
            }
            else
            {
                return bin2hex(mhash(MHASH_SHA1, $str));
            }
        }
        else
        {
            return sha1($str);
        }
    }

}

// END CI_Encrypt class


/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * System Front Controller
 *
 * Loads the base classes and executes the request.
 *
 * @package        CodeIgniter
 * @subpackage    codeigniter
 * @category    Front-controller
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/
 */

// CI Version
define('CI_VERSION', '1.5.4');

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
 *  Define a custom error handler so we can log PHP errors
 * ------------------------------------------------------
 */
set_error_handler('_exception_handler');

/*
 * ------------------------------------------------------
 *  Instantiate the hooks class
 * ------------------------------------------------------
 */

// SMC - added eval to work around issue where these were not getting populated
// using eval essentially prevents these from being cached as opcodes
eval('$GLOBALS["EXT"] = $EXT =& load_class("Hooks");');

/*
 * ------------------------------------------------------
 *  Is there a "pre_system" hook?
 * ------------------------------------------------------
 */
$EXT->_call_hook('pre_system');

/*
 * ------------------------------------------------------
 *  Instantiate the base classes
 * ------------------------------------------------------
 */

// SMC - added eval to work around issue where these were not getting populated
// using eval essentially prevents these from being cached as opcodes
eval('$GLOBALS["CFG"]  = $CFG  =& load_class("Config");');
eval('$GLOBALS["RTR"]  = $RTR  =& load_class("Router");');
eval('$GLOBALS["OUT"]  = $OUT  =& load_class("Output");');
eval('$GLOBALS["IN"]   = $IN   =& load_class("Input");');
eval('$GLOBALS["URI"]  = $URI  =& load_class("URI");');

// Load the base controller class
load_class('Controller', FALSE);

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
        else if(class_exists($className)){
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
 *  Security check
 * ------------------------------------------------------
 *
 *  None of the functions in the app controller or the
 *  loader class can be called via the URI, nor can
 *  controller functions that begin with an underscore. Also
 *  make sure that the function requested is actually callable
 */

if (!class_exists($className) ||
    $method === 'controller' ||
    substr($method, 0, 1) === '_' ||
    !is_callable(array($className, $method)) ||
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
$EXT->_call_hook('pre_controller');

/*
 * ------------------------------------------------------
 *  Instantiate the controller and call requested method
 * ------------------------------------------------------
 */
// If the controller is in a subdirectory then we need to correctly set the
// parm_segment config so that getParameter() works.  We defaulted that config to
// 3, which is correct for a controller which is not in a subdirectory.
if ($RTR->fetch_directory())
{
    $controllerDirectorySegments = count(explode('/', $RTR->fetch_directory())) - 1;
    $CFG->set_item('parm_segment', $controllerDirectorySegments + 3); // One for the controller name; one for the method name; one for the stupid 1-based indexing.  And one for my homies.  And one for the road.  And one for the ditch.
}

/*
 * ------------------------------------------------------
 *  Instantiate the controller
 * ------------------------------------------------------
 */

if(CUSTOM_CONTROLLER_REQUEST)
{
    \RightNow\Utils\Framework::installPathRestrictions();
    //Ensure that custom controllers finish executing their constructors
    ob_start(function($buffer) use($CFG){
        if($CFG->item('completedConstructor'))
            return $buffer;
        exit('You are not allowed to exit within your controller constructor.');
    });
}
$GLOBALS['CI'] = $CI = new $className();

if(CUSTOM_CONTROLLER_REQUEST)
{
    $CFG->set_item('completedConstructor', true);
    $constructorContent = ob_get_clean();
    if(strlen($constructorContent))
        echo $constructorContent;
}


if(!($CI instanceof \RightNow\Controllers\Base) && !($CI instanceof \RightNow\Controllers\Admin\Base))
{
    exit(sprintf("Controller classes must derive from the \RightNow\Controllers\Base class. The '%s' controller does not.", $subDirectory . $className));
}

$getInstanceResult = get_instance();
if(!$getInstanceResult)
{
    exit(sprintf("Controller classes must call the parent class constructor. The '%s' controller does not.", $subDirectory . $className));
}

if(CUSTOM_CONTROLLER_REQUEST || !IS_OPTIMIZED)
{
    if($CI instanceof \RightNow\Controllers\Base)
    {
        if(!\RightNow\Controllers\Base::checkConstructor($CI))
        {
            exit(sprintf("Controller class must call the \RightNow\Controllers\Base parent class constructor, not the Controller parent class constructor. The '%s' controller does not.", $subDirectory . $className));
        }
    }
    else if(!\RightNow\Controllers\Admin\Base::checkConstructor($CI))
    {
        exit(sprintf("Controller class must call the \RightNow\Controllers\Admin\Base parent class constructor, not the Controller parent class constructor. The '%s' controller does not.", $subDirectory . $className));
    }
}
$EXT->_call_hook('post_controller_constructor');

$CI->_ensureContactIsAllowed();

// Is there a "remap" function?
if(method_exists($CI, '_remap'))
{
    $CI->_remap($method);
}
else
{
    //Call the requested method. Any URI segments present (besides the class/function) will be passed to the method for convenience
    call_user_func_array(array(&$CI, $method), array_slice($RTR->rsegments, (($RTR->fetch_directory() == '') ? 2 : 3)));
}
/*
 * ------------------------------------------------------
 *  Is there a "post_controller" hook?
 * ------------------------------------------------------
 */
$EXT->_call_hook('post_controller');

/*
 * ------------------------------------------------------
 *  Send the final rendered output to the browser
 * ------------------------------------------------------
 */

if ($EXT->_call_hook('display_override') === FALSE)
{
    $OUT->_display();
}

/*
 * ------------------------------------------------------
 *  Is there a "post_system" hook?
 * ------------------------------------------------------
 */
$EXT->_call_hook('post_system');
