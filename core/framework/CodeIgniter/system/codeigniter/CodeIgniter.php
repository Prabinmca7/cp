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
?>
