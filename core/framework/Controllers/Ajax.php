<?php

namespace RightNow\Controllers;

use RightNow\Api,
    RightNow\Utils\Framework,
    RightNow\Libraries\ResponseObject,
    RightNow\Libraries\ResponseError,
    RightNow\Utils\Text,
    RightNow\Utils\Widgets,
    RightNow\Utils\Config,
    RightNow\Libraries\AbuseDetection,
    RightNow\Internal\Libraries\Widget\Registry;

/**
 * Ajax endpoint specifically used to deal with widgets which contain their own Ajax handler methods.
 */
final class Ajax extends Base{
    public $exceptions;
    public $clientLoader;
    private $inspectedLoginExemption = false;
    private $checkForAbuse = ["standard/feedback/SiteFeedback" => "submitSiteFeedback"];

    function __construct() {
        parent::__construct();
        $this->clientLoader = new \RightNow\Libraries\ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());
    }

    /**
     * Renders a response error indicating an error and exits
     * @param string $errorMessage Error to indicate in response object
     * @param string $errorCode Error code to indicate in response object
     * @param boolean $indicateIsResponseObject Add 'isResponseObject' attribute to the returned JSON
     */
    public function renderErrorResponseObject($errorMessage = null, $errorCode = null, $indicateIsResponseObject = true) {
        $responseObject = new ResponseObject('is_string');
        if(is_null($errorMessage)) {
            $errorMessage = \RightNow\Utils\Config::getMessage(SORRY_BUT_ACTION_CANNOT_PCT_R_TRY_AGAIN_MSG);
        }
        $responseObject->error = array('externalMessage' => $errorMessage, 'displayToUser' => true);
        if(!is_null($errorCode)) {
            $responseObject->error->errorCode = $errorCode;
        }
        header('Content-Type: application/json');
        echo $responseObject->toJson(array(), $indicateIsResponseObject);
        exit();
    }

    /**
     * Generic handler for all widgets that want to define their own ajax
     * handling methods. Variables are widget path, plus handler method (ea. segment is a parameter)
     */
    public function widget(){
        list($method, $widgetPath) = $this->_getWidgetPath(func_get_args());
        if (isset($this->checkForAbuse[$widgetPath]) && $this->checkForAbuse[$widgetPath] == $method) {
            AbuseDetection::check();
        }

        // expecting something like standard/feedback/SiteFeedback/handlerMethod
        // TODO use a separator (\?\) to delimit widget path & GET parameters
        if(!Text::beginsWith($widgetPath, 'standard/') && !Text::beginsWith($widgetPath, 'custom/')) {
            $this->renderErrorResponseObject();
        }
        $widgetID = $this->_getWidgetInstanceID($widgetPath);

        if($widgetPath !== 'standard/login/LogoutLink' && !$this->isAjaxRequest()) {
            $this->renderErrorResponseObject();
        }
        // get contextData for widget
        $contextData = $this->_getContextData();
        if($this->_loginRequired($method, $contextData) === true) {
            $this->renderErrorResponseObject(Config::getMessage(USER_IS_NOT_LOGGED_IN_LBL), \RightNow\Models\CommunityObjectBase::USER_NOT_LOGGED_IN_ERROR_CODE, false);
        }
        if($this->_checkFormToken($method, $contextData, $widgetPath, $widgetID) === false) {
            $this->renderErrorResponseObject();
        }
        $this->_cleanUpPostParams();

        $widget = $this->_getWidget($widgetPath, $contextData);

        //remove f_tok coming in POST so that downstream addtional checks are avoided
        unset($_POST['f_tok']);

        $this->_callWidgetMethod(array(
            'widget' => array(
                'class' => $widget,
                'id'    => $widgetID,
                'path'  => $widgetPath,
                'info'  => $contextData,
            ),
            'method' => $method,
            'params' => $_POST,
        ));
    }

    /**
     * Check if a user needs to login based on 'login_required' parameter in the context data.
     * @param type $method Name of the ajax handler method
     * @param type $contextData An array of context data for the widget
     * @return boolean Returns true if user needs to login otherwise false.
     */
    protected function _loginRequired($method, $contextData) {
        return array_key_exists('login_required', $contextData) && array_key_exists($method, $contextData['login_required']) && $contextData['login_required'][$method] === true && !Framework::isLoggedIn();
    }

    /**
     * Looks for a form token in the post parameters and verifies its validity.
     * @param string $method Name of the ajax handler method
     * @param string $contextData An array of context data for the widget
     * @param string $widgetPath Path of the widget
     * @param number $widgetID ID of the widget
     * @return boolean returns false if token is invalid else true.  
     */
    protected function _checkFormToken($method, $contextData, $widgetPath, $widgetID = 0) {
        if($this->input->post('f_tok')) {
            $formToken = $this->input->post('f_tok');
            $widgetID = 0;
        }
        else {
            if (Text::beginsWith($widgetPath, 'standard/') && Framework::isFormTokenRequired()) {
                return false;
            }
            $formToken = $this->input->post('rn_formToken');
        }
        if(!$formToken && ($_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) && array_key_exists('HTTP_X_CSRF_TOKEN', $_SERVER)) {
            if(Framework::isValidSecurityToken($_SERVER['HTTP_X_CSRF_TOKEN'], 0)) {
                return true;
            }
        }
        if(array_key_exists('token_check', $contextData) && array_key_exists($method, $contextData['token_check']) && $contextData['token_check'][$method] === false) {
            return true;
        }
        if(!Framework::isValidSecurityToken($formToken, $widgetID) || !$formToken) {
            return false;
        }
        return true;
    }

    /**
     * Gets (and validates) the widget's context data from the request
     * @return array An array representing the widget's context data
     * @internal
     */
    protected function _getContextData() {
        $contextData = array();
        $contextDataParam = $this->input->post('rn_contextData');
        $contextTokenParam = $this->input->post('rn_contextToken');
        $timestampParam = $this->input->post('rn_timestamp');
        if($contextDataParam && $contextTokenParam && $timestampParam) {
            $timestamp = strval($timestampParam);
            // timestamp cannot be older than 24 hours
            if($timestamp > strtotime("-1 day") && Api::ver_ske_decrypt($contextTokenParam) === sha1($contextDataParam . $timestamp)) {
                $contextData = json_decode(base64_decode($contextDataParam), true);
            }
            else {
                $this->renderErrorResponseObject();
            }
        }
        else {
            $this->renderErrorResponseObject();
        }
        return $contextData;
    }

    /**
     * Removes extra post params from $_POST
     * @internal
     */
    protected function _cleanUpPostParams() {
        foreach(array('rn_contextData', 'rn_contextToken', 'rn_timestamp', 'rn_formToken') as $postParam)
            if(array_key_exists($postParam, $_POST)) unset($_POST[$postParam]);
    }

    /**
     * Ensures user is allowed to view this data.
     * @internal
     */
    protected function _isContactAllowed() {
        if ($this->inspectedLoginExemption === false) {
            // Initially called by parent before #widget;
            // always return true since it's not yet known
            // whether the widget handler is exempt or not...
            return true;
        }
        // Called by parent after #_callWidgetMethod
        // has properly determined exemption status and
        // manually tells the parent to re-check
        return parent::_isContactAllowed();
    }

    /**
     * Extracts the widget path from the arguments. Ignores the session
     * parameter if it's present.
     * @param array $args Each segment in the request URI after '/ci/ajax/'
     * @return array Array with the following items
     *  - 0 : String Name of the widget's method
     *  - 1 : String Relative widget path
     * @internal
     */
    private function _getWidgetPath(array $args) {
        if (($sessionIndex = array_search('session', $args)) !== false) {
            unset($args[$sessionIndex], $args[$sessionIndex + 1]);  // Session key & value
        }

        return array(
            array_pop($args),
            implode('/', $args),
        );
    }

    /**
     * Returns the fully qualified widget controller class name, provided the widget path
     * @param string $widgetPath Path to the widget
     * @param array|null $contextData Data about the widget including its extension details
     * @return string Fully qualified widget controller class
     */
    private function _getWidget($widgetPath, $contextData) {
        try {
            $className = Widgets::getWidgetController($widgetPath, isset($contextData['extends']) && $contextData['extends'] ? $contextData['extends'] : array());
        }
        catch (\Exception $e) {
            //Error is handled below
        }

        if (!$className || !class_exists($className))
            $this->renderErrorResponseObject();

        return $className;
    }

    /**
     * Executes the specified `$method` on the widget.
     * It's the widget method's responsibility to echo out a response.
     * @param array $details The necessary data to execute the method on
     *                       the widget:
     *                       - widget:
     *                           - info: [widget info]
     *                           - class: widget classname
     *                           - id: widget's id
     *                           - path: relative path
     *                       - method: string method name
     *                       - params: array params to pass
     */
    private function _callWidgetMethod(array $details) {
        $widgetDetails = $details['widget'];
        $methodName = $details['method'];

        if (!$this->_widgetMethodExists($widgetDetails['class'], $methodName))
            $this->renderErrorResponseObject();

        $this->_insertClickstreamAction($widgetDetails['info'], $methodName);
        $this->_enforceLoginRequirements($widgetDetails['info'], $methodName);

        if (!$this->_callStaticMethod($widgetDetails['class'], $methodName, $details['params'])) {
            $widgetPathInfo = Registry::getWidgetPathInfo($widgetDetails['path']);
            $widgetMeta = (IS_OPTIMIZED) ? $widgetPathInfo->meta['meta'] :
                Widgets::convertAttributeTagsToValues(Widgets::getWidgetInfo($widgetPathInfo), array('validate' => true, 'eval' => true, 'omit' => array('name', 'description')));
            $widgetAttributes = $widgetMeta['attributes'];

            $widget = $widgetDetails['class'];
            $widgetInstance = new $widget($widgetAttributes);
            $this->_callInstanceMethod($widgetInstance, $methodName, $widgetDetails, $details['params']);
        }
    }

    /**
     * Retrieves the widget instance ID from POST data.
     * @param string $widgetPath Path to widget, only used for error reporting
     * @return int Widget instance ID
     */
    private function _getWidgetInstanceID($widgetPath) {
        $widgetID = $_POST['w_id'];
        if (!$widgetID && $widgetID !== '0') {
            exit("There's no widget instance id (w_id) specified for $widgetPath!");
        }

        // prevent XSS in widget IDs (some widgets, like search results, use nonnumeric IDs)
        // allow letters, numerals, and underscores with \w. Either 0 or FALSE counts as a fail
        if (!preg_match('/^\w+$/', $widgetID)) {
            $this->renderErrorResponseObject();
        }

        if (Text::stringContains($widgetID, '_')) {
            $widgetID = (int) Text::getSubstringAfter($widgetID, '_');
        }
        return $widgetID;
    }

    /**
     * If the specified method is static, executes it.
     * @param string $widgetClass     Widget class name
     * @param string $method     Method to execute
     * @param array $postParams POST parameters to send
     * @return boolean             True if executed, False if not
     */
    private function _callStaticMethod($widgetClass, $method, array $postParams) {
        $reflection = new \ReflectionMethod($widgetClass, $method);

        if ($reflection->isStatic()) {
            $widgetClass::$method($postParams);
            return true;
        }

        return false;
    }

    /**
     * Executes the specified method.
     * @param object $widget Widget instance
     * @param string $method        Method name
     * @param array $widgetDetails  Widget info
     * @param array $params         Post params
     */
    private function _callInstanceMethod($widget, $method, array $widgetDetails, array $params) {
        $name = basename($widgetDetails['path']);
        $id = $widgetDetails['id'];
        $widget->setInfo(array(
            'name' => $name,
            'w_id' => $id,
        ));
        $widget->instanceID = "{$name}_{$id}";

        if (isset($widgetDetails['info']['nonDefaultAttrValues']) && $widgetDetails['info']['nonDefaultAttrValues']) {
            $widget->setAttributes($widgetDetails['info']['nonDefaultAttrValues']);
        }

        $widget->setPath($widgetDetails['path']);
        $widget->setHelper();
        $widget->initDataArray();

        $widget->$method($params);
    }

    /**
     * Makes sure that the specified method actually exists on the widget.
     * @param string $widgetClass Widget's class
     * @param string $method     Method name
     * @return  boolean True if the method exists, False otherwise
     */
    private function _widgetMethodExists($widgetClass, $method) {
        $classMethods = get_class_methods($widgetClass);

        return $method && $classMethods && \RightNow\Utils\Framework::inArrayCaseInsensitive($classMethods, $method);
    }

    /**
     * Inserts a clickstream action for the widget
     * @param array  $contextData Widget info
     * @param string $methodName  Method name
     * @return string             The inserted action
     */
    private function _insertClickstreamAction($contextData, $methodName) {
        $clickstream = new \RightNow\Hooks\Clickstream();
        if (!$contextData || (!isset($contextData['clickstream']) || !$contextData['clickstream']) || (!isset($contextData['clickstream'][$methodName]) || !($action = $contextData['clickstream'][$methodName]))) {
            $action = $methodName;
        }

        $clickstream->trackSession('normal', $action);

        return $action;
    }

    /**
     * Special case to handle requests from guided assistance preview
     */
    public function _ensureContactIsAllowed() {
        // Adding support for multiple guides to be loaded via Agent Console and CP_CONTACT_LOGIN_REQUIRED config enabled
        if (Config::contactLoginRequiredEnabled() && (strpos($_SERVER['HTTP_RNT_REFERRER'], "guided_assistant") !== false || strpos($_SERVER['HTTP_REFERER'], "guided_assistant") !== false) && is_object($this->_getAgentAccount())){
            return true;
        }
        return parent::_ensureContactIsAllowed();
    }

    /**
     * Deals with login requirements and exemptions.
     * @param array  $contextData Widget info
     * @param string $methodName  Method name
     */
    private function _enforceLoginRequirements($contextData, $methodName) {
        if ($contextData && isset($contextData['exempt_from_login_requirement']) && $contextData['exempt_from_login_requirement'][$methodName]) {
            parent::_setMethodsExemptFromContactLoginRequired(array(
                $this->router->fetch_method(),
            ));
        }

        $this->inspectedLoginExemption = true;
        $this->_ensureContactIsAllowed();
    }
}
