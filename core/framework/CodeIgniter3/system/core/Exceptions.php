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
