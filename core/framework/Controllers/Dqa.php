<?php

namespace RightNow\Controllers;

/**
 * A generic controller for DQA
 *
 * @internal
 */
final class Dqa extends Base
{
    private $context;
    private $productionMode = IS_PRODUCTION;

    public function __construct()
    {
        parent::__construct();
        //NOTE: Later version of CI has get_post function. For now, we have to do checking
        if (isset($_POST['data']))
        {
            $data = $this->input->post('data');
        }
        else{
            $params = \RightNow\Utils\Text::unescapeQuotes($this->uri->uri_to_assoc(3));
            $data = $params['data'];
        }
        if($data)
        {
            $this->context = json_decode(urldecode($data));
        }
    }

    /**
     * Inserts DQA data into clickstreams
     * @throws \Exception If an invalid action or type is found when in development or staging environment
     */
    public function publish()
    {
        if(!is_array($this->context) || count($this->context) === 0){
            $errorMsg = ("DQA context is not an array or is empty: " . var_export($this->context, true));
            if (!$this->productionMode) {
                throw new \Exception($errorMsg);
            }

            \RightNow\Api::phpoutlog($errorMsg);

            //nothing to insert
            return;
        }

        foreach ($this->context as $entry)
        {
            $type = $entry->type;
            $action = $entry->action;
            $validationResults = $this->_validateQuery($type, $action);
            if ($validationResults === true) {
                $this->model('Clickstream')->insertQuery($type, $action);
            }
            else {
                if ($this->productionMode) {
                     \RightNow\Api::phpoutlog($validationResults);
                }
                else {
                    throw new \Exception($validationResults);
                }
            }
        }
    }

    /**
     * Do nothing. This takes care of unauthenticated users.
     * @internal
     */
    public function _ensureContactIsAllowed() {}

    /**
     * Do a simple edit to make sure the type and action are the correct types.
     * @param int $type DQA type. Should be positive integer
     * @param object|array $action DQA data to be JSON encoded. Should be an array or object
     * @return boolean|string - Returns boolean true if parameters are valid else a string with an error message if they are not
     */
    private static function _validateQuery($type, $action) {
        if (!is_int($type) || $type < 1) {
            return "DQA type is not a positive integer: " . var_export($type, true);
        }
        if (!is_object($action) && !is_array($action)) {
            return "DQA action is not an array or object: " . var_export($action, true);
        }
        return true;
    }
}
