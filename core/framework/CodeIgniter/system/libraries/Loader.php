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
?>