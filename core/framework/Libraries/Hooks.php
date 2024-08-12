<?php
namespace RightNow\Libraries;

use RightNow\Internal\Api,
    RightNow\Utils\Config;

/**
 * Handles calling of CP hooks and validates the hooks config file.
 */
class Hooks
{
    private static $hooks;
    private static $CI;

    /**
     * Validates a specific hook to ensure that it is correctly formed. This will validate that all hook indices are set.
     *
     * @param string $hookName The name of the hook to validate
     * @param array|null $hook The hook definition with class, function, and filepath indices.
     * @return mixed A string error message on failure or true if hook is valid
     */
    public static function validateHook($hookName, array $hook)
    {
        if(!$hook['class'])
            return sprintf(Config::getMessage(HOOK_ERR_HOOK_PCT_S_PCT_S_IDX_SET_MSG), $hookName, "class");
        if(!$hook['function'])
            return sprintf(Config::getMessage(HOOK_ERR_HOOK_PCT_S_PCT_S_IDX_SET_MSG), $hookName, "function");
        if(strpos($hook['filepath'], "../") !== false)
            return sprintf(Config::getMessage(HOOK_ERR_HOOK_PCT_S_FILEPATH_CONT_LBL), $hookName);
        return true;
    }

    /**
     * Executes the hook specified by $which and passes along $data to it
     *
     * @param string $which The name of the hook to execute
     * @param array|null &$data Data array to pass to the hook
     * @return string Custom error message if one is generated
     */
    public static function callHook($which, &$data)
    {
        // No, we do not want customer written hooks to modify the behavior of reference mode. It's supposed to be the unmodified pages. Nor should any custom code run in the admin area.
        // Modified the condition to allow only standard hooks to be added in OKCS reference mode.
        if((IS_REFERENCE && !Config::getConfig(OKCS_ENABLED) && Config::getConfig(MOD_RNANS_ENABLED)) || (IS_ADMIN && !IS_UNITTEST)) {
            return;
        }

        \RightNow\Utils\Framework::installPathRestrictions();
        return self::loadHooks($which, $data);
    }


    /**
     * Loads up the hooks file to check if the specified hook needs to be called
     *
     * @param string $which The name of the hook to load
     * @param array|null &$data The data to pass to the hook
     * @return string An error message if one is generated
     */
    private static function loadHooks($which, &$data)
    {
        //No hooks are defined
        if(self::$hooks === false)
            return;

        if(!self::$CI)
            self::$CI = get_instance();

        if(!self::$hooks)
        {
            // Added the condition for not processing the custom hooks in OKCS reference mode .
            if(!(IS_REFERENCE && Config::getConfig(OKCS_ENABLED) && !Config::getConfig(MOD_RNANS_ENABLED))) {
                require_once APPPATH . "config/hooks.php";
                self::$hooks = isset($rnHooks) ? $rnHooks : false;
                if(!is_array(self::$hooks))
                {
                    self::$hooks = false;
                }
                self::addStandardHooks();
            }
            else
            {
                self::addKAStandardHooks();
            }

            if (self::$hooks === false)
                return;
        }

        if(isset(self::$hooks[$which]))
        {
            if(isset(self::$hooks[$which][0]) && is_array(self::$hooks[$which][0]))
            {
                foreach (self::$hooks[$which] as $val)
                {
                    $returnVal = self::runHook($which, $val, $data);
                    if(is_string($returnVal))
                        return $returnVal;
                }
            }
            else
            {
                $returnVal = self::runHook($which, self::$hooks[$which], $data);
                if(is_string($returnVal))
                    return $returnVal;
            }
        }
    }

    /**
     * Adds standard hooks, if necessary. Currently only used with Siebel integration.
     *
     * @return void
     */
    private static function addStandardHooks()
    {
        if (Api::siebelEnabled())
        {
            if (self::$hooks === false)
                self::$hooks = array();

            if (!self::$hooks['pre_incident_create_save'])
                self::$hooks['pre_incident_create_save'] = array();
            self::$hooks['pre_incident_create_save'][] = array(
                'class' => 'Siebel',
                'function' => 'processRequest',
                'filepath' => '',
                'use_standard_model' => true,
            );

            if (!self::$hooks['pre_register_smart_assistant_resolution'])
                self::$hooks['pre_register_smart_assistant_resolution'] = array();
            self::$hooks['pre_register_smart_assistant_resolution'][] = array(
                'class' => 'Siebel',
                'function' => 'registerSmartAssistantResolution',
                'filepath' => '',
                'use_standard_model' => true,
            );
        }
        if(Config::getConfig(OKCS_ENABLED) && !(Config::getConfig(MOD_RNANS_ENABLED) && IS_PRODUCTION)) {
            if (self::$hooks === false)
                self::$hooks = array();

            if (!isset(self::$hooks['pre_retrieve_smart_assistant_answers']))
                self::$hooks['pre_retrieve_smart_assistant_answers'] = array();
            self::$hooks['pre_retrieve_smart_assistant_answers'][] = array(
                'class' => 'Okcs',
                'function' => 'retrieveSmartAssistantRequest',
                'filepath' => '',
                'use_standard_model' => true,
            );

            if (!isset(self::$hooks['okcs_site_map_answers']))
                self::$hooks['okcs_site_map_answers'] = array();
            self::$hooks['okcs_site_map_answers'][] = array(
                'class' => 'Okcs',
                'function' => 'getArticlesForSiteMap',
                'filepath' => '',
                'use_standard_model' => true,
            );
        }
    }

    /**
     * Validates and runs the hook specified
     *
     * @param string $hookName The name of the hook to execute
     * @param array $hookDetails Array defining details about the hook to execute
     * @param array|null &$data The data array to pass to the hook function handler
     * @return string An error message if one is generated
     */
    private static function runHook($hookName, array $hookDetails, &$data)
    {
        $valid = self::validateHook($hookName, $hookDetails);
        if($valid !== true)
        {
            echo $valid;
            exit;
        }

        $modelPath = self::getHookModelPath($hookDetails);
        $functionName = $hookDetails['function'];
        if(!method_exists(self::$CI->model($modelPath), $functionName))
        {
            printf(Config::getMessage(HOOK_ERR_HOOK_PCT_S_FUNC_PCT_S_EX_MSG), $hookName, $functionName, $hookDetails['class']);
            exit;
        }

        return self::$CI->model($modelPath)->$functionName($data);
    }

    /**
     * Returns the model path to execute the hook.
     *
     * @param array $hookDetails Array defining details about the hook to execute
     * @return string The model path to the hook
     */
    private static function getHookModelPath(array $hookDetails)
    {
        return isset($hookDetails['use_standard_model']) && $hookDetails['use_standard_model'] === true ? $hookDetails['class'] : ("custom/" . $hookDetails['filepath'] . "/" . $hookDetails['class']);
    }

    /**
     * Adds only KA standard hooks
     *
     * @return void
     */
    private static function addKAStandardHooks()
    {
        if(Config::getConfig(OKCS_ENABLED) && !(Config::getConfig(MOD_RNANS_ENABLED) && IS_PRODUCTION)) {
            if (self::$hooks === false)
                self::$hooks = array();

            if (!isset(self::$hooks['pre_retrieve_smart_assistant_answers']))
                self::$hooks['pre_retrieve_smart_assistant_answers'] = array();
            self::$hooks['pre_retrieve_smart_assistant_answers'][] = array(
                'class' => 'Okcs',
                'function' => 'retrieveSmartAssistantRequest',
                'filepath' => '',
                'use_standard_model' => true,
            );

            if (!isset(self::$hooks['okcs_site_map_answers']))
                self::$hooks['okcs_site_map_answers'] = array();
            self::$hooks['okcs_site_map_answers'][] = array(
                'class' => 'Okcs',
                'function' => 'getArticlesForSiteMap',
                'filepath' => '',
                'use_standard_model' => true,
            );
        }
    }
}
