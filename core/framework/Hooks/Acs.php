<?php
namespace RightNow\Hooks;

use RightNow\ActionCapture,
    RightNow\Utils\Framework,
    RightNow\Api;

/**
 * Initializes the Action Capture library to allow for recording of actions within Customer Portal.
 */
class Acs{
    private $CI;
    private $controllerName;
    private $controllerFunction;
    private $ignoredStandardControllers = array('ajaxRequestMin', 'browserSearch', 'dqa', 'designer', 'inlineImage', 'inlineImg', 'redirect', 'webdav');
    private $ignoredStandardRoutes = array('/pta/logout', '/ajaxRequest/getChatQueueAndInformation', '/cache/rss');
    private $allowedUrlParameters = array('a_id', 'c', 'comment', 'error_id', 'g_id', 'guideID', 'kw', 'lang', 'org', 'p', 'pac', 'page', 'people', 'posts', 'r_id', 'search', 'sort', 'st', 'qid');

    function __construct(){
        $this->CI = get_instance();
        $this->controllerName = $this->CI->router->fetch_class();
        $this->controllerFunction = $this->CI->router->fetch_method();
    }

    /**
     * Initializes the Action Capture Service library based on various environment settings.
     * @return boolean Whether ActionCapture was initialized or not.
     */
    function initialize(){
        $billingID = \RightNow\Utils\Config::getConfig(ACS_BILLING_ID, 'RNW');
        $captureHost = \RightNow\Utils\Config::getConfig(ACS_CAPTURE_HOST, 'RNW');
        if(!$billingID || !$captureHost){
            //Don't throw errors during any deploy operation, mainly to stop upgrade/service pack deploys from failing
            if($this->controllerName === 'deploy' && !CUSTOM_CONTROLLER_REQUEST){
                return;
            }
            exit(\RightNow\Utils\Config::getMessage(SITE_CONFIGURED_TRACKING_COMPLIANCE_MSG));
        }

        //There are some use cases where we don't want to do anything with ACS in order to not create billable actions. Instead of forcing the ACS core code to try and
        //figure out those endpoints/actions for us, we're just not going to initialize the library so recording isn't possible.
        if(!CUSTOM_CONTROLLER_REQUEST && (Framework::inArrayCaseInsensitive($this->ignoredStandardControllers, $this->controllerName) ||
            Framework::inArrayCaseInsensitive($this->ignoredStandardRoutes, "/{$this->controllerName}/{$this->controllerFunction}"))){
            return false;
        }

        $debugMode = !IS_PRODUCTION && !IS_ADMIN;
        if(!IS_HOSTED && !CUSTOM_CONTROLLER_REQUEST && $this->CI->router->fetch_directory() === 'UnitTest/'){
            //ACS has this annoying problem where it causes calls to dqa_insert which for some reason seem to eventually call
            //sql_commit. That screws up our unit tests, so force it into debug mode so it doesn't insert ACS actions
            $debugMode = true;
        }

        $configs = array(
                'debugMode' => $debugMode,
                'billingID' => $billingID,
                'instanceID' => \RightNow\Utils\Config::getConfig(DB_NAME, 'COMMON') . ':' . Api::intf_name(),
                'remoteIP' => $_SERVER['REMOTE_ADDR'],
                'referrer' => isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '',
                'userAgent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '' ,
                'url' => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . ORIGINAL_REQUEST_URI,
                'cleanUrl' => $this->getUrlWithWhitelistedParameters(),
                'productFamily' => 'rnw',
                'productVersion' => MOD_BUILD_VER . '-b' . MOD_BUILD_NUM . ((MOD_BUILD_SP) ? '-sp' . MOD_BUILD_SP : '')
            );

        //For non-hosted sites, record actions to the logs directory if the user has a tr.acs file there
        if(!IS_HOSTED && is_file(Api::cfg_path() . "/log/tr.acs")){
            $configs['logDirectory'] = Api::cfg_path() . "/log/acs/";
            $configs['debugMode'] = false;
        }

        //Set the session ID
        if($agentAccount = $this->CI->_getAgentAccount()){
            $configs['sessionID'] = md5($agentAccount->acct_id . '|' . $configs['billingID'] . '|' . $configs['remoteIP']);
        }
        else if(isset($this->CI->session) && $sessionID = $this->CI->session->getSessionData('sessionID')){
            $configs['sessionID'] = $sessionID;
        }
        //Else: Not an admin area but we don't have a session which should only happen for a widgetService controller request

        //Set the product name
        //Check to see whether or not it is an agent account and has a session ID then set to Admin or Agent based on that
        //The below check also now incorporates the validation of Agent account via Guided Assist via BUI/.NET to also consume new changes
        if($agentAccount){
            $configs['product'] = (!empty($sessionID) && (Framework::validateCustomerPortalAgent() === true)) ? 'Customer Portal Agent' : 'Customer Portal Admin';
        }
        else if($this->controllerName === 'documents' || $this->controllerName === 'friend'){
            $shortcut = \RightNow\Utils\Url::getParameter(MA_QS_ITEM_PARM) ?: $this->CI->input->post('p_shortcut');
            $app = $this->CI->model('Clickstream')->getMaAppType($shortcut, \RightNow\Utils\Url::getParameter(MA_QS_SURVEY_PARM), '');

            $configs['product'] = ($app === CS_APP_MA) ? 'RightNow Marketing' : 'RightNow Feedback';
        }
        else if($pageSetPath = $this->CI->getPageSetPath()){
            $configs['product'] = "Customer Portal - $pageSetPath";
            //Truncate and remove any invalid characters from the page set name
            $configs['product'] = preg_replace('@[^ -}]@', '', substr($configs['product'], 0, ActionCapture::PRODUCT_MAX_LENGTH));
        }
        else if(\RightNow\Utils\Text::beginsWith($_SERVER['REQUEST_URI'], '/ci/admin/')) {
            return false;
        }
        else if(Framework::validateCustomerPortalAgent() === true) {
            $configs['product'] = 'Customer Portal Agent';
        }
        else{
            $configs['product'] = 'Customer Portal';
        }

        ActionCapture::initialize($configs);

        $marketingProofPreview = !CUSTOM_CONTROLLER_REQUEST && $this->controllerName === 'documents' && !isset($configs['sessionID']);
        //Record a generic action for all endpoints except for syndicated widget rendering, marketing proof/preview requests and
        //normal page requests which will have the generic page/view action recorded at a later time
        if($this->controllerName !== 'widgetService' && (($this->controllerName !== 'page' && $this->controllerName !== 'facebook') && $this->controllerFunction !== 'render') && !$marketingProofPreview){
            ActionCapture::record(CUSTOM_CONTROLLER_REQUEST ? 'customController' : 'controller', 'request', substr($this->controllerName . '/' . $this->controllerFunction, 0, ActionCapture::OBJECT_MAX_LENGTH));
        }

        return true;
    }

    /**
     * Retrieves the current URL and removes any parameters that aren't whitelisted.
     * @return string The current, fully qualified page URL with only whitelisted parameters
     */
    private function getUrlWithWhitelistedParameters(){
        $nonParameterUrlComponent = '';
        if($this->controllerName === 'page' && $this->CI->router->foundControllerInCpCore){
            $nonParameterUrlComponent = '/app/' . $this->CI->page;
        }
        else{
            $nonParameterUrlComponent = (CUSTOM_CONTROLLER_REQUEST ? '/cc/' : '/ci/') . "{$this->controllerName}/";
            if($controllerDirectory = $this->CI->router->fetch_directory()){
                $nonParameterUrlComponent .= $controllerDirectory;
            }
            $nonParameterUrlComponent .= $this->controllerFunction;
        }

        //Since the page/controller component has already been url decoded, we need to iterate over each component and
        //re-encode the segments since they can have non-valid URL characters such as spaces.
        $nonParameterUrlComponent = implode('/', array_map('urlencode', explode('/', $nonParameterUrlComponent)));

        $currentPageParameters = $this->CI->uri->uri_to_assoc($this->CI->config->item('parm_segment'));
        $whiteListedParameters = array_intersect_key($currentPageParameters, array_flip($this->allowedUrlParameters));

        $cleanUrl = $nonParameterUrlComponent;
        foreach($whiteListedParameters as $parameterKey => $parameterValue){
            $cleanUrl .= "/$parameterKey/$parameterValue";
        }
        return substr((isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $cleanUrl, 0, ActionCapture::CLEAN_URL_MAX_LENGTH);
    }
}
