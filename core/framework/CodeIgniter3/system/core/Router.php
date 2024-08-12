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
