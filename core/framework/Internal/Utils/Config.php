<?
namespace RightNow\Internal\Utils;

use RightNow\Utils\Text,
    RightNow\Utils\Config as ConfigExternal;

class Config
{
    private static $javaScriptPattern = "@((?:[/]{2}|[/][*]).*)?\s*RightNow\s*[.]?\s*Interface\s*[.]\s*get(Message|Config)[(]\s*([^)]*)\s*[)]@";

    public static function messageReplacer($matches)
    {
        return '<?=' . self::getMsgForRnMsgTag($matches[0]) . ';?>';
    }

    public static function messageReplacerWithinPhp($matches)
    {
        return "' . " . self::getMsgForRnMsgTag($matches[0]) . " . '";
    }

    /**
     * Return one of the sandboxed configs (core, events, js, loginRequired) based
     * on the current mode (development, staging_xx, production).
     * If an error is encountered obtaining config value from sandboxedConfigs file,
     * revert to fetching from configbase.
     *
     * @param string $config One of 'core', 'events', 'js' or 'loginRequired'.
     * @return bool Value of config
     * @throws \Exception If an error is encountered except for errors obtaining value from file.
     */
    public static function getSandboxedConfig($config) {
        list($configValue, $exception) = \RightNow\Internal\Libraries\SandboxedConfigs::configValueFromMode($config);
        if ($exception && !$exception instanceof \RightNow\Internal\Libraries\ConfigFileException) {
            throw $exception;
        }
        return $configValue;
    }

    /**
     * Function to convert rn:config tags to configGetFrom function calls
     * within its own \<\?= tags
     *
     * @param array $matches The regex match for rn:config:...
     * @return string The PHP code get the config base entry specified by the tag
     */
    public static function configReplacer(array $matches)
    {
        return '<?=' . self::getConfigForRnConfigTag($matches[0]) . ';?>';
    }

    /**
     * Function to convert rn:config tags to configGetFrom function calls
     * inlined with other PHP code
     *
     * @param array $matches The regex match for rn:config:...
     * @return string The PHP code get the config base entry specified by the tag
     */
    public static function configReplacerWithinPhp(array $matches)
    {
        return "' . " . self::getConfigForRnConfigTag($matches[0]) . " . '";
    }

    /**
     * Parses the given content for all RightNow.Interface.getMessage and
     * RightNow.Interface.getConfig calls.
     * @param string $content The content in which to check for calls
     * @param string $file Path to the file that is being parsed; used as a key in the return array
     * @return array All matching elements
     *   Contains the following keys:
     *      -message
     *      -config
     *   Each of whose value is an array, (keyed by $file) containing every match (with duplicates removed).
     */
    public static function findAllJavaScriptMessageAndConfigCalls($content, $file) {
        $getCalls = function($pattern, $content, $file) {
            preg_match_all($pattern, $content, $matches);
            $calls = array();
            for ($i = 0; $i < count($matches[2]); $i++) {
                if ($matches[1][$i] === '' || (!Text::stringContains($matches[1][$i], '//') && !Text::stringContains($matches[1][$i], '/*'))) {
                    $calls[] = $matches[2][$i];
                }
            }
            return ($calls) ? array($file => array_unique($calls)) : $calls;
        };

        return array(
            'message' => $getCalls(str_replace('get(Message|Config)', 'getMessage', self::$javaScriptPattern), $content, $file),
            'config'  => $getCalls(str_replace('get(Message|Config)', 'getConfig', self::$javaScriptPattern), $content, $file),
        );
    }

    /**
     * Wraps arguments to `RightNow.Interface.getMessage` and `RightNow.Interface.getConfig`
     * calls with m4-ignore tags.
     * @param string $content JavaScript code
     * @return string Content with replacements made
     */
    public static function convertJavaScriptCompileSafeDefines($content) {
        return preg_replace_callback(self::$javaScriptPattern, function($matches) {
            $converted = $matches[0];
            foreach (explode(',', $matches[3]) as $arg) {
                // Wrap ea. argument since there may be legacy second params specifying message or config base.
                $converted = str_replace($arg, '<m4' . '-ignore>' . trim($arg) . '</m4' . '-ignore>', $converted);
            }
            return $converted;
        }, $content);
    }

    /**
    * Parses the given content for all
    *   RightNow.Field
    *   RightNow.Form
    *   RightNow.SearchFilter
    *   RightNow.ResultsDisplay
    * calls.
    *
    * @param string $content JavaScript code to look for calls in
    * @return array List off all found matches of the listed-above calls
    * @assert $content Is JavaScript code
    */
    public static function findAllJavaScriptHelperObjectCalls($content) {
        $helpers = array();
        $namedModules = array(
            'Field' => array('requires' => 'Form'),
            'Form' => true,
            'SearchFilter' => true,
            'ResultsDisplay' => array('aliased' => 'SearchFilter'),
            'SearchProducer' => true,
            'SearchConsumer' => array('aliased' => 'SearchProducer'),
            'ProductCategory' => true,
            'Avatar' => true,
            'RequiredLabel' => true
        );
        preg_match_all('@RightNow\s*[.]\s*(' . implode('|', array_keys($namedModules)) . ')[)(.]@', $content, $helpers);
        $helpers = $helpers[1];
        foreach ($helpers as $index => $module) {
            if ((isset($namedModules[$module]['requires']) && $dependency = $namedModules[$module]['requires']) && !in_array($dependency, $helpers)) {
                $helpers []= $dependency;
            }
            if (isset($namedModules[$module]['aliased']) && $alias = $namedModules[$module]['aliased']) {
                $helpers[$index] = $alias;
            }
        }
        return $helpers;
    }

    /**
     * Parse out JavaScript message base calls and get their values (or create errors).
     * @param array $messageBaseEntries An array of matches and the file they were found
     * @param bool $deployMode Denotes if values need to be escaped for the script compile process; defaults to false
     * @param bool $includeBaseMessages Whether to also return a base set of messages to include on every page; defaults to true
     * @return array First entry is an array containing all parsed entries; this array is keyed by slot names, where each value is itself an array with a 'value' key.
     *  Second entry is an array containing error messages; empty if no errors.
     */
    public static function parseJavascriptMessages(array $messageBaseEntries, $deployMode = false, $includeBaseMessages = true)
    {
        $messageBaseStrings = ($includeBaseMessages) ? self::getCoreJavaScriptMessages($deployMode) : array();
        return self::parseConfigOrMessageValues('message', $messageBaseEntries, $messageBaseStrings, $deployMode);
    }

    /**
     * Parse out JavaScript config base calls and get their values (or create errors).
     * @param array $configBaseEntries An array of matches and the file they were found
     * @param bool $deployMode Denotes if values need to be escaped for the script compile process; defaults to false
     * @param bool $includeBaseConfigs Whether to also return a base set of messages to include on every page; defaults to true
     * @param bool $includeChat Whether to include chat-specific configs; only included if $includeBaseMessages is also true; defaults to false
     * @return array First entry is an array containing all parsed entries; this array is keyed by slot names, where each value is itself an array with a 'value' key.
     *  Second entry is an array containing error messages; empty if no errors.
     */
    public static function parseJavascriptConfigs(array $configBaseEntries, $deployMode = false, $includeBaseConfigs = true, $includeChat = false)
    {
        $configBaseStrings = ($includeBaseConfigs) ? self::getCoreJavaScriptConfigs($deployMode, $includeChat) : array();
        return self::parseConfigOrMessageValues('config', $configBaseEntries, $configBaseStrings, $deployMode);
    }

    public static function convertScriptCompileSafeDefines($deployMode, $safeDefines, $returnSubArray = true)
    {
        list($openTag, $closeTag) = $deployMode ? array('<m4' . '-ignore>', '</m4' . '-ignore>') : array('', '');
        $toReturn = array();
        foreach ($safeDefines as $safeDefine => $slotID) {
            $safeDefine = substr($safeDefine, 1);
            $value = ($returnSubArray) ? array('value' => $slotID) : $slotID;
            $toReturn["{$openTag}{$safeDefine}{$closeTag}"] = $value;
        }
        return $toReturn;
    }

    /**
     * Function to convert rn:msg tags to message base defines
     *
     * @param string $rnMsgTag A rn:msg:... tag.
     * @param bool $returnFunctionCall Whether to return value or return function call of value
     * @return string The message base entry specified by the tag
     */
    private static function getMsgForRnMsgTag($rnMsgTag, $returnFunctionCall = true)
    {
        $matches = substr($rnMsgTag, 1, -1);
        if ((($message = Text::getSubstringAfter($matches, 'rn:msg:{')) !== false) && Text::endsWith($message, '}')) {
            // new style: #rn:msg:{message}:{DEFINE}#
            list($message, $context) = explode('}:{', substr($message, 0, -1));
            if ($returnFunctionCall) {
                $message = str_replace("'", "\'", $message);
                return ($context === null) ? "\\RightNow\\Utils\\Config::msg('$message')" : "\\RightNow\\Utils\\Config::msg('$message', '$context')";
            }
            return ConfigExternal::msg($message, $context);
        }
        else if (($message = explode(':', $matches)) && count($message) > 2) {
            // old style: #rn:msg:DEFINE#
            //for custom message base, pass name instead of id
            if(defined($message[2]) && constant($message[2]) > 1000000) {
                $name = str_replace("_", "-", $message[2]);
                return ($returnFunctionCall) ? "\\RightNow\\Utils\\Config::msgGetFrom($message[2], '$name')" : ConfigExternal::msgGetFrom($message[2]);
            }
            return ($returnFunctionCall) ? "\\RightNow\\Utils\\Config::msgGetFrom($message[2])" : ConfigExternal::msgGetFrom($message[2]);
        }

        return sprintf(ConfigExternal::getMessage(PCT_S_IS_BADLY_FORMED_MESSAGE_TAG_MSG), $rnMsgTag);
    }

    /**
     * Function to convert rn:config tags to configGetFrom function calls
     *
     * @param string $rnConfigTag A rn:config:... tag.
     * @return string The configGetFrom function call
     */
    private static function getConfigForRnConfigTag($rnConfigTag)
    {
        $matches = substr($rnConfigTag, 1, -1);
        $configContent = explode(':', $matches);
        if (count($configContent) < 3)
            return sprintf(ConfigExternal::getMessage(PCT_S_IS_BADLY_FORMED_CONFIG_TAG_MSG), $rnConfigTag);
        return "\\RightNow\\Utils\\Config::configGetFrom({$configContent[2]})";
    }

    /**
     * Matches the inner content of a matched RightNow.Interface.getMessage/getConfig
     * call.
     * @param string $content The content to match
     * @return array The matches array of the regular expression
     */
    private static function matchInterfaceCall($content)
    {
        preg_match('@(["\'])([A-Za-z0-9_]+)\1(?:,\s*(["\'])([A-Za-z_]+)\3)?@', $content, $matches);
        return $matches;
    }

    /**
     * Common function to parse out both config and messagebase entries and also
     * create any errors that may occur.
     * @param string $type The type of content to parse, either config or message
     * @param array|null $entries Matched entries from the pre-parsed content
     * (the sub-arrays returned from #findAllJavaScriptMessageAndConfigCalls)
     * Should be structured with filenames as keys whose values are arrays with the list of matches
     * @param array $parsedEntries Array of existing core entries
     * @param bool $deployMode Denotes if we're parsing entries for deployment
     * @return Array Parsed entries and any errors that occurred
     */
    private static function parseConfigOrMessageValues($type, $entries, array $parsedEntries, $deployMode)
    {
        if(!is_array($entries))
            return array($parsedEntries, array());
        $errors = array();
        $functionName = ($type === 'config') ? 'RightNow.Interface.getConfig()' : 'RightNow.Interface.getMessage()';
        foreach($entries as $reference => $list)
        {
            foreach($list as $value)
            {
                list(,, $slot) = self::matchInterfaceCall($value);
                if($slot)
                {
                    if(!array_key_exists($slot, $parsedEntries))
                    {
                        if(defined($slot) && $slotValue = constant($slot))
                        {
                            $key = ($deployMode) ? '<m4' . "-ignore>{$slot}</m4" . '-ignore>' : $slot;
                            $parsedEntries[$key] = array('value' => $slotValue);
                        }
                        else
                        {
                            $errorMessage = (Text::beginsWith($reference, 'custom/') || Text::beginsWith($reference, 'standard/'))
                                ? ConfigExternal::getMessageJS(PCT_S_WIDGET_REFERENCING_INV_ENTRY_MSG)
                                : ConfigExternal::getMessageJS(PCT_S_FILE_REFERENCING_INV_ENTRY_MSG);
                            $errors[] = sprintf($errorMessage, $reference, $slot, $functionName);
                        }
                    }
                }
                else
                {
                    $errorMessage = (Text::beginsWith($reference, 'custom/') || Text::beginsWith($reference, 'standard/'))
                        ? ConfigExternal::getMessageJS(PCT_S_WDGET_REFERENCING_INV_ENTRY_MSG)
                        : ConfigExternal::getMessageJS(PCT_S_FLE_REFERENCING_INV_ENTRY_MSG);
                    $errors[] = sprintf($errorMessage, $reference, $value, $functionName);
                }
            }
        }
        return array($parsedEntries, $errors);
    }

    /**
     * Returns a list of core messagebase values which are always passed into
     * JavaScript. Some are only passed in when in development mode.
     *
     * @param bool $deployMode Denotes if we need to escape values for the script compile process
     * @return array List of messages
     */
    private static function getCoreJavaScriptMessages($deployMode)
    {
        $coreMessages = self::convertScriptCompileSafeDefines($deployMode, array(
            '_ATTRIBUTES_LC_LBL' => ATTRIBUTES_LC_LBL,
            '_ATTRIBUTE_HAVENT_SPECIFIED_VALID_IT_LBL' => ATTRIBUTE_HAVENT_SPECIFIED_VALID_IT_LBL,
            '_BACK_LBL' => BACK_LBL,
            '_BEG_DIALOG_PLS_DISMISS_DIALOG_BEF_MSG' => BEG_DIALOG_PLS_DISMISS_DIALOG_BEF_MSG,
            '_CHANGED_LBL' => CHANGED_LBL,
            '_CLOSE_CMD' => CLOSE_CMD,
            '_COL_SAVE_ED_ERR_L_INV_E_PERMISSIONS_MSG' => COL_SAVE_ED_ERR_L_INV_E_PERMISSIONS_MSG,
            '_DIALOG_LBL' => DIALOG_LBL,
            '_DIALOG_PLEASE_READ_TEXT_DIALOG_MSG_MSG' => DIALOG_PLEASE_READ_TEXT_DIALOG_MSG_MSG,
            '_DIALOG_PLEASE_READ_TEXT_DIALOG_MSG_MSG' => DIALOG_PLEASE_READ_TEXT_DIALOG_MSG_MSG,
            '_END_DIALOG_PLS_DISMISS_DIALOG_BEF_MSG' => END_DIALOG_PLS_DISMISS_DIALOG_BEF_MSG,
            '_ERROR_LBL' => ERROR_LBL,
            '_ERRORS_LBL' => ERRORS_LBL,
            '_ERROR_PCT_S_LBL' => ERROR_PCT_S_LBL,
            '_ERROR_REQUEST_ACTION_COMPLETED_MSG' => ERROR_REQUEST_ACTION_COMPLETED_MSG,
            '_ERR_SUBMITTING_FORM_DUE_INV_INPUT_LBL' => ERR_SUBMITTING_FORM_DUE_INV_INPUT_LBL,
            '_ERR_SUBMITTING_SEARCH_MSG' => REQUEST_PLS_CHG_SEARCH_TERMS_TRY_MSG,
            '_HELP_LBL' => HELP_LBL,
            '_INFORMATION_LBL' => INFORMATION_LBL,
            '_INFORMATION_S_LBL' => INFORMATION_S_LBL,
            '_OK_LBL' => OK_LBL,
            '_PCT_S_ATTRIB_REQD_HAVENT_VALUE_MSG' => PCT_S_ATTRIB_REQD_HAVENT_VALUE_MSG,
            '_PG_COL_SAVE_ED_ERR_L_INV_E_PERMISSIONS_MSG' => PG_COL_SAVE_ED_ERR_L_INV_E_PERMISSIONS_MSG,
            '_REVEALED_DISP_TB_DD_OP_ADDTL_T_EXPOSED_MSG' => REVEALED_DISP_TB_DD_OP_ADDTL_T_EXPOSED_MSG,
            '_SOME_INST_ID_BUT_SEE_ITS_SATTRIBUTESS_MSG' => SOME_INST_ID_BUT_SEE_ITS_SATTRIBUTESS_MSG,
            '_SUCCESS_S_LBL' => SUCCESS_S_LBL,
            '_TEMPL_COL_SAVE_ED_ERR_INV_TEMPL_PRMSSNS_MSG' => TEMPL_COL_SAVE_ED_ERR_INV_TEMPL_PRMSSNS_MSG,
            '_THIS_WIDGET_HAS_NO_ATTRIBUTES_MSG' => THIS_WIDGET_HAS_NO_ATTRIBUTES_MSG,
            '_THIS_WIDGET_HAS_NO_VIEW_LBL' => THIS_WIDGET_HAS_NO_VIEW_LBL,
            '_VAL_PCT_S_ATTRIB_MINIMUM_VAL_ACCD_MSG' => VAL_PCT_S_ATTRIB_MINIMUM_VAL_ACCD_MSG,
            '_VAL_PCT_S_ATTRIB_MAX_VAL_ACCD_PCT_S_MSG' => VAL_PCT_S_ATTRIB_MAX_VAL_ACCD_PCT_S_MSG,
            '_VIEW_ATTRIBUTES_LBL' => VIEW_ATTRIBUTES_LBL,
            '_WARNING_LBL' => WARNING_LBL,
            '_WARNING_S_LBL' => WARNING_S_LBL,
            '_WIDGET_CHANGES_ERRORS_WILL_IGNORED_MSG' => WIDGET_CHANGES_ERRORS_WILL_IGNORED_MSG
        ));

        if(!$deployMode)
        {
            $developmentModeErrorMessages = self::convertScriptCompileSafeDefines($deployMode, array(
                '_FOLLOWING_WIDGET_JAVASCRIPT_SYNTAX_MSG' => FOLLOWING_WIDGET_JAVASCRIPT_SYNTAX_MSG,
                '_YOU_HAVE_PCT_D_WARNINGS_PAGE_LBL' => YOU_HAVE_PCT_D_WARNINGS_PAGE_LBL,
                '_YOU_HAVE_ONE_WARNING_PAGE_LBL' => ConfigExternal::getMessageJS(YOU_HAVE_ONE_WARNING_PAGE_LBL),
                '_YOU_HAVE_PCT_D_ERRORS_PAGE_LBL' => ConfigExternal::getMessageJS(YOU_HAVE_PCT_D_ERRORS_PAGE_LBL),
                '_YOU_HAVE_ONE_ERROR_PAGE_LBL' => ConfigExternal::getMessageJS(YOU_HAVE_ONE_ERROR_PAGE_LBL),
            ));
            $coreMessages = array_merge($coreMessages, $developmentModeErrorMessages);
        }
        return $coreMessages;
    }

    /**
     * Returns a list of core configbase values which are always passed into JavaScript.
     *
     * @param bool $deployMode Denotes if we need to escape values for the script compile process
     * @param bool $includeChat Whether or not to include chat configs
     * @return array List of configs, both name and value
     */
    private static function getCoreJavaScriptConfigs($deployMode, $includeChat)
    {
        $configs = array(
            '_DE_VALID_EMAIL_PATTERN' => DE_VALID_EMAIL_PATTERN,
            '_CP_HOME_URL' => CP_HOME_URL,
            '_CP_FILE_UPLOAD_MAX_TIME' => CP_FILE_UPLOAD_MAX_TIME,
            '_OE_WEB_SERVER' => OE_WEB_SERVER,
            '_SUBMIT_TOKEN_EXP' => SUBMIT_TOKEN_EXP
        );

        if($includeChat)
            $configs['_CHAT_CLUSTER_POOL_ID'] = CHAT_CLUSTER_POOL_ID;

        return self::convertScriptCompileSafeDefines($deployMode, $configs);
    }
}