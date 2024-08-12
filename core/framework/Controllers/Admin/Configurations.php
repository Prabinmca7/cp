<?php

namespace RightNow\Controllers\Admin;

use RightNow\Utils\Text,
    RightNow\Internal\Libraries\SandboxedConfigs;

class Configurations extends Base
{
    function __construct()
    {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');
    }

    function index()
    {
        $this->_render('configurations/index', array(), \RightNow\Utils\Config::getMessage(CONFIGURATION_LBL));
    }

    /**
     * Displays all of the user agent entries.
     *
     * @param string|null $message Will be sent back in the data array
     * @param int|string $error Error code or message
     */
    function pageSet($message = null, $error = 0)
    {
        $mappings = $this->model('Pageset')->getPageSetMappingArrays();
        $mappings['message'] = ($message) ? urldecode($message) : null;
        $mappings['error'] = $error;

        return $this->_render('configurations/pageSetMapping', $mappings, \RightNow\Utils\Config::getMessage(CONFIGURATION_LBL), array(
            'js'  => 'configurations/pageSetMapping',
            'css' => 'configurations/pageSetMapping',
        ));
    }

    /**
     * Enables the specified mapping ID.
     *
     * @param int $mappingID The id to disable
     * @param bool $enable True will enable, false will disable
     */
    function enablePageSet($mappingID, $enable)
    {
        if ($enable)
        {
            $mapping = end(array_filter($this->model('Pageset')->getPageSetMappingMergedArray(), function($item) use ($mappingID) {
                return ((int)$mappingID === $item->id);
            }));

            if ($mapping && !$this->model('Pageset')->isValueValid($mapping->value))
            {
                $this->_renderJSONAndExit(array(
                    'message' => Text::escapeHtml(sprintf(\RightNow\Utils\Config::getMessage(PTH_PG_SET_DOESNT_EX_CREATE_FLDR_MSG), "/cp/customer/development/views/pages/{$mapping->value}")),
                    'id' => null
                ));
            }
        }

        $success = $this->model('Pageset')->enableItem($mappingID, $enable);
        if ($success)
        {
            $message = sprintf(\RightNow\Utils\Config::getMessage(($enable)
                ? ITEM_PCT_D_ENABLED_STAGE_PROMOTE_MSG
                : ITEM_PCT_D_DISABLED_STAGE_PROMOTE_MSG), $mappingID);
        }
        else
        {
            $message = \RightNow\Utils\Config::getMessage(THERE_IS_PROBLEM_YOUR_REQUEST_MSG);
            $mappingID = null;
        }

        $this->_renderJSONAndExit(array('message' => Text::escapeHtml($message), 'id' => $mappingID));
    }

    /**
     * Deletes the user agent mapping entry.
     * @param int $mappingID The id of the mapping to disable
     */
    function deletePageSet($mappingID)
    {
        if ($this->model('Pageset')->deleteItem((int) $mappingID))
            $message = \RightNow\Utils\Config::getMessage(ITEM_HAS_BEEN_DELETED_MSG);
        else
            $message = \RightNow\Utils\Config::getMessage(THERE_IS_PROBLEM_YOUR_REQUEST_MSG);

        $this->_renderJSONAndExit(array('message' => $message));
    }

    /**
     * Saves the data from the post parameters.
     */
    function savePageSet()
    {
        $this->_returnResults($this->_savePageSetMappings());
    }

    /**
     * Saves the data from the post parameters.
     */
    function addPageSet()
    {
        $this->_returnResults($this->_savePageSetMappings('add'));
    }

    /**
    * Echoes a JSON-encoded object containing a status message
    * and the id of the saved mapping, if the save was successful.
    * @param int|string $result Result of operation or error message
    */
    private function _returnResults($result)
    {
        $this->_renderJSONAndExit(
            (is_int($result))
            ? array('message' => \RightNow\Utils\Config::getMessage(PG_SET_MAPPINGS_SAVED_PLS_STAGE_MSG), 'id' => $result)
            : array('message' => $result)
        );
    }

    /**
     * Traverses the form fields from the post data and saves them.
     * @param string $operation The type of operation to perform (add or update)
     * @return int ID of the added/updated mapping or String error message
     */
    private function _savePageSetMappings($operation = 'update')
    {
        $postData = $_POST;
        unset($postData['formToken']);
        $mappings = $regExErrors = $errors = array();
        foreach ($postData as $key => $value)
        {
            list($id, $inputField) = explode('_', $key);
            $id = intval($id);
            if($id > 0)
            {
                //pre-existing id must belong to a pre-existing mapping
                $combinedArray = &$mappings[$id];
            }
            else
            {
                //an id doesn't exist yet for a new mapping
                if($operation === 'add')
                    $combinedArray = &$mappings;
                else
                    $errors[] = sprintf(\RightNow\Utils\Config::getMessage(ERROR_ID_PCT_D_IS_NOT_VALID_UC_MSG), $id);
            }

            if ($inputField === 'item')
            {
                //value can be empty (for hook processing)
                $result = ($value === '') ? $value : @preg_match($value, 'abc');
                if ($result === false)
                    $regExErrors[] = $value;
                else
                    $combinedArray['item'] = $value;
            }
            else if ($inputField === 'description' || $inputField === 'value')
            {
                $value = trim($value, '/');
                if ($value === '')
                    $errors[] = ($inputField === 'value') ? \RightNow\Utils\Config::getMessage(PAGE_SET_MUST_CONTAIN_A_VALUE_MSG) : \RightNow\Utils\Config::getMessage(DESCRIPTION_MUST_CONTAIN_A_VALUE_MSG);

                if($inputField === 'value' && !$this->model('Pageset')->isValueValid($value))
                {
                    $errors[] = sprintf(\RightNow\Utils\Config::getMessage(PATH_PG_SET_DOESNT_EX_CREATE_FLDR_MSG), "/cp/customer/development/views/pages/$value");
                }
                $combinedArray[$inputField] = $value;
            }
        }
        if ($regExErrors || $errors)
        {
            if ($regExErrors)
            {
                $errors[] = sprintf(\RightNow\Utils\Config::getMessage(REGULAR_EXPRESSION_PCT_S_VALID_MSG), implode(', ', $regExErrors)) . ' ';
            }

            foreach($errors as &$error) {
                $error = Text::escapeHtml($error);
            }

            return $errors;
        }

        if($operation === 'add' && count($mappings) === 3)
        {
            $newMapping = $this->model('Pageset')->addItem($mappings);
            if($newMapping instanceof \RightNow\Libraries\PageSetMapping)
            {
                return $newMapping->id;
            }
        }
        else if($operation === 'update')
        {
            $this->model('Pageset')->updateItem($mappings);
            return $id;
        }
    }

    function sandboxedConfigs() {
        $headers = array(\RightNow\Utils\Config::getMessage(NAME_LBL), \RightNow\Utils\Config::getMessage(CONFIGURATION_LBL));
        $modes = SandboxedConfigs::modes(false);
        foreach($modes as $mode => $values) {
            $headers[] = $values['displayName'];
        }
        $configurations = array();
        foreach(SandboxedConfigs::configurations() as $config => $values) {
            $configurations[$config] = array_merge($values, array('configValue' => SandboxedConfigs::configValue($config)));
            $configurations[$config]['values'] = array();
            foreach(array_keys($modes) as $mode) {
                list($configValue) = SandboxedConfigs::configValueFromMode($config, $mode);
                $configurations[$config]['values'][] = $configValue;
            }
        }
        $this->_render('configurations/sandboxedConfigs', array(
            'headers' => $headers,
            'configurations' => $configurations,
        ), \RightNow\Utils\Config::getMessage(SANDBOXED_CONFIGURATIONS_LBL), array(
            'css' => 'configurations/sandboxedConfigs',
        ));
    }
}
