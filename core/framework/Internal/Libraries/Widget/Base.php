<?php

namespace RightNow\Internal\Libraries\Widget;

use RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Utils\Widgets;

abstract class Base
{
    protected $js = array();
    protected $attrs = array();
    protected $info = array();
    protected $parms = array();
    protected $ajaxHandlers = array();
    protected $CI;
    protected $data;
    protected $instanceID;
    protected $path;
    protected $viewContent;
    protected $hasCalledConstructor = false;
    protected $hasCalledAjaxHandlerSetter = false;
    protected static $serverConstraints = array();
    protected $inputName;
    protected $widgetPosition;

    private $viewFunctionName;

    public function __construct($manifestAttributes)
    {
        $this->hasCalledConstructor = is_array($manifestAttributes) ? true : null;
        $this->CI = get_instance();
        $this->setInfo('w_id', $this->getWidgetSerialNumber());
        $this->addManifestAttributes($manifestAttributes);
    }

    public function __get($name)
    {
        throw new \Exception("$name is not accessible.");
    }

    public function __set($name, $value)
    {
        throw new \Exception("$name is not accessible.");
    }

    /**
     * Defines the runtime abstract function which all widgets must implement
     */
    abstract function getData();

    /**
     * Transforms widget rn: tags into widget calls and displays widget
     *
     * @return string The eval'd buffer of rendered code
     */
    public function renderDevelopment()
    {
        if($this->hasCalledConstructor !== true)
        {
            echo $this->hasCalledConstructor === false ? self::widgetError($this->path, \RightNow\Utils\Config::getMessage(WIDGET_CONT_CALL_PARENT_MSG)) : self::widgetError($this->path, \RightNow\Utils\Config::getMessage(ARRAY_PASSED_WIDGET_CONSTRUCTORS_MSG));
            return;
        }
        $this->viewContent = \RightNow\Utils\Tags::transformTags($this->viewContent, null, $this);

        if (!$this->loadDataArray()) return;

        $ajaxContent = null;
        if(($widgetInstanceInfo = $this->setWidgetInstance('development')) && $this->CI->isAjaxRequest()) {
            $ajaxContent = $this->getAjaxContent($widgetInstanceInfo);
        }

        $widgetContent = trim(Framework::evalCodeAndCaptureOutputWithScope($this->viewContent, "widgets/{$this->path}", $this));

        $widgetContent = $this->addWidgetInspectorCustomAttributes($widgetContent);

        if($ajaxContent) {
            return $widgetContent . $ajaxContent;
        }

        return $widgetContent;
    }

    /**
     * Returns the string of the production widget
     * @return null|string The rendered widget or null if it doesn't want to be rendered
     */
    public function renderProduction() {
        if (!$this->loadDataArray()) return;

        $ajaxContent = null;
        if(($widgetInstanceInfo = $this->setWidgetInstance('production')) && $this->CI->isAjaxRequest()) {
            $ajaxContent = $this->getAjaxContent($widgetInstanceInfo);
        }

        ob_start();
        $this->{$this->viewFunctionName}($this->data);
        $widgetContent = trim(ob_get_clean());

        if($ajaxContent) {
            return $widgetContent . $ajaxContent . "\n";
        }
        return $widgetContent . "\n";
    }

    /**
     * Gets and formats the json-encoded data needed for ajax-rendered widgets
     * @param array $widgetInstanceInfo As returned from self::setWidgetInstance
     * @return string A string featuring formatted JSON to be appended to the widget's viewContent
     */
    protected function getAjaxContent($widgetInstanceInfo) {
        $widgetInstanceCall = json_encode(array(
            'data'           => $widgetInstanceInfo['data'],
            'contextData'    => $widgetInstanceInfo['contextData'],
            'contextToken'   => $widgetInstanceInfo['contextToken'],
            'timestamp'      => $widgetInstanceInfo['timestamp'],
            'instanceID'     => $widgetInstanceInfo['instanceID'],
            'javaScriptPath' => $widgetInstanceInfo['path'],
            'className'      => $widgetInstanceInfo['className'],
            'suffix'         => $widgetInstanceInfo['widgetID'],
            'formToken'      => $widgetInstanceInfo['formToken'],
            'showWarnings'   => $widgetInstanceInfo['showWarnings']
        ));
        return "<script type='text/json'>{$widgetInstanceCall}</script>";
    }

    /**
     * Sets the widget instance to the clientLoader, as well as return data about the
     *      set widget
     * @param string $mode Value 'production' or 'development'
     * @return array|null An array featuring values for redering widgets via ajax, or null when
     *      self::getClientInstanceInfo does not return a value
     */
    protected function setWidgetInstance($mode) {
        if($widgetInstanceInfo = $this->getClientInstanceInfo($mode)) {
            $this->CI->clientLoader->addWidgetJavaScriptInstance($widgetInstanceInfo);
            if($this->CI->isAjaxRequest()) {
                $widgetInstanceInfo['showWarnings'] = $this->CI->clientLoader->shouldThrowWidgetJavaScriptErrors();
            }
            return $widgetInstanceInfo;
        }
        return null;
    }

    /**
     * Sets attributes as either the passed in value
     *
     * @param array $attributes Array of passed in parameters
     */
    public function setAttributes(array $attributes)
    {
        if (isset($attributes['instance_id']) && $attributes['instance_id'])
        {
            $value = $attributes['instance_id'];
            if (!preg_match('@^[0-9a-zA-Z_]+$@', $value))
                return self::widgetError($this->path, \RightNow\Utils\Config::getMessage(VAL_INST_ID_ATTRIB_CONT_STRING_MSG));
            if (preg_match('@_\d+$@', $value))
                return self::widgetError($this->path, \RightNow\Utils\Config::getMessage(VAL_INST_ID_ATTRIB_END_UNDERSCORE_MSG));
            if (!$this->isUniqueInstanceID($value))
                return self::widgetError($this->path, sprintf(\RightNow\Utils\Config::getMessage(WIDGET_PG_WIDGET_INST_ID_PCT_S_INST_MSG), $value));
            $this->instanceID = $value;
            unset($attributes['instance_id']);
        }
        $attributes = array_change_key_case($attributes);

        foreach($this->attrs as $key => $attributeObject)
        {
            //Only validate default values on required attributes...
            $shouldValidateAttribute = $attributeObject->required;
            //Check if we need to overwrite the value since it's
            //being passed in at run time
            if(array_key_exists($key, $attributes))
            {
                $attributeObject->value = $attributes[$key];
                unset($attributes[$key]);
                //...or ones where the value is being set at runtime
                $shouldValidateAttribute = true;
            }
            if($shouldValidateAttribute)
            {
                $isValid = $this->validateAttributeValue($key, $attributeObject->value);
                if($isValid !== true)
                    return $isValid;
            }
        }
        if($this->hasCalledAjaxHandlerSetter)
            $this->deferredAjaxHandlerValidation();
        //At this point, the only attributes left will be ones which aren't defined
        //within this widget. In that case, we just set the values directly and don't
        //need to validate them at all
        foreach($attributes as $key => $value)
        {
            if(!isset($this->attrs[$key])){
                $this->attrs[$key] = (object)array();
            }
            $this->attrs[$key]->value = $value;
        }
        return true;
    }

    public function setViewFunctionName($value)
    {
        $this->viewFunctionName = $value;
    }

    /**
     * Return a formatted error message.
     *
     * @param PathInfo|string $widgetPath The string path to the widget or the PathInfo object where the error occurred
     * @param string $errorMessage The error message to display to the user
     * @param bool $severe If true, the message is displayed
     *   as an error in the development header; if false, the message is
     *   displayed as a warning. Defaults to true.
     * @return string The formatted error message
     */
    public static function widgetError($widgetPath, $errorMessage, $severe = true)
    {
        if(IS_OPTIMIZED)
            return '';

        if ($severe) {
            $message = sprintf(\RightNow\Utils\Config::getMessage(WIDGET_ERROR_PCT_S_PCT_S_LBL), $widgetPath, $errorMessage);
            Framework::addDevelopmentHeaderError($message);
        }
        else {
            $message = sprintf(\RightNow\Utils\Config::getMessage(WIDGET_WARNING_PCT_S_PCT_S_LBL), $widgetPath, $errorMessage);
            Framework::addDevelopmentHeaderWarning($message);
        }
        return "<div><b>$message</b></div>";
    }

    /**
     * Renders the supplied widget view partial according to the current
     * environment.
     * @param string $viewContent View content (if non-optimized) or name of method to call (optimized)
     * @param array  $dataForView Data that the view expects to exist in local scope
     * @return string Rendered content
     */
    protected function renderPartial($viewContent, array $dataForView)
    {
        return (IS_OPTIMIZED)
            ? $this->renderOptimizedPartial($viewContent, $dataForView)
            : $this->renderNonOptimizedPartial($viewContent, $dataForView);
    }

    /**
     * Returns the content from the called method.
     * @param string $methodContainingView Name of a method on the widget instance
     *                                      that will render the view content
     * @param array  $dataForView          Data that the view expects to exist in
     *                                      local scope
     * @return string                       Rendered content
     */
    private function renderOptimizedPartial($methodContainingView, array $dataForView)
    {
        ob_start();
        $this->{$methodContainingView}($dataForView);
        return ob_get_clean();
    }

    /**
     * Extracts data in the array and evals php content.
     * @param string $dontCollideVarNameViewContent View content
     * @param array  $dataForView                   Variables that the view content expects to exist
     * @return string|boolean                        Evaled content or false if there's an error
     */
    private function renderNonOptimizedPartial($dontCollideVarNameViewContent, array $dataForView)
    {
        extract($dataForView);
        ob_start();
        eval("?>$dontCollideVarNameViewContent<?");
        return ob_get_clean();
    }

    /**
    * Sets attributes on the widget.
    * @param array|null $attributes Array of attribute objects
    */
    private function addManifestAttributes($attributes)
    {
        if (is_array($attributes))
        {
            foreach ($attributes as $name => $attribute)
            {
                if($attribute instanceof \RightNow\Libraries\Widget\Attribute)
                {
                    $this->attrs[$name] = clone $attribute;
                }
            }
        }
    }

    /**
     * Validates a widget attribute
     *
     * @param string $key The attribute key
     * @param string &$value The attribute value
     * @return string|bool True if validation passed, error string otherwise
     */
    private function validateAttributeValue($key, &$value)
    {
        if (!isset($this->attrs[$key]))
        {
            //We removed the tabindex attribute, but for backward compatability, we still need to
            //convert it into an integer
            if($key === 'tabindex')
                $value = (int)$value;
            return true;
        }
        $wrongParmType = false;

        switch(strtoupper($this->attrs[$key]->type))
        {
            case 'STRING':
                if(!is_string($value) && !is_numeric($value))
                    $wrongParmType = true;
                break;
            case 'INT':
            case 'INTEGER':
                if(!is_numeric($value))
                    $wrongParmType = true;
                else
                    $value = (int)$value;
                break;
            case 'BOOL':
            case 'BOOLEAN':
                if(strcasecmp($value, 'true') === 0 || $value === true)
                    $value = true;
                else if(strcasecmp($value, 'false') === 0 || $value === false)
                    $value = false;
                else
                    $wrongParmType = true;
                break;
            case 'OPTION':
                if(!Framework::inArrayCaseInsensitive($this->attrs[$key]->options, $value))
                    return self::widgetError($this->path, sprintf(\RightNow\Utils\Config::getMessage(WRONG_VAL_TYPE_PCT_S_ATTRIB_VAL_MSG), $key));
                break;
            case 'MULTIOPTION':
                //When required attributes are being validated, they already come in as arrays
                if(!is_array($value)){
                    $value = explode(",", $value);
                }
                foreach($value as &$option){
                    $option = trim($option);
                    if(!Framework::inArrayCaseInsensitive($this->attrs[$key]->options, $option)){
                        return self::widgetError($this->path, sprintf(\RightNow\Utils\Config::getMessage(INVALID_ATTRIB_AVAIL_TO_TH_ATTRIBUTE_MSG), $key, $option));
                    }
                }
        }
        if($wrongParmType)
            return self::widgetError($this->path, sprintf(\RightNow\Utils\Config::getMessage(WRONG_PARAM_TYPE_PCT_S_ATTRIB_MSG), $key, strtoupper($this->attrs[$key]->type), strtoupper(gettype($value)), $value));
        return $this->doesAttributeValueMeetDataRequirements($key, $value);
    }

    /**
     * Checks if the provided attribute value conforms to the min, max, and requiredness settings.
     * @param  string $key   Name of attribute we're validating
     * @param  mixed  $value Value being set
     * @return boolean|string True if value is valid or a string error message on failure
     */
    private function doesAttributeValueMeetDataRequirements($key, $value){
        if((isset($this->attrs[$key]->min)) && (intval($value) < $this->attrs[$key]->min)){
            return self::widgetError($this->path, sprintf(\RightNow\Utils\Config::getMessage(VAL_PCT_S_ATTRIB_MINIMUM_VAL_ACCD_MSG), $key, $this->attrs[$key]->min, $value));
        }
        if(($this->attrs[$key]->max) && (intval($value) > $this->attrs[$key]->max)){
            return self::widgetError($this->path, sprintf(\RightNow\Utils\Config::getMessage(VAL_PCT_S_ATTRIB_MAX_VAL_ACCD_PCT_S_MSG), $key, $this->attrs[$key]->max, $value));
        }
        if($this->attrs[$key]->required && ($value === '' || $value === null || (is_array($value) && count($value) === 0))){
            return self::widgetError($this->path, sprintf(\RightNow\Utils\Config::getMessage(PCT_S_ATTRIB_REQD_HAVENT_VALUE_MSG), $key, $value));
        }
        return true;
    }

    /**
     * Sets the instanceID if not already set and initializes the attrs, info,
     * js, and name items on the widget's data member.
     * Calls the widget's #getData method.
     * @return bool If false, the widget intentionally doesn't want to render; otherwise, true
     */
    private function loadDataArray()
    {
        if (!$this->instanceID) {
            $widgetInfo = $this->getInfo();
            $this->instanceID = "{$widgetInfo['widget_name']}_{$widgetInfo['w_id']}";
        }

        $this->initDataArray();

        //If false is returned, bail out and don't display the widget
        if($this->getData() === false)
            return false;

        //Add in any special constraints
        if($this->inputName && $this->data['constraints'] && is_array($this->data['constraints'])) {
            self::$serverConstraints[$this->inputName] = $this->data['constraints'];
        }
        return true;
    }

    /**
     * Converts the widget data into a formatted array which is passed to
     * the widget logic file.
     *
     * @param array $data The widget data
     * @return array
     */
    private function formatWidgetData(array $data)
    {
        $formattedArray = array(
            'i' => array(
                'c' => isset($data['info']['controller_name']) ? $data['info']['controller_name'] : null,
                'n' => isset($data['info']['widget_name']) ? $data['info']['widget_name'] : null,
                'w' => isset($data['info']['w_id']) ? $data['info']['w_id'] : null,
            ),
            'a' => isset($data['attrs']) ? $data['attrs'] : null,
            'j' => isset($data['js']) && is_array($data['js']) ? $data['js'] : array(),
        );
        if(isset($data['info']['type']) && $data['info']['type'])
            $formattedArray['i']['t'] = $data['info']['type'];
        return $formattedArray;
    }

    /**
     * Gathers and prepares information which is passed to widgets, includes JavaScript files,
     *      and handles including parent and template files.
     * @param string $mode Value 'production' or 'development' (whether the JS files need to be
     *      included on the page)
     * @return void or array with the following keys:
     *  data: properly formated widget data
     *  contextData: encoded contextual widget data
     *  contextToken: hash used to verify contextData
     *  timestamp: timestamp used to verify contextData
     *  instanceID: string The ID of the widget instance
     *  path: string The path to the widget logic file
     *  className: string The name of the widgets JavaScript class
     *  widgetID: string The unique ID of the widget instance
     *  static: array Key-value properties that are static for the widget;
     *                  any values of complex-type data must be JSON-encoded
     */
    private function getClientInstanceInfo($mode) {
        $includeFiles = ($mode === 'development');
        $widgetInfo = $this->getInfo();
        $jsPath = isset($widgetInfo['js_path']) && $widgetInfo['js_path'] ? $widgetInfo['js_path'] : '';
        $requiredJS = isset( $widgetInfo['extends_js']) && $widgetInfo['extends_js'] ? $widgetInfo['extends_js'] : '';
        $parent = $className = null;
        $yuiModules = array();

        if ($requiredJS) {
            if ($includeFiles) {
                foreach ($requiredJS as $path) {
                    if($widget = Registry::getWidgetPathInfo($path)) {
                        $this->CI->clientLoader->parseSupportingWidgetContent(array(
                            array('meta' => Widgets::getWidgetInfo($widget))
                        ));
                    }
                }
            }
            if (!$jsPath) {
                // If widget doesn't have JS of its own, then instantiate its immediate parent.
                $parent = end($requiredJS);
                $className = Widgets::getWidgetJSClassName($parent);
            }
        }

        if($jsPath && !$parent) {
            $className = $widgetInfo['js_name'];
        }

        if ($className) {
            $widgetInfo = isset($widgetInfo['meta']) && $widgetInfo['meta'] ?: $widgetInfo;
            return array(
                'data' => $this->formatWidgetData($this->data),
                'contextData' => $widgetInfo['contextData'],
                'contextToken' => $widgetInfo['contextToken'],
                'timestamp' => $widgetInfo['timestamp'],
                'formToken' => $widgetInfo['formToken'],
                'instanceID' => $this->instanceID,
                'path' => $jsPath,
                'className' => $className,
                'widgetID' => $widgetInfo['w_id'],
                'static' => Widgets::getWidgetStatics($widgetInfo, $this->CI->clientLoader),
            );
        }
    }

    /**
    * Validates that all keys in ajaxHandlers correspond to widget's ajax attributes;
    * tacks the widget path and method name defined in the ajaxHandler onto the corresponding attribute value
    * @pre an ajax-type attribute w/ key of 'banana' has value of /ci/ajax/widget &
    *   'banana' ajaxHandler has value of 'bananaHandler'
    * @post the attribute w/ key of 'banana' has value of /ci/ajax/widget/path/to/widget/bananaHandler &
    *   'banana' ajaxHandler is untouched.
    */
    private function deferredAjaxHandlerValidation()
    {
        foreach($this->ajaxHandlers as $name => $value)
        {
            if(array_key_exists($name, $this->attrs) && strtolower($this->attrs[$name]->type) === 'ajax')
            {
                //tack the widget's path and handler method name onto the ajax endpoint
                $method = (is_string($value)) ? $value : $value['method'];
                $this->attrs[$name]->value .= ((Text::endsWith($method, '/')) ? '' : '/') . $this->getPath() . '/' . $method;
            }
            else
            {
                return self::widgetError($this->path, sprintf(\RightNow\Utils\Config::getMessage(PCT_S_ISNT_DEFINED_AJAX_TYPE_MSG), $name));
            }
        }
    }

    /**
     * Ensures that the instanceID attribute is unique on all instances of
     * widgets being rendered.
     *
     * @param string $value The value to the attribute
     * @return bool Whether the value to the attribute was unique
     */
    private function isUniqueInstanceID($value)
    {
        $suffixes = $this->CI->config->item('widgetInstanceIDs');
        if (in_array($value, $suffixes))
            return false;
        array_push($suffixes, $value);
        $this->CI->config->set_item('widgetInstanceIDs', $suffixes);
        return true;
    }

    /**
     * Gets incremental widget serial number from config, or uses time() for
     * ajax.
     *
     * @return int The widget id
     */
    private function getWidgetSerialNumber()
    {
        if($this->CI->isAjaxRequest()) {
            $widgetSerialNumber = (int) str_replace('.', '', microtime());
        }
        else {
            $widgetSerialNumber = $this->CI->config->item('w_id');
            $this->CI->config->set_item('w_id', $widgetSerialNumber + 1);
        }
        return $widgetSerialNumber;
    }

    /**
     * Adds the custom attributes needed for widget inspection
     * @param string $widgetContent Widget HTML content
     * @return string Modified Widget HTML content
     */
    private function addWidgetInspectorCustomAttributes($widgetContent){
        $encodedJson = htmlspecialchars(json_encode($this->data['attrs']));
        if(empty($this->data['info']['js_name'])) {
            $widgetContent = preg_replace('# id=#', ' data-attrs="' . $encodedJson . '" id=', $widgetContent, 1);
        }
        $widgetName = isset($this->data['name']) ? substr($this->data['name'], 0, strpos($this->data['name'], '_')) : false;
        $customAttributes = $this->getInfo('widgetPosition') ? " data-widget-position = \"{$this->getInfo('widgetPosition')}\"" : "";
        $customAttributes .= isset($this->data['attrs']["sub_id"]) && $this->data['attrs']["sub_id"] ? " data-subid=\"". $this->data['attrs']["sub_id"] ."\"" : "";
        $customAttributes .= " data-widget-path=\"{$this->path}\" data-widget-identifier=\"{$widgetName}\" id=";
        $widgetContent = $customAttributes ? preg_replace('# id=#', $customAttributes, $widgetContent, 1) : $widgetContent;
        return $widgetContent;
    }
}
