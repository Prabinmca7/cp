<?php

namespace RightNow\Hooks;
use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Api,
    RightNow\Internal\Sql\Clickstream as Sql,
    RightNow\Connect\v1_4 as Connect;

/**
 * This class captures clickstream entries for actions that occur within Customer Portal. It loads the session module and records user activities.
 */
class Clickstream
{
    /**
     * Constant for contact create actions
     * @internal
     */
    const DQA_ACCOUNT_CREATE_PARAM = "1";

    /**
     * Constant for contact update actions
     * @internal
     */
    const DQA_ACCOUNT_UPDATE_PARAM = "2";

    /**
     * Default URL parameter offset
     * @internal
     */
    const CONTROLLER_OFFSET = 3;

    private $CI;
    private $offset;
    private $useClickstream;
    private $controllerClassName;
    private $controllerFunctionName;
    private $ignoredControllers = array('answerPreview', 'browserSearch', 'dqa', 'inlineImage', 'inlineImg', 'redirect', 'webdav');
    private $ignoredStandardRoutes = array('/ajaxRequest/getChatQueueAndInformation', '/cache/rss');
    private $ignoredOptionsRequests = array('/oit/getConfigs', '/api/v1', '/oit/authenticateChat', '/oit/fileUpload');

    function __construct()
    {
        $this->CI = (func_num_args() === 1) ? func_get_arg(0) : get_instance();
        $this->controllerClassName = $this->CI->router->fetch_class();
        $this->controllerFunctionName = $this->CI->router->fetch_method();
        $this->offset = self::CONTROLLER_OFFSET + $this->CI->getPageSetOffset();

        $this->useClickstream = $this->determineSessionHandling($createSession, $useFakeSession);

        if ($createSession)
        {
            $this->CI->session = \RightNow\Libraries\Session::getInstance($useFakeSession);
        }

        $this->CI->model('Clickstream')->setClickstreamEnabled($this->useClickstream);
    }

    /**
     * Records actions and stores them within clickstreams. When any page is requested, the framework initializes its controller by invoking its constructor.
     * This method is invoked immediately after the controller's constructor runs.
     *
     * @param string $type Type of session tracking that is happening. Either 'page' or 'normal'.
     * @param string $onDemandAction Action to use at runtime. Happens mainly for widget ajax handlers
     * @return void
     */
    public function trackSession($type = 'normal', $onDemandAction = '')
    {
        if (!$this->shouldTrackSession($type, $onDemandAction))
        {
            return;
        }
        if ($spider = $this->CI->rnow->isSpider())
        {
            $this->CI->model('Clickstream')->insertSpider($this->CI->session->getSessionData('sessionID'),
                $this->CI->input->ip_address(), $_SERVER["HTTP_USER_AGENT"], $this->CI->uri->uri_string(), 1, $spider);
            return;
        }

        //In order to keep actions the same, we're going to map all /ajax/* requests to /ajaxRequest/*
        if(!CUSTOM_CONTROLLER_REQUEST && $this->controllerClassName === 'ajax'){
            $this->controllerClassName = 'ajaxRequest';
        }

        //The SA widget appends a SA token onto the URL when we successfully deflect an incident. If we
        //find that token in the URL, call into the API to register that deflection.
        if($smartAssistantResolutionToken = $this->getParameter('saResultToken')){
            try{
                $resolution = new \RightNow\Connect\Knowledge\v1\SmartAssistantResolution();
                $resolution->ID = 2; //KF_API_SA_RESOLUTION_TYPE_DEFLECTED
                \RightNow\Connect\Knowledge\v1\Knowledge::RegisterSmartAssistantResolution(\RightNow\Models\Base::getKnowledgeApiSessionToken(), $smartAssistantResolutionToken, $resolution);
            }
            catch(\Exception $e){
                //If for whatever reason this token has already been recorded, Connect might throw an exception, but we don't really want that to affect anything
            }
        }

        $trackingString = $this->getParameter("track");
        if($trackingString)
            $this->CI->model('Clickstream')->insertMailTransaction($trackingString, $this->CI->page, $this->CI->session->getSessionData('sessionID'), intval($this->getParameter('a_id')));

        //The SA widget appends a SA token onto the URL when we successfully deflect an incident. If we
        //find that token in the URL, call into the API to register that deflection.
        if($smartAssistantResolutionToken = $this->getParameter('saResultToken')){
            try{
                $resolution = new \RightNow\Connect\Knowledge\v1\SmartAssistantResolution();
                $resolution->ID = 2; //KF_API_SA_RESOLUTION_TYPE_DEFLECTED
                \RightNow\Connect\Knowledge\v1\Knowledge::RegisterSmartAssistantResolution(\RightNow\Models\Base::getKnowledgeApiSessionToken(), $smartAssistantResolutionToken, $resolution);
            }
            catch(\Exception $e){
                //If for whatever reason this token has already been recorded, Connect might throw an exception, but we don't really want that to affect anything
            }
        }

        $eventContext = (object) array("context1" => null, "context2" => null, "context3" => null);
        $contactID = $this->CI->session->getProfileData('contactID');

        // If $app is not set use new validatCustomerPortalAgent() to validate configs and set appropriate $app value
        if(!isset($app)) {
            $app = (Framework::validateCustomerPortalAgent() === true) ? CS_APP_AGENT_EU : CS_APP_EU;
        }

        $actionTag = $onDemandAction ?: $this->CI->_getClickstreamActionTag($this->controllerFunctionName);
        $action = $this->buildAction($actionTag);

        switch ($actionTag)
        {
            case "page_render":
                if ($this->pageRender($action, $eventContext) === false) return;
                break;
            case "report_data_service":
                $this->reportData($action, $eventContext);
                break;
            case "answer_feedback":
                if(!$this->answerObserved($eventContext)) return;
                break;
            case "incident_submit":
                $this->incidentSubmit($action, $eventContext);
                break;
            case "answer_notification":
                if ($this->answerNotification($action, $eventContext) === false) return;
                break;
            case "product_category_notification":
            case "product_category_notification_delete":
            case "product_category_notification_update":
                $this->productCategoryNotification($eventContext);
                break;
            case "notification_update":
                if($this->recordNotification($action, $eventContext) === false) return;
                break;
            case "notification_delete":
                if($this->recordNotification($action, $eventContext, true) === false) return;
                break;
            case "email_answer":
                if ($this->emailAnswer($eventContext) === false) return;
                break;
            case "attachment_view":
                $this->attachmentView($eventContext);
                break;
            case "opensearch_service":
                $this->openSearch($action, $eventContext);
                break;
            case 'emailCredentials':
                $action = $this->buildAction($this->getParameter('requestType', true) ?: 'emailPassword');
                break;
            case "document_view":
            case "document_detail":
            case "document_submit":
            case "document_verify_contact":
                $app = $this->document($action, $eventContext, $app, $contactID);
                break;
            case "question_delete":
            case "comment_create":
            case "comment_update":
            case "comment_reply":
            case "comment_mark_best":
            case "comment_delete":
            case "social_content_flag":
            case "social_content_rate":
                $action = $this->socialContentActions($action, $actionTag, $eventContext);
                break;
        }

        if (isset($this->CI->page) && $this->CI->page === "error404" && stripos($action, "error404") === false) {
            $action = "/".$this->CI->page;
            $eventContext->context1 = null;
            $eventContext->context2 = null;
            $eventContext->context3 = null;
        }

        $this->CI->model('Clickstream')->insertAction(
            $this->CI->session->getSessionData('sessionID'),
            $contactID,
            $app,
            (trim($action) === '') ? '/' . ClickstreamActionMapping::getAction('home') : $action,
            $eventContext->context1,
            $eventContext->context2,
            $eventContext->context3
        );
    }
    /**
    * Populates clickstream context for a normal page render action
    * @param string &$action Pass-by-reference action to record
    * @param object $eventContext Context object to record actions
    * @return bool False if the action shouldn't be recorded, True otherwise
    */
    private function pageRender(&$action, $eventContext) {
        if (isset($this->CI->meta["clickstream"]) && $clickStreamTag = $this->CI->meta["clickstream"])
        {
            $action = "/" . ClickstreamActionMapping::getAction($clickStreamTag);
            switch ($clickStreamTag)
            {
                case "answer_preview":
                case "answer_print":
                    if(!$this->answerObserved($eventContext)) return false;
                    break;
                case "answer_list":
                    $eventContext->context2 = $this->getParameter("p");
                    $eventContext->context3 = $this->getParameter("c");
                    break;
                case "incident_confirm":
                case "incident_view":
                case "incident_update":
                case "incident_print":
                    if($incidentID = $this->getParameter("i_id"))
                        $eventContext->context1 = $incidentID;
                    else if(($refno = $this->getParameter("refno")))
                        $eventContext->context1 = $this->CI->model('Incident')->getIncidentIDFromRefno($refno)->result;
                    break;
                case "question_view":
                    if($questionID = $this->getParameter("qid"))
                        $eventContext->context1 = $questionID;
                    break;
            }
        }
        else
        {
            $action = "/" . $this->CI->page;
            if ($this->CI->page === "error")
            {
                $eventContext->context1 = $this->getParameter("error_id");
            }
        }

        // The work-around to check for the POST'ed keyword parameter is for our
        // basic (non-JavaScript) page set.
        // If the keyword has been POST'ed assume that the user actively made a search
        // and record the context appropriately.
        // Note that we are unable to record these activities when the user is selecting
        // a product or category. Only an interaction with the BasicKeywordSearch
        // widget will cause this context to get recorded in the basic page set.
        if($this->getParameter("search") === "1" || $this->getParameter("kw", true) !== false)
        {
            // did you mean
            if ($this->getParameter("dym") === "1")
                $action = "$action/DYM";
            // suggested
            else if ($this->getParameter("suggested") === "1")
                $action = "$action/Suggested";

            $action .= "/" . ClickstreamActionMapping::getAction("search");
            $kw = $this->getParameter("kw");
            if ($kw === null && $this->getParameter("kw", true) !== false)
                $kw = $this->getParameter("kw", true);
            $eventContext->context1 = $kw;
            $eventContext->context2 = $this->getParameter("p");
            $eventContext->context3 = $this->getParameter("c");
        }

        return true;
    }


    /**
     * Returns the specified URL parameter value, or,
     * if desired, looks in the POST data for the value.
     * @param string $name Key of the parameter
     * @param string $fromPost Whether to retrieve the value from POST data
     * @return bool|null|string String value if found; if not found, false
     * is returned when looking at POST params, null for GET params
     */
    private function getParameter($name, $fromPost = false) {
        if ($fromPost) {
            return $this->CI->input->post($name);
        }

        return Url::getParameter($name, $this->CI);
    }

    /**
    * Populates clickstream context for a report search
    * @param string &$action Pass-by-reference action to record
    * @param object $eventContext Context object to record actions
    * @return void
    */
    private function reportData(&$action, $eventContext) {
        $filters = json_decode($this->getParameter('filters', true), true);
        if (isset($filters['search']) && $filters['search'] == 1)
        {
            $keywords = $filters['keyword'];

            if($keywords['filters'] && $keyword = $keywords['filters']['data']){
                $eventContext->context1 = is_array($keyword) ? implode("/", $keyword) : $keyword;
            }

            if($filters['p'] &&
                $filters['p']['filters'] &&
                ($prods = $filters['p']['filters']['data']) &&
                (count($prods) > 0) &&
                $prods[0] != null)
            {
                $eventContext->context2 = is_array($prods[0]) ? implode(",", $prods[0]) : $prods[0];
            }

            if($filters['c'] &&
                $filters['c']['filters'] &&
                ($cats = $filters['c']['filters']['data']) &&
                (count($cats) > 0) &&
                $cats[0] != null)
            {
                $eventContext->context3 = is_array($cats[0]) ? implode(",", $cats[0]) : $cats[0];
            }

            $action .= "/" . ClickstreamActionMapping::getAction("search");
        }
        else
        {
            // could be pagination
            $action .= "/" . ClickstreamActionMapping::getAction("paging");
            $eventContext->context1 = (string) $filters['page'];
        }
    }

    /**
     * Populates clickstream context for an incident submission.
     * @param string &$action Pass-by-reference action to record
     * @param object $eventContext Context object to record actions
     * @return void
     */
    private function incidentSubmit(&$action, $eventContext) {
        $formData = $this->getParameter('form', true) ?: $this->getParameter('formData', true);
        if(!is_array($formData)){
            $formData = json_decode($formData, true);
        }
        $profile = $this->CI->session->getProfile();
        if($listOfUpdateRecordIDs = json_decode($this->getParameter('updateIDs', true), true)){
            $incidentID = $listOfUpdateRecordIDs['i_id'];
            $assetID = $listOfUpdateRecordIDs['asset_id'];
            $questionID = $listOfUpdateRecordIDs['qid'];
        }
        $incidentID = $incidentID ?: $this->getParameter('i_id');
        $assetID = $assetID ?: $this->getParameter('asset_id');
        $questionID = $questionID ?: $this->getParameter('qid');

        $contactFieldsPresent = $incidentFieldsPresent = $assetFieldsPresent = false;
        $emailFieldValue = null;
        if(is_array($formData))
        {
            foreach($formData as $key => $value)
            {
                if(is_array($value)){
                    $key = $value['name'];
                    $value = $value['value'];
                }
                if(Text::beginsWith($key, 'Contact.'))
                    $contactFieldsPresent = true;
                else if(Text::beginsWith($key, 'Incident.'))
                    $incidentFieldsPresent = true;
                else if(Text::beginsWith($key, 'Asset.'))
                    $assetFieldsPresent = true;
                else if(Text::beginsWith(strtolower($key), 'communityquestion.'))
                    $questionFieldsPresent = true;

                if($key === "Contact.Emails.PRIMARY.Address")
                    $emailFieldValue = $value;

                if ($contactFieldsPresent && $incidentFieldsPresent && $assetFieldsPresent && $questionFieldsPresent && $emailFieldValue !== null) {
                    break;
                }
            }
        }

        if($incidentFieldsPresent)
        {
            if($incidentID){
                $action = $this->buildAction("incident_update");
                $eventContext->context1 = $incidentID;
            }
            else{
                //When a user first submits an incident and the SA widget is on the page, it sends an 'smrt_asst' flag that is initially
                //set to 'true' to denote that we should run SA. The subsequent submission has the 'smrt_asst' flag set to 'false' which is
                //when we record the 'incident_create_smart' action. If the SA widget isn't on the page at all, no 'smrt_asst' flag is sent at all.
                if($this->getParameter('smrt_asst', true) === 'false')
                    $action = $this->buildAction("incident_create_smart");
            }
            if($contactFieldsPresent){
                if($profile === null){
                    if($emailFieldValue !== null){
                        if(!$this->CI->model('Contact')->lookupContactByEmail($emailFieldValue)->result){
                            $eventContext->context2 = self::DQA_ACCOUNT_CREATE_PARAM;
                        }
                    }
                    else{
                        $eventContext->context2 = self::DQA_ACCOUNT_CREATE_PARAM;
                    }
                }
                else if(!\RightNow\Utils\Framework::isPta()){
                    $eventContext->context2 = self::DQA_ACCOUNT_UPDATE_PARAM;
                }
            }
        }
        else if($contactFieldsPresent){
            $action = $this->buildAction(($profile !== null) ? "account_update" : "account_create");
        }
        else if($assetFieldsPresent){
            $action = $this->buildAction($assetID ? "asset_update" : "asset_create");
        }
        else if($questionFieldsPresent) {
            $action = $this->buildAction($questionID ? "question_update" : "question_create");
            if($questionID) {
                $eventContext->context1 = $questionID;
            }
        }
    }

    /**
     * Populates clickstream context for an answer notification update/delete.
     * @param string &$action Pass-by-reference action to record
     * @param object $eventContext Context object to record actions
     * @return void|bool
     */
    private function answerNotification(&$action, $eventContext) {
        if(!$this->answerObserved($eventContext)) return false;

        $actions = array(
            '0'  => 'answer_notification_update',
            '-4' => 'answer_notification_delete',
        );

        $status = $this->getParameter('status', true);
        $validAction = $status !== false ? $actions[$status] : false;

        if ($validAction) {
            $action = $this->buildAction($validAction);
        }
    }

    /**
     * Populates clickstream context for a product/cagetory notification create/update/delete
     * @param object $eventContext Context object to record actions
     * @return void
     */
    private function productCategoryNotification($eventContext) {
        $context = $this->getParameter('chain', true);
        $type = $this->getParameter('filter_type', true);

        if(Text::stringContainsCaseInsensitive($type, "prod") || $type == HM_PRODUCTS)
            $eventContext->context2 = $context;
        else if(Text::stringContainsCaseInsensitive($type, "cat") || $type == HM_CATEGORIES)
            $eventContext->context3 = $context;
    }

    /**
     * Router function for prod/cat/ans notifications now that they all are routed through
     * the same two clickstream actions
     * @param string &$action Action to be recorded. This will be mapped to it's old value
     * @param object $eventContext Context of action to record
     * @param bool $isDelete Denotes if we're deleting or updating the notification
     * @return bool Denotes if action should be recorded
     */
    private function recordNotification(&$action, $eventContext, $isDelete = false){
        $notificationType = $this->getParameter('filter_type', true);
        $notificationID = $this->getParameter('id', true);

        if(in_array($notificationType, array('Product', 'Category', HM_PRODUCTS, HM_CATEGORIES))){
            $action = $this->buildAction(($isDelete) ? 'product_category_notification_delete' : 'product_category_notification_update');

            if($notificationType === 'Product' || intval($notificationType) === HM_PRODUCTS){
                $eventContext->context2 = $notificationID;
            }
            else if($notificationType === 'Category' || intval($notificationType) === HM_CATEGORIES){
                $eventContext->context3 = $notificationID;
            }
        }
        else{
            $action = $this->buildAction(($isDelete) ? 'answer_notification_delete' : 'answer_notification_update');

            $answerSummary = $this->getAnswerSummary($notificationID);
            if($answerSummary === null) return false;

            $eventContext->context1 = $notificationID;
            $eventContext->context2 = $answerSummary;
        }

        return true;
    }

    /**
     * Populates clickstream actions when a user emails an answer.
     * @param object $eventContext Context object to record actions
     * @return bool|null False if a invalid answer is specified, null otherwise
     */
    private function emailAnswer($eventContext) {
        if (!$this->answerObserved($eventContext)) return false;

        $eventContext->context3 = $this->getParameter('to', true);
    }

    /**
     * Populates clickstream actions when a file attachment is viewed.
     * @param object $eventContext Context object to record actions
     * @return void
     */
    private function attachmentView($eventContext) {
        //array of all params
        $actionArray = $this->CI->uri->segment_array();
        if (count($actionArray) > 2 && is_numeric($actionArray[self::CONTROLLER_OFFSET]))
            $eventContext->context1 = $actionArray[self::CONTROLLER_OFFSET];

        $params = $this->CI->uri->uri_to_assoc($this->offset);
        if($params["redirect"] === '1')
        {
            $eventContext->context2 = 1;
        }
    }

    /**
     * Sets question and comment IDs as appropriate for action and interprets the `social_content_[flag|rate]` actions.
     * @param string $action The action being recorded
     * @param string $actionTag The action tag
     * @param object $eventContext The event context
     * @return string The action to record
     */
    private function socialContentActions($action, $actionTag, $eventContext) {
        $eventContext->context1 = $this->getParameter('questionID', true);
        $eventContext->context2 = $this->getParameter('commentID', true);

        if (in_array($actionTag, array('social_content_flag', 'social_content_rate'))) {
            $action = $this->buildAction(($eventContext->context2 ? 'comment' : 'question') . substr($actionTag, -5));
        }

        return $action;
    }

    /**
     * Populates clickstream context for when a search is done through the open search controller
     * @param string &$action Pass-by-reference action to record
     * @param object $eventContext Context object to record actions
     * @return void
     */
    private function openSearch(&$action, $eventContext) {
        $params = array();
        // GET parameters in the URL
        parse_str($_SERVER['QUERY_STRING'], $params);

        // Check if there's a search being performed, and record that in
        // the context. The search term can be in the 'kw' or 'q'
        // parameter. If there's pagination, then record
        // the action as a paging action, not a search.
        $searchTerm = $params['q'] ?: $params['kw'];

        if ($params['startIndex']) {
            $action .= "/" . ClickstreamActionMapping::getAction("paging");
        }
        else if ($searchTerm) {
            $action .= "/" . ClickstreamActionMapping::getAction("search");
        }

        if ($searchTerm) {
            $eventContext->context1 = rawurldecode($searchTerm);
        }
        if ($params['p']) {
            $eventContext->context2 = $params['p'];
        }
        if ($params['c']) {
            $eventContext->context3 = $params['c'];
        }
    }

    /**
     * Populates clickstream context for a marking document as viewed.
     * @param string &$action Pass-by-reference action to record
     * @param object $eventContext Context object to record actions
     * @param int &$contactID Contact ID of user. Filled in during call if not provided
     * @return int Marketing type served
     */
    private function document(&$action, $eventContext, &$contactID) {
        $action = "/{$this->controllerClassName}/{$this->controllerFunctionName}";
        $params = $this->CI->uri->uri_to_assoc($this->offset);

        if (!$contactID && ($marketingTrack = Api::generic_track_decode($params[MA_QS_ENCODED_PARM]))) {
            $contactID = $marketingTrack->c_id;
        }

        $eventContext->context1 = Sql::getDocID($params);

        return $this->CI->model('Clickstream')->getMaAppType($params[MA_QS_ITEM_PARM], $params[MA_QS_SURVEY_PARM],
            $this->getParameter('p_shortcut', true))->result;
    }

    /**
     * Gets the answer ID that is being viewed, either from GET or POST data.
     * Sets the answer id and answer summary in the context object.
     * @param object $context Context object to record actions
     * @return string Answer ID that was viewed or null if the answer doesn't exist
     */
    private function answerObserved($context)
    {
        $answerID = $this->getParameter('a_id', true) ?: $this->getParameter('a_id');
        if(!$answerID){
            $answerID = $this->getParameter('answerID', true);
        }

        $answerSummary = $this->getAnswerSummary($answerID);
        if($answerSummary === null) return null;

        $context->context1 = $answerID;
        $context->context2 = $answerSummary;

        return $answerID;
    }

    /**
     * Returns the summary for a particular answer
     * @param int $answerID Answer id viewed
     * @return string|null Summary of the answer or null
     * if the answer doesn't exist
     */
    private function getAnswerSummary($answerID)
    {
        if(is_numeric($answerID) && ($answer = $this->CI->model('Answer')->getAnswerSummary($answerID, false, false)->result)){
            return $answer[$answerID]['Summary'];
        }
        return null;
    }

    /**
    * Constructs the action to record for the specified tag.
    * @param string $actionTag Name of action
    * @return string Action to record
    */
    private function buildAction($actionTag) {
        return "/{$this->controllerClassName}/" . ClickstreamActionMapping::getAction($actionTag);
    }

    /**
     * Determines if the session should be tracked based on several conditions.
     * @param string $type Type of caller (normal or page)
     * @param string $onDemandAction Action specified by caller
     * @return bool Whether a clickstream action should be recorded
     */
    private function shouldTrackSession($type, $onDemandAction) {
        return (
            ($this->useClickstream && !Url::isCallFromTagGallery() && !Url::isPtaLogout()) && $_SERVER["HTTP_USER_AGENT"] != 'RNT_SITE_MONITOR' &&
            /* Page / Facebook controller -> render manually calls after setting page's meta property */
            (!in_array($this->controllerClassName, array('page', 'facebook'), true) || $this->controllerFunctionName !== 'render' || $type !== 'normal' || CUSTOM_CONTROLLER_REQUEST) &&
            /* Ajax controller manually calls after retrieving widget ajax handler method and clickstream action */
            ($this->controllerClassName !== 'ajax' || CUSTOM_CONTROLLER_REQUEST || $onDemandAction !== '') && ($this->controllerClassName !== 'okcsAjaxRequest')
        );
    }

    /**
     * Determines how the clickstream tracking and session should be handled.
     * @param bool &$createSession Whether a session should be created
     * @param bool &$useFakeSession Whether a fake session should be used
     * @return bool Whether or not to track the clickstream action
     */
    private function determineSessionHandling(&$createSession, &$useFakeSession) {
        // Default values
        $useClickstream = IS_PRODUCTION;
        $useFakeSession = false;
        $createSession = true;

        if (!CUSTOM_CONTROLLER_REQUEST) {
            if (strcasecmp($this->controllerClassName, 'ajaxRequestMin') === 0 ||
                (strtolower($this->controllerClassName) === 'pta' && strtolower($this->controllerFunctionName) === 'logout')) {
                $useClickstream = false;
                $useFakeSession = false;
                $createSession = true;
            }
            // 'webdav' is checked against controllerClassName despite being in the Admin folder
            // because REQUEST_URI is /dav in that case (init.php maps 'dav' to 'admin/webdav/index').
            else if (Framework::inArrayCaseInsensitive($this->ignoredControllers, $this->controllerClassName) ||
                Framework::inArrayCaseInsensitive($this->ignoredStandardRoutes, "/{$this->controllerClassName}/{$this->controllerFunctionName}") ||
                Text::beginsWith($_SERVER['REQUEST_URI'], '/ci/admin/') || $this->isIgnoredOptionsRequest()) {
                $useClickstream = false;
                $useFakeSession = $createSession = ($this->controllerClassName === 'docs' || $this->controllerClassName === 'cache');
            }
            else if ($this->controllerClassName === 'documents') {
                $params = $this->CI->uri->uri_to_assoc($this->offset);
                if (array_key_exists(MA_QS_ENCODED_PARM, $params)) {
                    $marketingTrack = Api::generic_track_decode($params[MA_QS_ENCODED_PARM]);
                    if (($marketingTrack->flags & GENERIC_TRACK_FLAG_PREVIEW) || ($marketingTrack->flags & GENERIC_TRACK_FLAG_PROOF)) {
                        $useClickstream = false;
                        $useFakeSession = false;
                        $createSession = false;
                    }
                }
            }
        }

        if ($this->CI->_getAgentAccount()) {
            $useClickstream = false;
        }

        return $useClickstream;
    }

    /**
     * Checks if the current request is igonored OPTIONS request
     * @return boolean
     */
    private function isIgnoredOptionsRequest() {
        if(Framework::inArrayCaseInsensitive($this->ignoredOptionsRequests, "/{$this->controllerClassName}/{$this->controllerFunctionName}") && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            return true;
        }
        return false;
    }
}

/**
 * Internal mapping of specific clickstream actions to defines used by dataminer
 *
 * @internal
 */
abstract class ClickstreamActionMapping
{
    private static $mapping = array(
        'home'                                 => CS_ACTION_HOME_PAGE,
        'search'                               => CS_ACTION_SEARCH,
        'paging'                               => CS_ACTION_PAGE,
        'email_answer'                         => CS_ACTION_EMAIL,

        //Answer Actions
        'answer_list'                          => CS_ACTION_ANSWER_LIST,                // visits answers/list page
        'answer_view'                          => CS_ACTION_CP_ANSWER_VIEW,             // visits answers/detail page
        'answer_view_related'                  => CS_ACTION_ANSWER_VIEW_RELATED,        // visits a related answer
        'answer_rating'                        => CS_ACTION_CP_ANSWER_RATING,           // rates an answer
        'answer_preview'                       => CS_ACTION_ANSWER_PREVIEW,             // agent previews an answer (deprecated 9.11)
        'answer_print'                         => CS_ACTION_ANSWER_PRINT,               // prints an answer (deprecated 9.11)

        //Incident Actions
        'incident_create'                      => CS_ACTION_CP_ASK,                     // visits ask page
        'incident_submit'                      => CS_ACTION_CP_INCIDENT_CREATE,         // submits AAQ form first time
        'incident_create_smart'                => CS_ACTION_CP_INCIDENT_CREATE_SMART,   // submits AAQ form after seeing SA results
        'incident_confirm'                     => CS_ACTION_CP_ASK_CONFIRM,             // visits ask_confirm page
        'incident_view'                        => CS_ACTION_INCIDENT_VIEW,              // visits an incident on questions/detail page
        'incident_print'                       => CS_ACTION_INCIDENT_PRINT,             // prints an incident (deprecated 9.11)
        'incident_list'                        => CS_ACTION_INCIDENT_LIST,              // visits questions/list page
        'incident_update'                      => CS_ACTION_INCIDENT_UPDATE,            // updates an existing incident

        //Social Actions
        'question_view'                        => CS_ACTION_CP_SOCIAL_QUESTION_VIEW,
        'question_delete'                      => CS_ACTION_CP_SOCIAL_QUESTION_DELETE,
        'question_create'                      => CS_ACTION_CP_SOCIAL_QUESTION_CREATE,
        'question_update'                      => CS_ACTION_CP_SOCIAL_QUESTION_UPDATE,
        'question_flag'                        => CS_ACTION_CP_SOCIAL_QUESTION_FLAG,
        'question_rate'                        => CS_ACTION_CP_SOCIAL_QUESTION_RATE,

        'comment_view'                         => CS_ACTION_CP_SOCIAL_COMMENT_VIEW,
        'comment_create'                       => CS_ACTION_CP_SOCIAL_COMMENT_CREATE,
        'comment_update'                       => CS_ACTION_CP_SOCIAL_COMMENT_UPDATE,
        'comment_delete'                       => CS_ACTION_CP_SOCIAL_COMMENT_DELETE,
        'comment_mark_best'                    => CS_ACTION_CP_SOCIAL_COMMENT_MARK_BEST,
        'comment_flag'                         => CS_ACTION_CP_SOCIAL_COMMENT_FLAG,
        'comment_rate'                         => CS_ACTION_CP_SOCIAL_COMMENT_RATE,
        'comment_reply'                        => CS_ACTION_CP_SOCIAL_COMMENT_REPLY,

        //Account Actions
        'account_create'                       => CS_ACTION_ACCOUNT_CREATE,
        'account_update'                       => CS_ACTION_ACCOUNT_UPDATE,
        'account_login'                        => CS_ACTION_ACCOUNT_LOGIN,
        'account_logout'                       => CS_ACTION_ACCOUNT_LOGOUT,

        //Notifications
        'answer_notification'                  => CS_ACTION_ANSWER_NOTIFICATION,
        'answer_notification_update'           => CS_ACTION_ANSWER_NOTIFICATION_UPDATE,
        'answer_notification_delete'           => CS_ACTION_ANSWER_NOTIFICATION_DELETE,
        'product_category_notification'        => CS_ACTION_PRODUCT_CATEGORY_NOTIFICATION,
        'product_category_notification_update' => CS_ACTION_PRODUCT_CATEGORY_NOTIFICATION_UPDATE,
        'product_category_notification_delete' => CS_ACTION_PRODUCT_CATEGORY_NOTIFICATION_DELETE,

        //Feedback Actions
        'answer_feedback'                      => CS_ACTION_ANSWER_FEEDBACK,
        'site_feedback'                        => CS_ACTION_SITE_FEEDBACK,

        //Chat Actions
        'chat_request'                         => CS_ACTION_CHAT_REQUEST,
        'chat_landing'                         => CS_ACTION_CHAT_LANDING,

        //Syndicated Data Services
        'opensearch_service'                   => CS_ACTION_OPENSEARCH_SERVICE,

        //Report Actions
        'report_data_service'                  => CS_ACTION_REPORT_DATA_SERVICE,

        //Attachment actions
        'attachment_view'                      => CS_ACTION_FATTACHMENT_VIEW,
        'attachement_upload'                   => CS_ACTION_FATTACHMENT_UPLOAD,

        //Misc actions
        'form_token_update'                    => CS_ACTION_FORM_TOKEN_UPDATE,
    );

    /**
     * Returns the action mapping if it exists, if not then the default value is returned
     * or the original tag is returned if neither are set.
     *
     * @param string $tag Action to look up
     * @param mixed $defaultValue Default to use if no mapping is found
     * @return string|int Action to use
     */
    public static function getAction($tag, $defaultValue = null)
    {
        if (!is_string($tag)) return (!is_null($defaultValue) ? $defaultValue : $tag);

        return isset(self::$mapping[$tag]) ? self::$mapping[$tag] : (!is_null($defaultValue) ? $defaultValue : $tag);
    }
}
