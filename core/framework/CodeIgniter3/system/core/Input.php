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
