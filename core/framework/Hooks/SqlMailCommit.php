<?php

namespace RightNow\Hooks;
use RightNow\Connect\v1_4 as Connect;

/**
 * Runs at the shutdown of CP, and performs any commits to the database that have been queued up by the request.
 */
class SqlMailCommit
{
    private $callCommit = false;
    private $commitControllerList = array('configurations', 'ajax', 'ajaxrequest', 'ajaxrequestmin', 'ajaxrequestoptional', 'oit', 'page', 'pta', 'wgetrecipient');

    /**
     * Flag for the the unit tests to verify the disconnect has been called.
     */
    public static $disconnected = false;
    
    public function __construct()
    {
        $CI = get_instance();
        if(!$CI)
            return;
        if(in_array(strtolower($CI->router->class), $this->commitControllerList)){
            $this->callCommit = true;
        }
    }

    /**
     * Commits any outstanding SQL operations to the DB, which may invoke additional mail actions. Called automatically
     * when the CP framework is shutting down. Can be invoked manually using the \RightNow\Utils\Framework::runSqlMailCommitHook() method.
     * 
     * @param boolean $disconnectDatabase Flag to indicate whether the database connection to be closed or not
     * @return void
     */
    public function commit($disconnectDatabase = false)
    {
        if($this->callCommit || CUSTOM_CONTROLLER_REQUEST){
            Connect\ConnectAPI::commit();
        }
        
        if($disconnectDatabase) {
            if(!IS_HOSTED) {
                self::$disconnected = true;
                return;
            }
            \RightNow\Api::sql_disconnect();
        }
    }
}
