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
?>
