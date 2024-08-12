<?php
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
