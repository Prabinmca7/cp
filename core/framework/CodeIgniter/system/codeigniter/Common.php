<?php
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
?>
