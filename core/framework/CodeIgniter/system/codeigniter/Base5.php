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


?>
