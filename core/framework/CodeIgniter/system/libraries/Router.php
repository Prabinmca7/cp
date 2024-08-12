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
?>
