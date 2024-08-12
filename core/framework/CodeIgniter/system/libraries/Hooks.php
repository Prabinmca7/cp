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
 * CodeIgniter Hooks Class
 *
 * Provides a mechanism to extend the base system without hacking.  Most of
 * this class is borrowed from Paul's Extension class in ExpressionEngine.
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Libraries
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/encryption.html
 */
class CI_Hooks {

    public $enabled = false;
    public $in_progress = false;
    public $hooks = array(
        'post_controller_constructor' => array(
            array(
                'class' => 'RightNow\Hooks\CleanseData',
                'function' => 'cleanse',
                'filename' => 'CleanseData.php',
                'filepath' => 'Hooks',
            ),
            array(
                'class' => 'RightNow\Hooks\Clickstream',
                'function' => 'trackSession',
                'filename' => 'Clickstream.php',
                'filepath' => 'Hooks',
                'params' => 'normal',
            ),
            array(
                'class' => 'RightNow\\Hooks\\Acs',
                'function' => 'initialize',
                'filename' => 'Acs.php',
                'filepath' => 'Hooks',
            )
        ),
        'post_controller' => array(
            array(
                'class' => 'RightNow\Hooks\SqlMailCommit',
                'function' => 'commit',
                'filename' => 'SqlMailCommit.php',
                'filepath' => 'Hooks',
                'params' => true,
            ),
        ),
    );
    /**
     * Constructor
     *
     */
    function __construct()
    {
        $this->_initialize();
        log_message('debug', "Hooks Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Initialize the Hooks Preferences
     *
     * @access    private
     * @return    void
     */
      function _initialize()
      {
        $CFG =& load_class('Config');

        // If hooks are not enabled in the config file
        // there is nothing else to do

        if ($CFG->item('enable_hooks') == FALSE)
        {
            return;
        }

        // Grab the "hooks" definition file.
        // If there are no hooks, we're done.

        /* Normally CodeIngiter would slurp in hooks.php here, but we're trying
         * to minimize the number of files included, so I stuck the 3 hooks we
           define directly into this class above.
        @include(CPCORE.'config/hooks'.EXT);

        if ( ! isset($hook) OR ! is_array($hook))
        {
            return;
        }
         */
        $this->enabled = TRUE;
      }

    // --------------------------------------------------------------------

    /**
     * Call Hook
     *
     * Calls a particular hook
     *
     * @access    private
     * @param    string    the hook name
     * @return    mixed
     */
    function _call_hook($which = '')
    {
        if ( ! $this->enabled OR ! isset($this->hooks[$which]))
        {
            return FALSE;
        }

        if (isset($this->hooks[$which][0]) AND is_array($this->hooks[$which][0]))
        {
            foreach ($this->hooks[$which] as $val)
            {
                $this->_run_hook($val);
            }
        }
        else
        {
            $this->_run_hook($this->hooks[$which]);
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Run Hook
     *
     * Runs a particular hook
     *
     * @access    private
     * @param    array    the hook details
     * @return    bool
     */
    function _run_hook($data)
    {
        if ( ! is_array($data))
        {
            return FALSE;
        }

        // -----------------------------------
        // Safety - Prevents run-away loops
        // -----------------------------------

        // If the script being called happens to have the same
        // hook call within it a loop can happen

        if ($this->in_progress == TRUE)
        {
            return;
        }

        // -----------------------------------
        // Set file path
        // -----------------------------------

        if ( ! isset($data['filepath']) OR ! isset($data['filename']))
        {
            return FALSE;
        }

        $filepath = CPCORE.$data['filepath'].'/'.$data['filename'];

        if ( ! is_readable($filepath))
        {
            return FALSE;
        }

        // -----------------------------------
        // Set class/function name
        // -----------------------------------

        $class        = FALSE;
        $function    = FALSE;
        $params        = '';

        if (isset($data['class']) AND $data['class'] != '')
        {
            $class = $data['class'];
        }

        if (isset($data['function']))
        {
            $function = $data['function'];
        }

        if (isset($data['params']))
        {
            $params = $data['params'];
        }

        if ($class === FALSE AND $function === FALSE)
        {
            return FALSE;
        }

        // -----------------------------------
        // Set the in_progress flag
        // -----------------------------------

        $this->in_progress = TRUE;

        // -----------------------------------
        // Call the requested class and/or function
        // -----------------------------------
        if ($class !== FALSE)
        {
            if ( ! class_exists($class))
            {
                require($filepath);
            }

            $HOOK = new $class;
            $HOOK->$function($params);
        }
        else
        {
            if ( ! function_exists($function))
            {
                require($filepath);
            }

            $function($params);
        }

        $this->in_progress = FALSE;
        return TRUE;
    }

}

// END CI_Hooks class
?>
