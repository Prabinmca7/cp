<?php
namespace RightNow\Models;

use RightNow\ActionCapture,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Internal\Sql\Document as Sql;

if (!IS_HOSTED){
    require_once CORE_FILES . 'compatibility/Mappings/Functions.php';
}
require_once CORE_FILES . 'compatibility/Internal/Sql/Document.php';

/**
 * Methods for handling survey/campaign/document submission and display
 */
class Document extends Base{
    const UA_SERVICE_TIMEOUT_MS = 250;
    const CLOSE_BODY_TAG_REGEX = '@(</body(?:|\s+[^>]*)>)@i';

    function __construct()
    {
        parent::__construct();
        $this->CI = get_instance();
    }

    /**
     * Returns a FOSE document
     *
     * @param int $docID The ID of the document
     * @param array $surveyData Array of values relevant to surveys
     * @param array $questionData Array of values relevant to questions
     * @param string $formShortcut The 'shortcut' to the web page
     * @param array $options Array of options that should probably be set properly
     *      -authParameter: A secret auth parameter to prevent people from flipping through survey IDs and gaining access to something they shouldn't
     *      -trackString: The tracking string
     *      -type: The type of survey
     *      -mailingIDs: Comma seperated list of mailing IDs
     *      -maCookie: The value in our marketing cookie
     *      -prefillVal: List of fields and their values that need prefilled
     *      -source: The source TBL that started the transaction (VTBL_INCIDENTS, TBL_CHATS, etc)
     *      -sourceID: The ID of the object that started the transaction
     *      -newID: The ID of the object that may have been created in the last flow_rules run
     *      -accountID: The acct_id of the agent who should be recorded in question_sessions
     *      -newSource: The TBL of the object that may have been created in the last flow_rules run
     *      -useCookie: Determines if we should use a cookie to recognize the contact
     *      -preview: Determines if we are in preview mode
     *      -forceTracking: Determines if we should force tracking
     *      -stats: Determines if we should track stats
     *      -formSubmit: Set to true if we are getting a document after a form submission
     *      -rememberCookie: Determines if we should delete the users cookie or remember it
     *
     * @return string The document
     */
    public function getDocument($docID, $surveyData, $questionData, $formShortcut, $options)
    {
        $options['prefillVal'] = htmlspecialchars(urldecode($options['prefillVal']), ENT_QUOTES, 'UTF-8', false);
        $surveyID = $surveyData['survey_id'];
        $score = $surveyData['score'];
        $track = null;
        $cID = $clickCID = $flowStatus = $flowType = 0;
        $resumeSurvey = false;
        $maCookieEmail = $maCookieTrackString = '';
        $formatID = 0;

        // Decode the url and populate tracking struct
        if($options['trackString'])
        {
            $track = generic_track_decode($options['trackString']);
            $cID = $clickCID = $track->c_id;

            // We don't want to force any type of login when in proxy mode
            if ($options['type'] === 'proxy')
                $cID = $useCID = $clickCID;
        }

        $flags = $this->getFlags($options['preview'], $track);

        list($cID, $email) = $this->getInfoFromProfileData($cID);

        if ($formShortcut || $surveyID > 0)
        {
            $bail = $this->checkPageRestrictions($surveyID, $flowID, $track, $allowAnonymous, $formShortcut, $options['source'], $options['sourceID'], $surveyData, $options['authParameter']);
            if ($bail)
                return null;

            if (($track === null || !($track->flags & GENERIC_TRACK_FLAG_PREVIEW)) && $options['rememberCookie'] && strcmp($options['type'], 'proxy') != 0)
            {
                $this->checkResumeSurvey($flowID, $options['rememberCookie'], $surveyData, $formShortcut, $resumeSurvey, $options['source'], $options['sourceID'], $cID);

                if ($resumeSurvey === true && !($track->c_id > 0) && $cID > 0)
                    $track->c_id = $cID;
            }

            // we only want to do this check on the first page - the authParameter is only input on the first page
            if ($this->shouldClearCookie($track, $options['authParameter'], $surveyData))
                destroyCookie('rnw_survey_preview', '');

            $navigate = array_key_exists('surveyNavigation', $surveyData) && $surveyData['surveyNavigation'] === 1;
            $flowInfo = Sql::getFlowInfoFromDatabase($surveyID, $resumeSurvey, $navigate, $formShortcut, $flowID, $track);

            list($flowID, $docID, $noWebFormShortcut, $wpID, $secCookie, $secTracking, $secLogin, $secForcePw, $formShortcut) = $flowInfo;

            // If we are using cookies go ahead and set up both the service and ma variables.
            // If the contact has a service cookie and not a ma cookie, go ahead and create a ma cookie.
            if ($options['useCookie'] && strcmp($options['type'], 'proxy') != 0)
            {
                if ($secCookie) {
                    if ($this->CI->session && isLoggedIn() && !$this->issetMaCookie())
                    {
                        $cookieTrackString = generic_track_encode($cID);
                        $options['maCookie'] = '||'.$email.'|'.$cookieTrackString;
                        if (($expTime = $this->getMaCookieExpTime()) != -1)
                        {
                            $cookieVal = sprintf('%s%s%s%s%s', COOKIE_SEP, COOKIE_SEP, $email, COOKIE_SEP, $cookieTrackString);
                            setCPCookie('rnw_ma_login', $cookieVal, time() + $expTime);
                        }
                    }

                    list($maCookieLogin, $maCookiePassword, $maCookieEmail, $maCookieTrackString) = explode('|', $options['maCookie']);
                }
            }

            // force_tracking is set when this is a SWP action that was reached
            // by a submit from another web form.
            if ($secLogin && strcmp($options['type'], 'proxy') != 0)
            {
                $parms = array($track, $options['trackString'], $cID, $secForcePw, $formShortcut, $surveyID, $options['authParameter'], $options['previewMobile'], $clickCID, $useCID, $wpID, $options['formSubmit'], $flowID, $surveyID, $docID, $options['relatedTbl'], $options['relatedID']);
                $this->handleSecureLogin($parms);
                // If we get here then we've identified the user & we can show the page
                $useCID = $cID;
            }
            else  // Not using login page or we are but force_tracking is set
            {
                $this->handleOtherLoginScenarios($secCookie, $secTracking, $options['useCookie'], $options['forceTracking'], $options['type'], $clickCID, $maCookieEmail, $maCookieTrackString, $cID, $track, $useCID);
            }

            if ($flowID > 0)
                list($flowStatus, $flowType) = Sql::getStatusAndTypeForFlow($flowID);

            $this->logClickAndViewTransaction($track, $clickCID, $cID, $useCID, $wpID, $options['formSubmit'], $flowID, $surveyID, $docID, true);
            // Check the status before continuing on
            $this->checkFlowStatus($flowID, $flowType, $flowStatus, $noWebFormShortcut === 1);
            $this->checkAllowAnonymous($surveyID, $flowType, $useCID, $allowAnonymous, $this->isProofOrPreview($track));

            if ($options['previewMobile'])
                $surveyData['preview_mobile'] = intval($options['previewMobile']);

            if (array_key_exists('q_sessions_type', $surveyData) && $surveyData['q_sessions_type'] === QUESTION_SESSION_TYPE_PROXY)
                $options['type'] = 'proxy';

            $surveyData['q_sessions_acct_id'] = intval($options['accountID']);

            $surveyData['cookies_enabled'] = $this->getCookiesEnabled();

            if (!(array_key_exists('q_sessions_type', $surveyData) && $surveyData['q_sessions_type']))
            {
                $isMobileUserAgent = $this->isMobileUserAgent();
                $surveyData['mobile_user_agent'] = $isMobileUserAgent;
                $surveyData['q_sessions_type'] = $this->getQuestionSessionType(($options['type'] === 'proxy'), $this->renderInMobileMode($surveyID, $isMobileUserAgent), ($surveyData['override_mobile'] === 1));
            }
            $formText = serve_doc_get($docID, $useCID, 0, GENERIC_TRACK_TYPE_MA, $flowID, $formatID, $formShortcut, $options['forceTracking'] ? '' : $maCookieEmail, $flags, 0, $options['mailingIDs'], $options['prefillVal'], $surveyData, $questionData, $options['source'], $options['sourceID'], $options['newID'], $options['newSource'], false);
        }
        else if ($docID)
        {
            $formText = serve_doc_get($docID, $cID > 0 ? $cID : $track->c_id, 0, GENERIC_TRACK_TYPE_MA, 0, $formatID, '', $options['forceTracking'] ? '' : $maCookieEmail, $flags, $options['stats'], $options['mailingIDs'], $options['prefillVal'], $surveyData, $questionData, $options['source'], $options['sourceID'], $options['newID'], $options['newSource'], false);
        }

        $this->recordAcsActionsForServeDoc($track, $docID, $flowID, $surveyData['override_mobile'] === 1);

        if (!$formText)
            return $this->handleEmptyFormText($options['preview']);

        if ($options['type'] === 'proxy')
            $this->sendProxyHeaders();

        return $formText;
    }

    /**
     * Gets info from the profile
     *
     * @param int $cID The original c_id so we don't overwrite it if the user is not logged in
     *
     * @return array containing the c_id and email IF the user is logged in, otherwise null
     */
    private function getInfoFromProfileData($cID)
    {
        if ($this->CI->session && isLoggedIn())
        {
            $cID = $this->CI->session->getProfileData('c_id');
            $email = $this->CI->session->getProfileData('email');
            return array($cID, $email);
        }
        return array($cID, null);
    }

    /**
     * Handles login scenarios not included in handleSecureLogin
     *
     * @param string $secCookie If true we should use the cookie to identify the user
     * @param bool $secTracking If true we should use email click through parameters to identify the user
     * @param bool $useCookie If true we should use the cookie if it exists
     * @param bool $forceTracking If true we force the user to be tracked if we can identify them
     * @param int $surveyType The survey's type
     * @param int $clickCID The c_id of the user who clicked the link
     * @param string $maCookieEmail The email of the user that we retrieved from the ma cookie
     * @param string $maCookieTrackString The trackingstring we parsed out of the ma cookie
     * @param int &$cID The c_id of the contact
     * @param TrackingStringObject &$track If we have a ma cookie track then this will be decoded after calling this function
     * @param int &$useCID The c_id of the contact
     */
    public function handleOtherLoginScenarios($secCookie, $secTracking, $useCookie, $forceTracking, $surveyType, $clickCID, $maCookieEmail, $maCookieTrackString, &$cID, &$track, &$useCID)
    {
        if($secCookie && $useCookie && !$forceTracking && strcmp($surveyType, 'proxy') != 0)
        {
            if($clickCID)
            {
                $cID = $clickCID;
            }
            else if($maCookieEmail || $maCookieTrackString)
            {
                if ($maCookieTrackString)
                    $cookieTrack = generic_track_decode($maCookieTrackString);

                if ($cookieTrack)
                {
                    $track = $cookieTrack;
                    if ($this->verifyContactID($track->c_id) > 0)
                        $cID  = $track->c_id;
                    else
                        $cID = $track->c_id = $useCID = 0;
                }
                else
                {
                    $match = contact_match(array('email' => $maCookieEmail));
                    $cID = $match['c_id'];
                }
            }
        }
        if(!$secCookie && !$secTracking && !$forceTracking && strcmp($surveyType, 'proxy') != 0){
            $cID = 0;// Now set it to 0 so we don't use it
            if ($track && !($track->flags & GENERIC_TRACK_FLAG_FRIEND))
                $track->c_id = 0;
        }
        if($forceTracking && !($track->flags & GENERIC_TRACK_FLAG_FRIEND))
            $useCID = $track->c_id;
        else if($cID > 0)
            $useCID = $cID;
        else if($track && $track->c_id > 0 && !($track->flags & GENERIC_TRACK_FLAG_FRIEND))
            $useCID = $track->c_id;
        else
            $useCID = 0;
    }

    /**
     * Ensures that the user is logged in properly, this function can redirect and exit
     *
     * @param array $parms A whole boat of parameters
     */
    private function handleSecureLogin($parms)
    {
        list($track, $trackString, $cID, $secForcePw, $formShortcut, $surveyID, $authParameter, $previewMobile, $clickCID, $useCID, $wpID, $formSubmit, 
            $flowID, $surveyID, $docID, $relatedTbl, $relatedID) = $parms;
       
        if (!($track->flags & GENERIC_TRACK_FLAG_PREVIEW)) 
        {
            $validSession = $this->CI->session && isLoggedIn();

            $doLogoutAndLogBackInBecausePasswordIsForced = $secForcePw && !strstr($_SERVER['REQUEST_URI'], 'sverified');

            if(!$validSession || !$cID || $doLogoutAndLogBackInBecausePasswordIsForced)
            {
                $relatedRecord = "";
                if ($relatedTbl && $relatedID)
                    $relatedRecord = "/" . MA_QS_SOURCE_PARM . "/" . $relatedTbl . "/" . MA_QS_SOURCE_ID_PARM . "/" . $relatedID; 
                $redirectPath = $this->getRedirectPath($track, $trackString, $formShortcut, $surveyID, $authParameter.$relatedRecord, $previewMobile);

                $session = get_instance()->session;
                if($session && $doLogoutAndLogBackInBecausePasswordIsForced)
                    $this->CI->model('Contact')->doLogout('');

                // Still want to log click transaction but not view.
                $this->logClickAndViewTransaction($track, $clickCID, $cID, $useCID, $wpID, $formSubmit, $flowID, $surveyID, $docID, false);
                header("Location: $redirectPath");
                exit;
            }
        }
    }

    /**
     * Checks the flow status to ensure the flow is launched, if it isn't this function will exit
     *
     * @param int $flowID The ID of the flow
     * @param int $flowType The type of the flow
     * @param int $flowStatus The status of the flow
     * @param bool $noWebFormShortcut If true there is not a shortcut to this web form
     */
    private function checkFlowStatus($flowID, $flowType, $flowStatus, $noWebFormShortcut)
    {
        if ($flowStatus != FLOW_STATUS_LAUNCHED || $noWebFormShortcut)
        {
            $flowResult = ma_get_flow_default_url($flowID, $flowType, true);
            if (isset($flowResult->redirect_url) && $flowResult->redirect_url != '')
                header(sprintf('Location: %s', $flowResult->redirect_url));
            exit;
        }
    }

    /**
     * Checks the allow anonymous flag and if you are not properly identified prints an error and exits
     *
     * @param int $surveyID The ID of the survey
     * @param int $flowType The type of the flow
     * @param int $useCID The c_id we are currently using
     * @param int $allowAnonymous Set to 1 if we are allowing anonymous survey takers
     * @param bool $preview True if we are in preview mode
     */
    private function checkAllowAnonymous($surveyID, $flowType, $useCID, $allowAnonymous, $preview)
    {
        if (($surveyID > 0 || $flowType == FLOW_SURVEY_TYPE) && $useCID <= 0 && $allowAnonymous == 0 && !$preview)
        {
            $title = getMessage(CONTACT_NOT_IDENTIFIED_LBL);
            $msg = getMessage(IDENTIFIED_CONTACTS_ACCESS_SURVEY_MSG);
            $this->printErrorMessage($msg, $title, $surveyID);
            exit;
        }
    }

    /**
     * Gets the flags for this document serve
     *
     * @param bool $preview True if we are in preview mode
     * @param trackingObject $track The tracking object
     *
     * @return The flags for this document serve
     */
    private function getFlags($preview, $track)
    {
        $flags = 0;

        if ($preview)
        {
            $flags = GENERIC_TRACK_FLAG_PREVIEW;
        }
        else if ($this->CI->rnow->isSpider())
        {
            $track = $track ?: new \stdClass();
            $track->flags = $track->flags | GENERIC_TRACK_FLAG_PROOF;
            $flags = $track->flags;
        }
        else if ($track !== null)
        {
            $flags = $track->flags ? $track->flags : 0;
        }
        return $flags;
    }

    /**
     * Records ACS actions related to the serving of a document
     *
     * @param trackingObject $track The tracking object
     * @param int $docID The doc ID
     * @param int $flowID The flow ID
     * @param bool $overrideMobile True if the user clicked our view in desktop link from a mobile page
     */
    private function recordAcsActionsForServeDoc($track, $docID, $flowID, $overrideMobile)
    {
        if (!$this->isProofOrPreview($track))
        {
            ActionCapture::record('documents', 'serveDocument', $docID);
            if ($flowID > 0)
                ActionCapture::record('documents', 'serveFlow', $flowID);
            if ($overrideMobile)
                ActionCapture::record('documents', 'viewInDesktopLinkClick', $flowID);
        }
    }

    /**
     * Sends headers that the smart client needs for proxy surveys
     */
    private function sendProxyHeaders()
    {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Handles the case where the form text is empty
     *
     * @param bool $preview If true we are in preview mode
     *
     * @return string the page content, if not in preview mode it will redirect to the RNM_DEFAULT_URL config
     */
    private function handleEmptyFormText($preview)
    {
        if($preview)
            return(sprintf('<HTML><HEAD></HEAD><BODY>%s</BODY></HTML>', getMessage(DOC_UNAVAIL_LBL)));

        $defaultUrl = getConfig(RNM_DEFAULT_URL, 'MA');
        if ($defaultUrl)
            header(sprintf('Location: %s', $defaultUrl));
    }

    /**
     * Determines if cookies are enabled
     *
     * @return int 1 if there is a cookie 0 if there is not
     */
    private function getCookiesEnabled()
    {
        return ($_COOKIE['cp_session'] || $_COOKIE['session_id'] || SURVEY_COOKIE) ? 1 : 0;
    }

    /**
     * Builds a path to a campaign page for redirect
     *
     * @param TrackObject $track The decoded tracking string
     * @param string $trackString The raw tracking string
     * @param string $formShortcut The shortcut specified in the campaign/survey editor
     * @param int $surveyID The ID of the survey (not set for campaigns)
     * @param string $authParameter The authentication paramter
     * @param bool $previewMobile Determines if we are in mobile preview mode
     *
     * @return string the path
     */
    private function getRedirectPath($track, $trackString, $formShortcut, $surveyID, $authParameter, $previewMobile)
    {
        if ($this->isProofOrPreview($track))
        {
            $loginPreview = 1;
            $trackString = '';
        }

        $nextPage = sprintf("/ci/documents/detail/2/$formShortcut/sverified/1%s%s%s%s", $trackString ? "/1/$trackString" : '', ($surveyID > 0 && strlen($authParameter) > 0) ? "/5/$surveyID/12/$authParameter" : '', $previewMobile > 0 ? '/p_pre_mobile/1' : '', ($loginPreview === 1) ? '/p_preview/1' : '');

        if (getConfig(PTA_ENABLED))
        {
            $externalLogin = replaceExternalLoginVariables(0, "$nextPage");
            $redirectPath = $externalLogin ?: getConfig(PTA_ERROR_URL);
        }
        else
        {
            $cpLoginUrl = getConfig(CP_LOGIN_URL);
            $nextPage = urlencode($nextPage);
            $redirectPath = getShortEufBaseUrl(false, "/app/$cpLoginUrl/redirect/$nextPage");
        }
        return $redirectPath;
    }

    /**
     * Displays a Message Templates document in the browser for a 'view in browser' link
     *
     * @param TrackObject $trackingDataObject The decoded tracking string
     * @param string $sessionID The session ID
     */
    private function viewServiceDocumentInBrowser($trackingDataObject, $sessionID)
    {
        $this->serviceViewInBrowserCreateTrans($trackingDataObject, MA_TRANS_EMAIL_VIEW, $sessionID);
        $this->serviceViewInBrowserCreateTrans($trackingDataObject, MA_TRANS_VIEW_IN_BROWSER, $sessionID);
        $excludeNotes = ($trackingDataObject->flags & GENERIC_TRACK_FLAG_EXCLUDE_NOTES);

        $content = serve_doc_get($trackingDataObject->doc_id, $trackingDataObject->c_id, $trackingDataObject->email_type, GENERIC_TRACK_TYPE_CSS, 0, 0, '', '', 0, 0, '', 0, array(), null, $trackingDataObject->tbl, $trackingDataObject->id, 0, 0, $excludeNotes);
        if($content != null && $track->media == MA_MAIL_TYPE_HTML && stripos($content, "</form>") !== false)
        {
            $this->insertJavascriptAndEchoDocument($content);
        }
        else
        {
            echo $content;
        }
    }

    /**
     * Displays any document (marketing mailing or message templates document) in the browser for a 'view in browser' link
     *
     * @param string $trackString The raw tracking string
     * @param string $cloud Additional information if the view was from a social network
     * @param array $surveyData The survey details
     * @param string $sessionID The session ID
     */
    public function viewDocument($trackString, $cloud, $surveyData, $sessionID)
    {
        $created = time();
        // Step 1: Decode Tracking string
        $track = generic_track_decode($trackString);

        if ($track->type === GENERIC_TRACK_TYPE_CSS)
        {
            $this->viewServiceDocumentInBrowser($track, $sessionID);
            exit;
        }

        // Step 2: Transaction logging
        if (($track) && !$this->isProofOrPreview($track))
        {
            $this->marketingViewInBrowserCreateTrans($track, MA_TRANS_PG_VIEW, $created);
            $this->marketingViewInBrowserCreateTrans($track, MA_TRANS_VIEW_IN_BROWSER, $created);
        }
        else if (strlen($cloud) > 0)
        {
            // We had to do a special encryption as opposed to using the tracking string
            // We are no longer encoding ! as eEe but we can't get rid of the str_replace due to backward compatibility
            $track->c_id = 0;
            $transType = MA_TRANS_VIEW_FROM_CLOUD;
            $decryptString = pw_rev_decrypt(decode_base64_urlsafe(str_replace('eEe', '!', $cloud)));
            list($docID, $refCID, $linkType) = explode('*', $decryptString);
            $track->doc_id = intval($docID);

            $track->flags  = GENERIC_TRACK_FLAG_FRIEND;
            if (intval($refCID) > 0)
            {
                // If we decoded a c_id then we can set this as the referrer - hence friend flag above
                $track->c_id   = intval($refCID);
            }

            if ($track->doc_id <= 0)
            {
                $msg = getMessage(CONTENT_NOT_AVAILABLE_LBL);
                $this->printErrorMessage($msg, '', $surveyData['survey_id']);
                exit;
            }

            if ($linkType > 0)
            {
                switch($linkType)
                {
                    case MA_TRANS_CLICK_TWITTER_TWEET:
                        $transType = MA_TRANS_VIEW_FROM_TWITTER;
                        break;
                    case MA_TRANS_CLICK_FACEBOOK_SHARE:
                        $transType = MA_TRANS_VIEW_FROM_FACEBOOK;
                        break;
                    case MA_TRANS_CLICK_MYSPACE_SHARE:
                        $transType = MA_TRANS_VIEW_FROM_MYSPACE;
                        break;
                }
            }

            $track->media = MA_MAIL_TYPE_HTML;
            $this->marketingViewInBrowserCreateTrans($track, $transType, $created);
            $track->c_id = 0;
        }

        $track->flags |= GENERIC_TRACK_FLAG_VIEW_IN_BROWSER;
        // Step 3: Pass the doc_id and c_id into the PE
        $content = serve_doc_get($track->doc_id, $track->c_id, 0, GENERIC_TRACK_TYPE_MA, $track->flow_id, $track->format_id, '', '', $track->flags, 0, '', 0, $surveyData, null, $surveyData['source'], $surveyData['source_id'], 0, 0, false);
        if($content != null && $track->media == MA_MAIL_TYPE_HTML && stripos($content, "</form>") !== false)
        {
            $this->insertJavascriptAndEchoDocument($content);
        }
        else
        {
            echo $content;
        }
    }

    /**
     * Checks to see if the page has previously been submitted
     * @param int $source The source TBL that started the transaction (VTBL_INCIDENTS, TBL_CHATS, etc)
     * @param int $sourceID The ID of the object that started the transaction
     * @param int $flowID The ID of the flow
     * @param array $surveyData Data related to the survey
     * @param int $surveyType The type of the survey
     *
     * @return bool returns true if there was a prior submission
     */
    private function checkForPriorSubmission($source, $sourceID, $flowID, $surveyData, $surveyType)
    {
        $checkSurvey = true;

        switch($source)
        {
            case TBL_QUESTION_SESSIONS:
                $identifier = sprintf('q_session_id');
                break;
            case VTBL_INCIDENTS:
                $identifier = sprintf('i_id');
                break;
            case VTBL_OPPORTUNITIES:
                $identifier = sprintf('op_id');
                break;
            case TBL_CHATS:
                $identifier = sprintf('chat_id');
                break;
            case VTBL_CONTACTS:
                $identifier = sprintf('c_id');
                if ($surveyType === SURVEY_TYPE_ON_DEMAND)
                    $checkSurvey = false;
                break;
        }

        if ($checkSurvey && strlen($identifier) > 0)
            $retVal = Sql::checkForPriorSubmissionSql($identifier, $sourceID, $flowID, $surveyData);

        return ($retVal);
    }

    /**
     * Determines the question session type based on the pertinent parameters
     *
     * @param bool $isProxy True if this is a proxy survey
     * @param bool $isMobile True if this is a mobile survey
     * @param bool $mobileOverride True if the user clicked the 'switch to desktop' link
     *
     * @return <int> The question session type
     */
    public function getQuestionSessionType($isProxy, $isMobile, $mobileOverride)
    {
        if ($isProxy)
            return QUESTION_SESSION_TYPE_PROXY;
        if ($isMobile && !$mobileOverride)
            return QUESTION_SESSION_TYPE_MOBILE;

        return QUESTION_SESSION_TYPE_WEB;
    }

    /**
     * Generates a secret hash so people can't easily flip through all possible survey IDs
     *
     * @param int $surveyID The ID of the survey
     *
     * @return string the auth parameter
     */
    public function generateSurveyAuthParameter($surveyID)
    {
        return sha1("{$surveyID}surVey-*-feeDback");
    }

    /**
     * Checks the given auth parameter with the survey ID to determine if the survey taker is authorized
     *
     * @param int $surveyID The ID of the survey
     * @param date $surveyCreatedDate The date when the survey was created, if the survey was created before we required the auth paramater, then it is authorized
     * @param string $authParameter The auth parameter we are ensuring is legit
     *
     * @return string the auth parameter
     */
    private function surveyIsAuthorized($surveyID, $surveyCreatedDate, $authParameter)
    {
        if (empty($authParameter))
        {
            $upgradeToAuthRequiredDate = Sql::getUpgradeToAuthRequiredDate();

            // if the survey was created before we required the auth paramater, then it is authorized
            if ($upgradeToAuthRequiredDate > 0)
                return $surveyCreatedDate < $upgradeToAuthRequiredDate;

            //if an upgrade entry is not found, return true only if the rightnow version is less than 12.8(the version in which the survey security token was introduced)
            return ((\RightNow\Internal\Utils\Version::getVersionNumber(MOD_BUILD_VER) < 12.8) ? true : false);
        }

        $hashed = $this->generateSurveyAuthParameter($surveyID);
        return $hashed === $authParameter;
    }

    /**
     * Determines if the survey is expired
     *
     * @param SurveyObject $survey The survey we are checking the expiration on
     * @param array $surveyData Other information related to the survey
     *
     * @return bool false if the survey is expired
     */
    private function checkSurveyExpiration($survey, $surveyData)
    {
        if (array_key_exists('max_responses', $survey) && $survey['max_responses'] !== null && $survey['num_started'] >= $survey['max_responses'])
        {
            $this->printErrorMessage((strlen($survey['expire_msg']) > 0 ? $survey['expire_msg'] : getMessage(RESPONSE_THRESHOLD_EXCEEDED_LBL)), '', $surveyData['survey_id']);
            return true;
        }

        if (array_key_exists('expires', $survey) && $survey['expires'])
        {
            $localTime = localtime();
            $now = mktime($localTime[2], $localTime[1], $localTime[0], $localTime[4] + 1, $localTime[3], $localTime[5] + 1900);

            if ($survey['expires'] < $now)
            {
                if (strlen($survey['expire_msg']) > 0)
                {
                    $this->printErrorMessage($survey['expire_msg'], '', $surveyData['survey_id']);
                }
                else
                {
                    $title = getMessage(SURVEY_EXPIRED_LBL);
                    $dateDone = date_str(DATEFMT_DTTM, $survey['expires']);
                    $message = getMessage(SURVEY_ACCEPTING_RESP_EXP_DATE_MSG) . ' ' . $dateDone;
                    $this->printErrorMessage($message, $title, $surveyData['survey_id']);
                }
                return true;
            }
        }

        if (array_key_exists('duration_days', $survey) && $survey['duration_days'] > 0 && $surveyData !== null)
        {
            $expirationTimestamp = $surveyData['sent_timestamp'] + ($survey['duration_days'] * 24 * 60 * 60);

            if (time() > $expirationTimestamp)
            {
                $message = strlen($survey['expire_msg']) > 0 ? $survey['expire_msg'] : getMessage(SURVEY_EXPIRED_LBL);
                $this->printErrorMessage($message, '', $surveyData['survey_id']);
                return true;
            }
        }

        return false;
    }

    /**
     * Validation method to ensure that the page can be displayed
     *
     * @param int $surveyID The ID of the survey
     * @param int &$flowID The ID of the flow
     * @param TrackObject $track The decoded tracking string
     * @param bool &$allowAnonymous Whether the survey should require login or tracking information
     * @param string $formShortcut The shortcut specified in the 'serve web page' element
     * @param int $source The source TBL that started the transaction (VTBL_INCIDENTS, TBL_CHATS, etc)
     * @param int $sourceID The ID of the object that started the transaction
     * @param array $surveyData Data related to the survey
     * @param string $authParameter The auth param
     *
     * @return bool whether the page can be displayed or not
     */
    private function checkPageRestrictions($surveyID, &$flowID, $track, &$allowAnonymous, $formShortcut, $source, $sourceID, $surveyData, $authParameter)
    {
        $surveyDisabled = 0;
        if (is_int($surveyID) && $surveyID > 0 && (!array_key_exists('surveyNavigation', $surveyData) || $surveyData['surveyNavigation'] !== 1))
        {
            $survey = survey_get(array('id' => $surveyID));

            $flowID = $survey['flow_id'];
            $allowAnonymous = $survey['allow_anonymous'];
            $surveyDisabled = $survey['disabled'];
            $survey['expire_msg'] = array_key_exists('expire_msg', $survey) ? htmlspecialchars_decode($survey['expire_msg']) : '';

            if (!$this->surveyIsAuthorized($surveyID, $survey['created'], $authParameter))
            {
                $this->printErrorMessage(getMessage(SURVEY_REQUEST_MSG), getMessage(INCORRECT_SURVEY_REQUEST_LBL), $surveyID);
                return true;
            }

            // polling surveys should never be accessed via the document model
            if ($survey['type'] === SURVEY_TYPE_POLLING)
            {
                $this->printErrorMessage(getMessage(POLLING_SURVEYS_AVAIL_WEBSITE_LINK_MSG), getMessage(INVALID_SURVEY_TYPE_LBL), $surveyID);
                return true;
            }

            if ($survey['interface_id'] !== intf_id())
            {
                $this->printErrorMessage(getMessage(CANNOT_ACCESS_WEB_PAGE_INTERFACE_MSG), getMessage(PERMISSION_DENIED_LBL), $surveyID);
                return true;
            }

            if ($this->checkSurveyExpiration($survey, $surveyData))
                return true;

            if ($source > 0 && $sourceID > 0)
            {
                if ($this->checkForPriorSubmission($source, $sourceID, $flowID, $surveyData, $survey['type']))
                {
                    $this->printErrorMessage(getMessage(RESPONSE_INPUT_APPRECIATED_MSG), getMessage(SURVEY_PREVIOUSLY_SUBMITTED_LBL), $surveyID);
                    return true;
                }
            }
            if ($survey['allow_multi_submit'] === 0 && $survey['type'] == SURVEY_TYPE_AUDIENCE && $track->c_id > 0 && !($track->flags & GENERIC_TRACK_FLAG_FRIEND))
            {
                if ($this->checkForPriorSubmission(VTBL_CONTACTS, $track->c_id, $flowID, $surveyData, $survey['type']))
                {
                    $this->printErrorMessage(getMessage(RESPONSE_INPUT_APPRECIATED_MSG), getMessage(SURVEY_PREVIOUSLY_SUBMITTED_LBL), $surveyID);
                    return true;
                }
            }
        }
        else if ($formShortcut)
        {
            list($allowAnonymous, $surveyDisabled) = Sql::getAllowAnonymousAndDisabled($formShortcut);
        }

        if ($surveyDisabled === 1 && !$this->isProofOrPreview($track))
        {
            $this->printErrorMessage(getMessage(THIS_SURVEY_HAS_BEEN_DISABLED_MSG), getMessage(SURVEY_DISABLED_LBL), $surveyID);
            return true;
        }
        return false;
    }

    /**
     * Determines if we should resume the survey or start at the first page
     *
     * @param int $flowID The ID of the flow
     * @param bool $rememberCookie Determines if we should delete the users cookie or remember it
     * @param array &$surveyData Data releveant to the survey
     * @param string &$formShortcut The shortcut specified in the 'serve web page' element
     * @param bool &$resumeSurvey Out parameter to determine if we should resum the survey
     * @param int $source The source TBL that started the transaction (VTBL_INCIDENTS, TBL_CHATS, etc)
     * @param int $sourceID The ID of the object that started the transaction
     * @param int &$cID The ID of the contact
     */
    public function checkResumeSurvey($flowID, $rememberCookie, &$surveyData, &$formShortcut, &$resumeSurvey, $source, $sourceID, &$cID)
    {
        // This looks like a lot of overhead... HOWEVER...
        // This will only execute the first query when there is a past cookie and the flow_ids match
        // It will only do further processing if it is found that there is an unfinished session
        $resumeSurvey = false;
        list($cookieLogin, $cookiePassword, $cookieFlowDoc) = explode('|', $rememberCookie);
        list($remFlowID, $remQsID, $breadCrumb) = explode('_', $cookieFlowDoc);

        switch($source)
        {
            case VTBL_INCIDENTS:
                $identifier = 'i_id';
                break;
            case VTBL_OPPORTUNITIES:
                $identifier = 'op_id';
                break;
            case TBL_CHATS:
                $identifier = 'chat_id';
                break;
            case VTBL_CONTACTS:
                $identifier = 'c_id';
                break;
        }

        if ($flowID == $remFlowID && $remQsID > 0 && $surveyData['override_mobile'] !== 1)
        {
            list($sScore, $sIID, $sOpID, $sChatID, $sDocID, $sCID, $sSurveyID, $sQuestNums, $srow) = Sql::getQuestionSessionData($flowID, $remQsID, $identifier, $sourceID);

            if ($srow)
            {
                // Now need to figure out how to get the next page... Basically, we need to examine the flow finding the last page
                // that was submitted and see where it connected to.
                $crumbs = str_replace(':', ',', getSubstringBefore($breadCrumb, '?', $breadCrumb));
                $resumeArgs = array("flow_id" => intval($flowID), "q_session_id" => intval($remQsID), "breadcrumbs" => $crumbs);
                $currentPage = getSubstringAfter($breadCrumb, '?');
                if ($currentPage)
                    $resumeArgs["current_fwp_id"] = intval($currentPage);

                $resumeResult = survey_resume_shortcut($resumeArgs);
                if ($resumeResult)
                {
                    $shortcut = $resumeResult->shortcut;

                    if (strlen($shortcut) > 0)
                    {
                        $formShortcut = $shortcut;
                        $surveyData['q_session_id'] = intval($remQsID);
                        $surveyData['score']            = $sScore;
                        $surveyData['survey_id']        = $sSurveyID;

                        if ($resumeResult->pageCount > 0)
                            $surveyData['last_page_num'] = $resumeResult->pageCount;
                        if ($sQuestNums && $resumeResult->questionCount > 0)
                            $surveyData['last_question_num'] = $resumeResult->questionCount;
                        if ($sCID > 0)
                            $cID = $sCID;

                        if ($sIID > 0)
                        {
                            $source = VTBL_INCIDENTS;
                            $sourceID = $sIID;
                        }
                        else if ($sOpID > 0)
                        {
                            $source = VTBL_OPPORTUNITIES;
                            $sourceID = $sOpID;
                        }
                        else if ($sChatID > 0)
                        {
                            $source = TBL_CHATS;
                            $sourceID = $sChatID;
                        }

                        $surveyData['source']    = $source;
                        $surveyData['source_id'] = strval($sourceID);
                        $resumeSurvey = true;
                    }
                }
            }
        }
    }

    /**
     * Logs click and view stats
     *
     * @param TrackingObject $track The decoded tracking object
     * @param int $clickCID The c_id of the contact who clicked the link
     * @param int $cID The c_id of the contact who clicked the link
     * @param int $useCID The c_id of the contact who clicked the link
     * @param int $wpID The flow_web_page ID
     * @param bool $formSubmit Set to true if we are getting a document after a form submission
     * @param int $flowID The ID of the flow
     * @param int $surveyID The ID of the survey
     * @param int $docID The ID of the document
     * @param bool $logView Determines if we should log a view or only a click
     *
     * @return nothing
     */
    public function logClickAndViewTransaction($track, $clickCID, $cID, $useCID, $wpID, $formSubmit, $flowID, $surveyID, $docID, $logView)
    {
        if ($this->isProofOrPreview($track))
            return;
        $created = time();
        // Now only logging this transaction if the link was clicked from one of our pages
        if ($wpID > 0 && $formSubmit != true && $track && $track->doc_id > 0)
        {
            // Update the database
            $pairdata = array('type'         => $surveyID > 0 ? MA_TRANS_CLICK_SURVEY_LINK : MA_TRANS_CLICK_WP_LINK,
                              'doc_id'       => $track->doc_id,
                              'flow_web_page_id' => $wpID,
                              'ref_c_id'     => 0
                              );

            if($clickCID > 0)
            {
                if($track->flags & GENERIC_TRACK_FLAG_FRIEND)
                    $pairdata['ref_c_id'] = $clickCID;
                else
                    $pairdata['c_id'] = $clickCID;
            }
            else if ($cID > 0)
            {
                if($track->flags & GENERIC_TRACK_FLAG_FRIEND)
                    $pairdata['ref_c_id'] = $cID;
                else
                    $pairdata['c_id'] = $cID;
            }

            $pairdata['format_id'] = $track->format_id;
            $pairdata['media'] = $track->media;

            if ($track->flow_id > 0)
                $pairdata['flow_id'] = $track->flow_id;
            else if ($flowID > 0)
                $pairdata['flow_id'] = $flowID;

            $isReminder = ($track->flags & GENERIC_TRACK_FLAG_SURVEY_REMINDER);

            $entry = array('ref_c_id' => $pairdata['ref_c_id'], 'c_id' => $pairdata['c_id'], 'format_id' => $pairdata['format_id'], 'media' => $pairdata['media'], 'flow_id' => $pairdata['flow_id'], 'type' => $pairdata['type'], 'created' => $created, 'doc_id' => $pairdata['doc_id'], 'flow_web_page_id' => $pairdata['flow_web_page_id'], 'is_reminder' => $isReminder, 'table' => 'ma_trans');
            $json = json_encode($entry);
            dqa_insert(DQA_DOCUMENT_STATS, $json);
        }
        // we do this here because we want to try and get the flow_id whenever possible
        if ($track && $track->doc_id > 0 && $logView == true)
        {
            // Update the database
            $pairdata = array('type' => MA_TRANS_PG_VIEW, 
                              'doc_id' => $docID,
                              'ref_c_id' => 0);

            if ($track->flags == GENERIC_TRACK_FLAG_FRIEND)
            {
                if ($useCID > 0)
                {
                    $pairdata['c_id'] = $useCID;
                    $pairdata['ref_c_id'] = $clickCID;
                }
                else if ($track->c_id > 0)
                {
                    $pairdata['ref_c_id'] = $clickCID;
                }
            }
            else if ($useCID > 0)
            {
                $pairdata['c_id'] = $useCID;
            }

            $pairdata['media'] = $track->media;
            $pairdata['format_id'] = $track->format_id;
            $pairdata['flow_id'] = $flowID;
            $pairdata['flow_web_page_id'] = $wpID;

            $entry = array('ref_c_id' => $pairdata['ref_c_id'], 'c_id' => $pairdata['c_id'], 'format_id' => $pairdata['format_id'], 'media' => $pairdata['media'], 'flow_id' => $pairdata['flow_id'], 'type' => $pairdata['type'], 'created' => $created, 'doc_id' => $pairdata['doc_id'], 'flow_web_page_id' => $pairdata['flow_web_page_id'], 'table' => 'ma_trans');
            $json = json_encode($entry);
            dqa_insert(DQA_DOCUMENT_STATS, $json);
        }
        else if($docID > 0 && $logView == true)
        {
            // Update the database
            $pairdata = array('type' => MA_TRANS_PG_VIEW, 'doc_id' => $docID);

            $pairdata['c_id'] = $useCID;
            $pairdata['flow_id'] = $flowID;
            $pairdata['flow_web_page_id'] = $wpID;

            $entry = array('c_id' => $pairdata['c_id'], 'flow_id' => $pairdata['flow_id'], 'type' => $pairdata['type'], 'created' => $created, 'doc_id' => $pairdata['doc_id'], 'flow_web_page_id' => $pairdata['flow_web_page_id'], 'table' => 'ma_trans');
            $json = json_encode($entry);
            dqa_insert(DQA_DOCUMENT_STATS, $json);
        }
    }

    /**
     * Return whether the ma cookie is set
     *
     * @return whether the ma cookie is set
     */
    private function issetMaCookie()
    {
        return isset($_COOKIE['rnw_ma_login']);
    }

    /**
     * Sorting method to sort matrix rows
     *
     * @param string $elemOne The first element we are comparing
     * @param string $elemTwo The second element we are comparing
     *
     * @return int less than, equal to, or greater than zero if the first argument is considered to be respectively less than, equal to, or greater than the second.
     */
    private function matrixRowFilterSort($elemOne, $elemTwo)
    {
        //So that we sneak through rules_runtime without bailing early, we need to:
        //1) put the regular question responses before matrix ones
        //2) sort regular responses by question_id
        //3) sort matrix responses by q_row_id
        //Matrix keys have an underscore in, regular questions don't
        $eOneHasUs = strstr($elemOne, '_');
        $eTwoHasUs = strstr($elemTwo, '_');

        if ($eOneHasUs !== false && $eTwoHasUs !== false)
        {
            // both have underscores so they're both row responses - sort by q_row_id
            $arrOne = explode('_', $elemOne);
            $arrTwo = explode('_', $elemTwo);

            $rOne = (int) str_replace("'", '', $arrOne[count($arrOne) - 1]); // sometimes it's _r_, sometimes not
            $rTwo = (int) str_replace("'", '', $arrTwo[count($arrTwo) - 1]);

            if ($rOne < $rTwo)
                return -1;
            else if ($rOne > $rTwo)
                return 1;
            else
                return 0;
        }
        else if ($eOneHasUs === false && $eTwoHasUs === false)
        {
            // neither has an underscore so they're both regular responses - sort by q_id
            $rOne = (int) $elemOne;
            $rTwo = (int) $elemTwo;
            if ( $rOne < $rTwo )
                return -1;
            else if ( $rOne > $rTwo )
                return 1;
            else
                return 0;
        }
        else
        {
            if ($eOneHasUs !== false)
                return 1; // row responses go after regular, always
            else
                return -1; // regular responses go first
        }
    }

    /**
     * Handler for page to page navigation
     *
     * @param array $data The data that was submitted on the page we are leaving
     * @param array $surveyData Array of values relevant to surveys
     * @param array $questionData Array of values relevant to questions
     * @param array $questionOrder The order of the questions
     * @param array $typeData Array containing the type (text, input, matrix) of each question
     */
    public function navigateInPage($data, $surveyData, $questionData, $questionOrder, $typeData)
    {
        $pt = $this->CI->input->post('p_t');
        $surveyData['surveyNavigation'] = 1;
        if(isset($_COOKIE[$data['surveyCookieName']]))
        {
            $surveyData['survey_id'] = $data['surveyID'];

            list($cookieLogin, $cookiePassword, $cookieFlowDoc) = explode('|', $_COOKIE[$data['surveyCookieName']]);
            list($remFlowID, $remQsID, $breadCrumb) = explode('_', $cookieFlowDoc);
            $crumbs = explode(':', $breadCrumb);

            if (isset($_POST['prev_btn'])) //Back Button
            {
                $wpID = $surveyData['flow_web_page_id'];
                $last = array_pop($crumbs);
                if(stringContains($last, '?'))
                {
                    list($lastID, $currID) = explode('?', $last);
                    if ($wpID !== intval($currID))
                    {
                        $fwpID = $currID;
                    }
                    else
                    {
                        $key = array_search($currID, $crumbs);
                        if($key !== false)
                        {
                            $fwpID = $crumbs[$key - 1];
                        }
                        $last = "$lastID?$fwpID";
                        array_push($crumbs, $last);
                    }
                }
                else
                {
                    $fwpID = $last;
                    $last = "{$fwpID}:{$wpID}?{$fwpID}";
                    array_push($crumbs, $last);
                }

                $surveyData['next_page_id'] = $wpID;
                $surveyData['back_button_request'] = true;

                // we need to subtract one from the last_page num because we are going backwards one page
                // we need to subtract another because it has been incremented one additional time during the back button request
                if ($surveyData['last_page_num'] > 1)
                    $surveyData['last_page_num'] = $surveyData['last_page_num'] - 2;
            }
            else //Next Page Request
            {
                $fwpID = $this->CI->input->post('p_next_id');
                $last = array_pop($crumbs);
                if (stringContains($last, '?'))
                {
                    list($lastID, $currID) = explode('?', $last);
                    $key = array_search($currID, $crumbs);
                    if ($key !== false)
                    {
                        if ($key < (count($crumbs) - 2))
                        {
                            $surveyData['next_page_id'] = intval($crumbs[$key + 2]);
                        }
                        else if ($key < (count($crumbs) - 1))
                        {
                            $surveyData['next_page_id'] = intval($lastID);
                        }

                        if ($key < (count($crumbs) - 1))
                        {
                            $last = "$lastID?$fwpID";
                            array_push($crumbs, $last);
                        }
                    }
                }
            }
            $breadCrumb = implode(':', $crumbs);
            $crumbs = explode(':', getSubstringBefore($breadCrumb, '?'));
            if ($remQsID > 0 && count(array_unique($crumbs)) !== count($crumbs))
            {
                //Cookie is corrupt. Force rebuild.
                $fwpID = 0;
            }

            $resumeArgs = array(
                    "flow_id" => intval($remFlowID),
                    "q_session_id" => intval($remQsID),
                    "flow_web_page_id" => intval($fwpID),
                    "survey" => $surveyData,
                    "questions" => $questionData,
                    "question_order" => $questionOrder,
                    "question_types" => $typeData,
                    "rebuild_crumbs" => true
                    );

            $resumeResult = survey_resume_shortcut($resumeArgs, true);
            $shortcut = $resumeResult->shortcut;

            if (strlen($resumeResult->breadcrumbs) > 0)
            {
                $breadCrumb = $resumeResult->breadcrumbs;
                unset($surveyData['next_page_id']);
            }

            $expTime = get_survey_cookie_exp_time();
            if (strlen($shortcut) === 0)
            {
                // at this point we are assuming that the user messed with their cookie
                // most likely in a malicious manner -- empty the cookie
                setCPCookie($data['surveyCookieName'], '', time() + $expTime);
            }
            else
            {
                $remQsID = $this->getQSessionID($remQsID, $resumeResult);
                // Setting cookie to remember the session
                $cookieVal = sprintf('%s%s%d_%d_%s', COOKIE_SEP, COOKIE_SEP, $remFlowID, $remQsID, $breadCrumb);
                if ($expTime > 0)
                    setCPCookie($data['surveyCookieName'], $cookieVal, time() + $expTime);
            }
        }
        else
        {
            $resumeArgs = array(
                    "flow_id" => intval($remFlowID),
                    "q_session_id" => intval($surveyData['q_session_id']),
                    "survey" => $surveyData,
                    "questions" => $questionData,
                    "question_order" => $questionOrder,
                    "question_types" => $typeData
                    );

            $resumeResult = survey_resume_shortcut($resumeArgs, true);
            $shortcut = $resumeResult->shortcut;
        }

        if (strlen($shortcut) === 0)
        {
            // at this point we are assuming that the user messed with their cookie
            // most likely in a malicious manner -- take them back to the first page of the survey
            $surveyLocation = $this->CI->model('Survey')->buildSurveyURL($data['surveyID']);
            header("Location: $surveyLocation");
            exit;
        }

        $authParam = $data['surveyID'] > 0 ? $this->generateSurveyAuthParameter($data['surveyID']) : '';

        $options = array('trackString' => $pt, 'useCookie' => false, 'source' => $surveyData['source'], 'sourceID' => $surveyData['source_id'],
                         'mailingIDs' => '', 'preview' => false, 'forceTracking' => true, 'stats' => false,
                         'maCookie' => '', 'prefillVal' => '', 'formSubmit' => true, 'type' => '',
                         'rememberCookie' => '', 'newID' => 0, 'newSource' => 0, 'authParameter' => $authParam,
                         'accountID' => $surveyData['q_sessions_acct_id'], 'previewMobile' => $surveyData['preview_mobile']);

        $surveyData['q_session_id'] = intval($remQsID);

        //fetch question session data corresponding to the current flow id and question session id
        $qSessionData = Sql::getQuestionSessionData($data['flowID'], $surveyData["q_session_id"], "", "");
        if(count($qSessionData) > 0)
        {
            //update surveyData with the latest score
            $surveyData["score"] = $qSessionData[0];
        }

        $formText = $this->getDocument(false, $surveyData, $questionData, $shortcut, $options);
        $this->insertJavascriptAndEchoDocument($formText);
    }

    /**
     * Retrieves the actual session id which is set in different ways depending on whether user is answering survey questions
     *
     * @param string $remQsID The session id from cookie (may not be set if no questions answered on first page)
     * @param object $resumeResult The returned object from the server call to resume the survey
     *
     * @return int - the correct question_session_id
     */
    private function getQSessionID($remQsID, $resumeResult)
    {
        return intval($remQsID) > 0 ? $remQsID : $resumeResult->questionSessionID;
    }

    /**
     * Adds RightNow.Compatibility.MarketingFeedback.js to the document and echos the result
     *
     * @param string $document The document that is to be output
     */
    public function insertJavascriptAndEchoDocument($document)
    {
        list($location) = \RightNow\Environment\retrieveModeAndModeTokenFromCookie();
        $devMode = ($location === 'development');
        $filePath = (IS_HOSTED || !$devMode) ? '/euf/core/static/RightNow.Compatibility.MarketingFeedback.js' : '/euf/core/debug-js/RightNow.Compatibility.MarketingFeedback.js';
        if($this->CI->session) {
            //we need a session to generate form token
            $formToken = \RightNow\Utils\Framework::createTokenWithExpiration(0);
            $tokenScript = "<script> window.onload = function() {
                           var forms = document.getElementsByTagName('form');
                           for(i = 0; i < forms.length; i++) {
                            var formElement = forms[i];
                            if(formElement.name == '_main')
                            {
                             var f_tok = document.createElement('input');
                             f_tok.setAttribute('type', 'hidden');
                             f_tok.setAttribute('name', 'f_tok');
                             f_tok.setAttribute('value', '$formToken');
                             formElement.insertBefore(f_tok, formElement.lastChild);
                             formElement.action = formElement.action.replace(/^(http|https):/, '');
                            }
                           }
                          }</script>";
            echo insertBeforeTag($document, "<script type='text/javascript' src='$filePath'></script> $tokenScript", self::CLOSE_BODY_TAG_REGEX);
        }
        else {
            echo insertBeforeTag($document, "<script type='text/javascript' src='$filePath'></script>", self::CLOSE_BODY_TAG_REGEX);
        }
    }

    /**
     * Queries the database for information about the tracking string
     *
     * @param string $trackParam The raw tracking string
     * @param array &$data Output parameter that is populated after the query
     */
    public function getTrackData($trackParam, &$data)
    {
        $formatID = 0;

        $track = generic_track_decode($trackParam);
        if($track)
        {
            $formatID           = $track->format_id;
            $data['flowID']     = $track->flow_id;
            $data['docID']      = $track->doc_id;
            $data['flags']      = $track->flags;
            $data['isProofOrPreview'] = $this->isProofOrPreview($track);
            $data['surveyCookieName'] = $data['isProofOrPreview'] ? 'rnw_survey_preview' : 'rnw_survey_login';

            if($data['flags'] & GENERIC_TRACK_FLAG_FRIEND)
            {
                $data['cID'] = 0;
            }
            else
            {
                $data['cID'] = $track->c_id;
            }
        }

        // Try to get the mailing id if it wasn't in the tracking string...
        // This might happen if the bulk mailing was launched before the flow was saved.
        if ($data['flowID'] <= 0)
        {
            $data['flowID'] = Sql::getFlowIDForMailing($formatID);
            if($data['flowID'] <= 0)
                $data['flowID'] = 0;
        }

        // If we have the flow then get the campaign or survey_id
        if ($data['flowID'] > 0)
            Sql::setUpFlowData($data);

        // Try to find the state to start at
        if($data['flowID'] > 0 && $data['stateID'] <= 0)
            Sql::setUpStateID($params, $formatID, $data);
    }

    /**
     * Parses the raw fields into either surveyFields, optInFields or server side validation fields
     *
     * @param array $fields The raw fields
     * @param array &$surveyFields The output parameter survey fields
     * @param array &$optInFields The output parameter opt in fields
     * @param array &$ssvFlds The output parameter server sude validation fields
     */
    public function parseSubmitFields($fields, &$surveyFields, &$optInFields, &$ssvFlds)
    {
        foreach ($fields as $key => $value)
        {
            // NOTE: We need to do the preg_match because we're now including
            // these val_* fields for each element of a date & date time field.
            // We skip those by ensuring that the last character of the val_ label
            // is a number, which is the field ID.  The date fields have _mon, _yr,
            // etc... as their last characters.
            if ((strncmp($key, 'val_wf_)', 7) == 0) && (preg_match('/[0-9]$/', $key)))
            {
                // Split up the hidden input value that contains data about
                // the preceding form field.

                // Format: 'field_name','label',field_type,max_length,flags, mask, minval, maxval
                // split the first two fields using ',(') and the last 5 using ,
                $tmpOne = preg_split("/\',\'?/", $value, 3);
                $tmpTwo = preg_split('/,/', $tmpOne[2], 6);
                $tmpOne = array_slice($tmpOne, 0, 2);
                $tmp = array_merge($tmpOne, $tmpTwo);

                // trim the beginning quote (') character off of field_name
                $tmp[SSV_NAME] = substr($tmp[SSV_NAME], 1);
                // trim single quotes off of custom field's mask value
                $tmp[SSV_MASK] = trim($tmp[SSV_MASK], "'");
                $ssvFlds[] = $tmp;

                // While we're here, cache the opt-in fields.
                if($tmp[SSV_FLAGS] & V_FLAG_IS_CHECKBOX)
                    $optInFields[$tmp[SSV_NAME]] = 1;
            }
            // validate survey questions
            else if (strncmp($key, 'val_q_)', 6) == 0)
            {
                $tmp = preg_split('/,/', $value, 8);
                $surveyFields[] = $tmp;
            }
        }
    }

    /**
     * Runs server side validation on the fields
     *
     * @param array $data A data struct that we use to get the survey ID
     * @param array $ssvFlds The non-survey fields being validated with server side validation (ssv)
     * @param array $surveyFields The survey fields being validated
     *
     * @return bool true if validation is passed, false if not
     */
    public function validateSubmitFields($data, $ssvFlds, $surveyFields)
    {
        $errMsg = $this->ssvCheckFields($ssvFlds);
        if(count($surveyFields) > 0)
        {
            $errors = $this->ssvCheckSurveyFields($surveyFields);
            foreach ($errors as $err)
            {
                if (strlen($err['choice_text']) > 0)
                    $errMsg .= sprintf('%s: %s [%s: %s]<br />', getMessage(ERROR_MSG), $err['error_text'], getMessage(CHOICES_LBL), $err['choice_text']);
                else
                    $errMsg .= sprintf('%s: %s [%s: %s]<br />', getMessage(ERROR_MSG), $err['error_text'], getMessage(QUESTION_TEXT_LC_LBL), $err['question_text']);
            }
        }

        if ($errMsg)
        {
            $errMsg .= ' ' . getMessage(BACK_UP_TO_CORRECT_MSG);
            $this->printErrorMessage($errMsg, '', $data['surveyID']);
            return false;
        }
        return true;
    }

    /**
     * Parses the raw fields into either webFormFields, surveyFields or optInFields
     *
     * @param array $params The entire $_REQUEST super global
     * @param array &$data The output parameter containing data
     * @param array &$surveyData The output parameter containing survey data
     * @param array &$optInFields The output parameter containing opt in fields
     */
    public function parseSubmitParams($params, &$data, &$surveyData, &$optInFields)
    {
        // This is a map that lets us refer to table columns generically.
        $idColumnMap = ma_get_field_column_map(VTBL_CONTACTS);
        $cfParams = $pairdata = $questionData = $typeData = array();
        $fieldCount = $nonEmptyFieldCount = $questionCount = 0;
        $markedArray = array();

        foreach ($params as $key => $value)
        {
            if(strncmp($key, 'wf_', 3) == 0)
            {
                $this->parseWebFormField($optInFields, $cfParams, $pairdata, $fieldCount, $nonEmptyFieldCount, $idColumnMap, $value, $key, $markedArray, $data);
            }
            // Check for question responses
            else if((strncmp($key, 'q_session_id', 12) != 0) && (strncmp($key, 'q_', 2) == 0 || (strncmp($key, 'other_', 6) == 0)))
            {
                $this->parseQuestionResponse($key, $value, $questionData, $questionCount);
            }
            else if(strncmp($key, 'val_q_', 6) == 0)
            {
                list($question, $minsize, $maxsize, $type, $garbage) = explode(',', $value, 5);
                $typeData[$question] = intval($type);
            }
            else if($key == '_last_q_num')
            {
                $surveyData['last_question_num'] = intval($value);
            }
            else if($key == '_q_session_id')
            {
                $surveyData['q_session_id'] = intval($value);
            }
            else if($key == '_prev_q_num')
            {
                $surveyData['prev_question_num'] = intval($value);
            }
            else if($key == '_survey_score')
            {
                $surveyData['score'] = intval($value);
            }
            else if($key == '_last_page_num')
            {
                $surveyData['last_page_num'] = intval($value);
            }
            else if($key == '_source')
            {
                $surveyData['source'] = intval($value);
            }
            else if($key == '_source_id')
            {
                $surveyData['source_id'] = $value;
            }
            else if($key === 'p_question_session_type')
            {
                $surveyData['q_sessions_type'] = intval($value);
            }
            else if($key === 'p_question_session_acct_id')
            {
                $surveyData['q_sessions_acct_id'] = intval($value);
            }
            else if($key === 'p_preview_mobile')
            {
                $surveyData['preview_mobile'] = intval($value);
            }
            else if($key === 'p_is_mobile')
            {
                $surveyData['mobile_user_agent'] = intval($value);
            }
        }

        // Now look for opt_in fields that were in the from and not checked
        $this->parseOptInFields($optInFields, $cfParams, $pairdata, $fieldCount, $idColumnMap);

        $data['idColumnMap'] = $idColumnMap;
        $data['cfParams'] = $cfParams;
        $data['pairdata'] = $pairdata;
        $data['questionData'] = $questionData;
        $data['typeData'] = $typeData;
        $data['fieldCount'] = $fieldCount;
        $data['nonEmptyFieldCount'] = $nonEmptyFieldCount;
        $data['questionCount'] = $questionCount;
    }

    /**
     * Function to parse a web form field
     *
     * @param array &$optInFields The output parameter containing opt in fields
     * @param array &$cfParams The output paramter containing custom field paramteres
     * @param array &$pairdata The output parameter containing pair data
     * @param int &$fieldCount The output parameter containiing the field count
     * @param int &$nonEmptyFieldCount The output parameter containing the count of non-empty fields
     * @param array $idColumnMap A map to help us do things generically
     * @param string $value The value of the field
     * @param string $key The key of the field
     * @param array &$markedArray An array for handling first/last name since they both need to go under 'name'
     * @param array $data Another data array containing data
     *
     * @return nothing
     */
    private function parseWebFormField(&$optInFields, &$cfParams, &$pairdata, &$fieldCount, &$nonEmptyFieldCount, $idColumnMap, $value, $key, &$markedArray, $data)
    {
        $fieldCount++;
        list($ig, $tbl, $fld) = explode('_', $key, 3);
        // don't even bother to check the flags, just clear out any opt_in field with this name
        $wasOptIn = $optInFields[$key] == 1;
        unset($optInFields[$key]);

        if($value != '')
            $nonEmptyFieldCount++;
        else if ($value == '' && $data['cID'] == 0)
            return;

        // If a custom field, add to a list until later
        if ($fld < MAX_CF)
        {
            $lbl = "p_ccf_$fld";
            // Checkboxes come in as 'on'
            $cfParams[$lbl] = ($wasOptIn && $value == 'on') ? '1' : $value;
        }
        else
        {
            if ($idColumnMap->$fld)
            {
                if ($idColumnMap->$fld->nested_name)
                {
                    $nameSlot = ($idColumnMap->$fld->alt_nest_name) ? $idColumnMap->$fld->alt_nest_name : $idColumnMap->$fld->column;
                    $tempData = array();

                    // if we already have an entry for this nested name we need to add to it
                    // without wiping out the old data.......
                    if ($markedArray[$idColumnMap->$fld->nested_name])
                        $tempData = $markedArray[$idColumnMap->$fld->nested_name];

                    if ($idColumnMap->$fld->is_int && $value == 'on')
                        $tempData[$nameSlot] = 1;
                    else if ($idColumnMap->$fld->is_int && $value == '0')
                        $tempData[$nameSlot] = 0;
                    else if ($idColumnMap->$fld->is_int)
                        $tempData[$nameSlot] = intval($value) ? intval($value) : INT_NULL;
                    else
                        $tempData[$nameSlot] = $value;

                    $pairdata[$idColumnMap->$fld->nested_name] = $tempData;
                    $markedArray[$idColumnMap->$fld->nested_name] = $tempData;
                }
                else if ($idColumnMap->$fld->is_int)
                {
                    if ($value == 'on')
                        $pairdata[$idColumnMap->$fld->column] = 1;
                    else
                    {
                        if($value == '0')
                            $pairdata[$idColumnMap->$fld->column] = 0;
                        else
                            $pairdata[$idColumnMap->$fld->column] = intval($value) ? intval($value) : INT_NULL;
                    }
                }
                else
                {
                    $pairdata[$idColumnMap->$fld->column] = $value;
                }
            }
        }
    }
    /**
     * Parses Question Responses
     * @param string $key The question id
     * @param string $value The question response value
     * @param array &$questionData The output parameter containing question data
     * @param int &$questionCount The output parameter containing the question count
     */
    private function parseQuestionResponse($key, $value, &$questionData, &$questionCount)
    {
        if(strpos($key, '_r_') === false)
        {
            list($ig, $question, $choice) = array_pad(explode('_', $key, 3), 3, null);
        }
        else
        {
            list($ig, $q, $dum, $row, $choice) = array_pad(explode('_', $key, 5), 5, null);
            $question = $q . '_' . $row;
        }

        // A SELECT element with the MULTIPLE attribute will return an *array* as the value.
        $newValue = is_array($value) ? implode(';', $value) : $value;

        // We test for $newValue because our '--' menu items don't have a value
        if ($newValue !== null && $newValue !== '')
        {
            if(isset($questionData[$question]))
            {
                if (strncmp($ig, 'other', 5) == 0)
                {
                    if (substr($newValue, -1) === '\\')
                    {
                        $newValue = substr($newValue, 0, strlen($newValue) - 1);
                    }
                    $newValue = '_other_' . str_replace(';', '\;', $newValue);
                }
                $questionData[$question] = $questionData[$question] . ';' . $newValue;
            }
            else
            {
                $questionData[$question] = $newValue;
                $questionCount++;
            }
        }
    }

    /**
     * Function to parse opt in fields
     *
     * @param array &$optInFields The output parameter containing opt in fields
     * @param array &$cfParams The output paramter containing custom field paramteres
     * @param array &$pairdata The output parameter containing pair data
     * @param array &$fieldCount The output parameter containiing the field count
     * @param array $idColumnMap A map to help us do things generically
     */
    private function parseOptInFields(&$optInFields, &$cfParams, &$pairdata, &$fieldCount, $idColumnMap)
    {
        foreach ($optInFields as $key => $value)
        {
            list($ig, $tbl, $fld) = explode('_', $key, 3);

            // If a custom field, add to a list until later
            if ($fld < MAX_CF)
            {
                $lbl = "p_ccf_$fld";
                $cfParams[$lbl] = '0';
            }
            else
            {
                if ($idColumnMap->$fld)
                {
                    $fieldCount++;
                    $pairdata[$idColumnMap->$fld->column] = 0;
                }
            }
        }
    }

    /**
     * Determines if the survey was already submitted
     *
     * @param array $data Array containing data pertinent to this function
     * @param array &$surveyData The output parameter that is also used as an input parameter
     *
     * @return bool false if the survey has NOT been submitted
     */
    public function surveyAlreadySubmitted($data, &$surveyData)
    {
        $qsID = array_key_exists('q_session_id', $surveyData) ? $surveyData['q_session_id'] : 0;
        if ($data['flowID'] > 0 && $qsID > 0 && $this->checkForPriorSubmission(TBL_QUESTION_SESSIONS, $qsID, $data['flowID'], $surveyData, 0))
            return true;

        if ($data['flowID'] > 0 && $data['surveyID'] > 0 && ($data['cID'] > 0 || (array_key_exists('source', $surveyData) && $surveyData['source'] === TBL_CHATS )))
        {
            list($surveyType, $surveyMulti) = Sql::getTypeMultiSubmitForSurvey($data['surveyID']);
            if ($surveyType == SURVEY_TYPE_AUDIENCE && $surveyMulti == 0)
                $bail = $this->checkForPriorSubmission(VTBL_CONTACTS, $data['cID'], $data['flowID'], $surveyData, $surveyType);
            if ($surveyData['source'] > 0 && strlen($surveyData['source_id'] > 0) && !$bail)
                $bail = $this->checkForPriorSubmission($surveyData['source'], $surveyData['source_id'], $data['flowID'], $data['surveyData'], $surveyType);

            if ($bail)
                return true;

            $surveyData['survey_type'] = $surveyType;
        }

        return false;
    }

    /**
     * Housholding logic wrapper
     *
     * @param array &$data Array containing data pertinent to this function, also an output parameter
     * @param array $contact A contact record from contact_get
     */
    public function matchContactIfChanged(&$data, $contact)
    {
        $pairdata = $data['pairdata'];
        // Because of householding, we need to make sure we loaded the correct contact
        // If the form loads with contact A info and they overwrite first/last for contact B
        // we need to recognize that and update Contact B.
        if (array_key_exists('name', $pairdata) && array_key_exists('email', $pairdata) && (strlen($pairdata['email']['addr']) > 0) && $contact != null)
        {
            $mFirstName = $pairdata['name']['first'];
            $mLastName  = $pairdata['name']['last'];
            $mEmail    = $pairdata['email']['addr'];

            // However, we only do this if they have left the email the same as the contact that was originally matched
            // Otherwise this will follow our other code path of telling the user they are logged in and giving the option to log out
            if ($contact->Emails[0]->Address === $mEmail)
            {
                $match = contact_match(array('email' => $mEmail, 'first' => $mFirstName, 'last' => $mLastName));
                if ($match['c_id'] > 0 && $match['partial_name_match'] === 1)
                    $data['cID'] = $match['c_id'];
            }
        }
    }

    /**
     * Method to handle the flow result error scenarios as well as return if the flow result has an error
     *
     * @param array $data Array containing data pertinent to this function
     * @param array $result The flow result data
     * @param string $shortcut The shortcut to the FWP
     * @param array $contact The contact from contact_get
     *
     * @return bool true if the result has an error
     */
    public function flowResultHasError($data, $result, $shortcut, $contact)
    {
        $surveyID = $data['surveyID'];
        $cID = $data['cID'];

        if ($result === -1)
        {
            $msg = getMessage(ERROR_DUE_INVALID_CONTACT_RECORD_LBL);
            $this->printErrorMessage($msg, '', $surveyID);
            return true;
        }
        else if ($result === -2)
        {
            include get_cfg_var('doc_root') . '/ma/cci/top.phph';

            $httpParams = $_SERVER['HTTP_REFERER'];

            // If it is a survey then they their entire survey session is invalid as it is for the wrong contact...
            // So, we send them back to the first page of the survey.
            if ($surveyID > 0)
            {
                $authParameter = $this->generateSurveyAuthParameter($surveyID);
                $httpParams = getShortEufBaseUrl(false, "/ci/documents/detail/5/$surveyID/p_clear_cookie/1/12/$authParameter");
            }
            else
            {
                $httpParams = getShortEufBaseUrl(false, "/ci/documents/detail/2/$shortcut/p_clear_cookie/1");
            }

            echo getMessage(EMAIL_ADDR_EXISTS_MSG);
            if ($cID > 0 && $contact != null)
            {
                $email = $contact->Emails[0]->Address;
                $fName = $contact->Name->First;
                $lName = $contact->Name->Last;
            }
            echo '<span class="text">';
            echo sprintf(getMessage(LOGGED_PCT_S_PCT_S_PCT_S_COLON_MSG), $fName, $lName, $email);
            echo '<br />';
            echo "<a href=\"$httpParams\">";
            echo getMessage(CLICK_HERE_TO_LOG_OUT_CMD);
            echo "</a>";
            include get_cfg_var('doc_root') . '/ma/cci/bottom.phph';
            return true;
        }
        return !is_object($result);
    }

    /**
     * Wrapper to run flow rules
     *
     * @param array &$data Array containing data pertinent to this function
     * @param array &$surveyData Array containing survey data
     * @param array $questionData Array containing question data
     * @param array $questionOrder The order of the questions
     * @param array $typeData Array containing the type (text, input, matrix) of each question
     * @param int $transSource The src_lvl for this transaction
     *
     * @return array the flow result
     */
    public function runFlowRules(&$data, &$surveyData, $questionData, $questionOrder, $typeData, $transSource)
    {
        $pairdata = $data['pairdata'];
        $customFields = getCustomFieldList(VTBL_CONTACTS, VIS_MA_WEB_FORM);
        // Now convert from cf parms to pairs
        if (count($data['cfParams']) > 0 && count($customFields) > 0)
            cfparms2pairs($pairdata, $data['cfParams'], 'p_ccf_', true, $customFields);

        $pairdata['source_upd']  = $transSource;
        if ($data['cID'] > 0)
            $pairdata['c_id'] = $data['cID'];
        if ($data['docID'] > 0)
            $surveyData['doc_id'] = $data['docID'];

        if($data['fieldCount'] + $data['questionCount'] > 0)
            $flowResult = flow_rules($data['cID'], $data['flowID'], $data['stateID'], true, $data['docID'], $data['nonEmptyFieldCount'], $pairdata, $surveyData, $questionData, $questionOrder, $typeData, $data['fieldCount']);
        else
            $flowResult = flow_rules($data['cID'], $data['flowID'], $data['stateID'], true, $data['docID'], $data['nonEmptyFieldCount'], $pairdata, $surveyData);

        sql_commit();
        return $flowResult;
    }

    /**
     * Adds a submit transaction to DQA/ACS
     *
     * @param array &$data Array containing data pertinent to this function
     * @param array $flowResult The flow result from flow_rules
     */
    public function addSubmitTransaction(&$data, $flowResult)
    {
        $created = time();
        // After a flow_rules run, see if we created a contact. If so, grab their new c_id so we can make a transaction.
        if ($data['cID'] <= 0 && property_exists($flowResult, 'c_id') && $flowResult->c_id > 0)
            $data['cID'] = $flowResult->c_id;

        if ($data['docID'] > 0)
        {
            $transPairdata = array('c_id' => $data['cID'] > 0 ? $data['cID'] : INT_NOT_SET,
                                   'type' => MA_TRANS_PG_SUBMIT,
                                   'doc_id' => $data['docID'],
                                   'flow_web_page_id' => $data['wpID'] > 0 ? $data['wpID'] : INT_NOT_SET,
                                   'flow_id' => $data['flowID'] > 0 ? $data['flowID'] : INT_NOT_SET);
            if (!$data['isProofOrPreview'])
            {
                $entry = array('c_id' => (int)$transPairdata['c_id'], 'created' => $created, 'flow_id' => $transPairdata['flow_id'], 'type' => $transPairdata['type'], 'doc_id' => $transPairdata['doc_id'], 'flow_web_page_id' => $transPairdata['flow_web_page_id'], 'table' => 'ma_trans');

                $json = json_encode($entry);
                dqa_insert(DQA_DOCUMENT_STATS, $json);

                ActionCapture::record('documents', 'submitDocument', $data['docID']);
                if ($data['flowID'] > 0)
                    ActionCapture::record('documents', 'submitFlow', $data['flowID']);
            }
        }
        sql_commit();
    }

    /**
     * Sets the MA cookie
     *
     * @param array $data Array containing data pertinent to this function
     * @param array $flowResult The flow result from flow_rules
     *
     * @return bool true if the cookie is set properly
     */
    public function setMaCookie($data, $flowResult)
    {
        $forceCookieSet = false;
        //Set the cookie if we're supposed to.
        $cookie = MA_COOKIE;

        if($data['setCookie'])
        {
            list($cookieLogin, $cookiePassword, $cookieEmail, $cookieTrackString) = explode('|', $cookie);
            //We need to get info from the contacts table so we can fill out the ma_trans entry correctly
            if($cookieTrackString)
                $cookieTrack = generic_track_decode($cookieTrackString);

            if($cookieEmail)
            {
                $match = contact_match(array('email' => $cookieEmail));
                $cookieContactID = $match['c_id'];
            }

            if( ($data['cID'] > 0 && ($cookieContactID != $data['cID'])) || ($cookieTrack->c_id > 0 && $cookieTrack->c_id != $cookieContactID) || (isset($flowResult) && ($flowResult->c_id > 0) && ($cookieContactID != $flowResult->c_id)))
                $forceCookieSet = 1;
        }

        if ($forceCookieSet || ($data['setCookie'] && !isset($cookie) && (($data['cID'] > 0 && !isset($flowResult)) || (isset($flowResult) && ($flowResult->c_id > 0) && isset($flowResult->contact_email)))))
        {
            if (($expTime = $this->getMaCookieExpTime()) != -1)
            {
                $cookieTrackString = generic_track_encode($flowResult->c_id);
                $cookieVal = sprintf('%s%s%s%s%s', COOKIE_SEP, COOKIE_SEP, $flowResult->contact_email, COOKIE_SEP, $cookieTrackString);
                setCPCookie('rnw_ma_login', $cookieVal, time() + $expTime);
            }
        }

        return true;
    }

    /**
     * Gets the survey cookie
     *
     * @param string $surveyCookie The existing survey cookie value
     * @param array $data Array containing data pertinent to this function
     * @param array $flowResult The flow result from flow_rules
     *
     * @return string the survey cookie
     */
    public function buildSurveyCookie($surveyCookie, $data, $flowResult)
    {
        $breadCrumb = '';
        if (isset($surveyCookie))
        {
            $first = explode('|', $surveyCookie);
            $second = explode('_', $first[2]);
            if (count($second) > 2)
            {
                list($remFlowID, $remQsID, $breadCrumb) = $second;
                if ($remFlowID == $data['flowID'] && ($remQsID == INT_NOT_SET || $remQsID == $flowResult->q_session_id))
                {
                    $crumbs = array_diff(explode(':', $breadCrumb), array(''));

                    if (count($crumbs) > 0)
                    {
                        //check if we used back button and pop off breadcrumbs from previous traversal
                        if (stringContains($crumbs[count($crumbs) - 1], '?'))
                        {
                            $last = array_pop($crumbs);
                            while(count($crumbs) > 0 && $last != $data['wpID'])
                            {
                                $last = array_pop($crumbs);
                            }
                        }
                        //the last crumb will equal the current page if resume fell back to the backup shortcut
                        if (count($crumbs) == 0 || $crumbs[count($crumbs) - 1] != $data['wpID'])
                        {
                            array_push($crumbs, $data['wpID']);
                        }
                        $breadCrumb = implode(':', $crumbs);
                    }
                    else
                    {
                        $breadCrumb = $data['wpID'];
                    }
                }
                else
                {
                    $breadCrumb = $data['wpID'];
                }
            }
        }
        else
        {
            $breadCrumb = $data['wpID'];
        }
        // Setting cookie to remember the session
        $cookieVal = sprintf('%s%s%d_%d_%s', COOKIE_SEP, COOKIE_SEP, $data['flowID'],
            $flowResult->q_session_id, $breadCrumb);
        return $cookieVal;
    }

    /**
     * Serves the next page of the survey/campaign
     *
     * @param array $data Array containing data pertinent to this function
     * @param array $surveyData Data related to the survey
     * @param array $flowResult The flow result from flow_rules
     */
    public function serveNextPage($data, $surveyData, $flowResult)
    {
        $surveyData['q_session_id'] = $flowResult->q_session_id;
        $surveyData['score'] = $flowResult->score;
        $surveyData['survey_id'] = 0;

        if ($data['flowType'] === FLOW_SURVEY_TYPE)
        {
            $cookieVal = $this->buildSurveyCookie($_COOKIE[$data['surveyCookieName']], $data, $flowResult);
            $expTime = get_survey_cookie_exp_time();
            if ($expTime > 0)
                setCPCookie($data['surveyCookieName'], $cookieVal, time() + $expTime);
        }

        $source = $_REQUEST['_source'];
        $sourceID = $_REQUEST['_source_id'];

        // NOTE: we send "false" as the cookie because we don't want the function to try
        // and set the contact_id based on the cookie. The tracking string holds the correct contact.
        $options = array('trackString' => $flowResult->tracking_string, 'useCookie' => false, 'source' => $source, 'sourceID' => $sourceID,
                         'mailingIDs' => '', 'preview' => false, 'forceTracking' => true, 'stats' => false,
                         'maCookie' => '', 'prefillVal' => '', 'formSubmit' => true, 'type' => '',
                         'rememberCookie' => '', 'newID' => $flowResult->src_id, 'newSource' => $flowResult->src_tbl, 'authParameter' => '',
                         'accountID' => $surveyData['q_sessions_acct_id'], 'previewMobile' => $surveyData['preview_mobile']);

        $formText = $this->getDocument(false, $surveyData, $data['questionData'], $flowResult->shortcut, $options);

        $this->insertJavascriptAndEchoDocument($formText);
    }

    /**
     * Submits a document
     */
    public function submitDocument()
    {
        $formToken = $this->CI->input->post('f_tok');
        $pShortcut = $this->CI->input->post('p_shortcut');
        $pPrevRequest = isset($_POST['prev_btn']);
        $pNextPage = $this->CI->input->post('p_next_id');
        $pt = $this->CI->input->post('p_t');
        include_once DOCROOT . '/ma/util.phph';
        define('MAX_CF', 100000 - 1);

        $pClearCookie = $this->CI->input->post('p_clear_cookie');
        if ($pClearCookie === 1)
            destroyCookie('rnw_ma_login', '');

        $submitData = $this->initializeSubmitData();
        $submitData['cID'] = ($this->CI->session && isLoggedIn()) ? $this->CI->session->getProfileData('c_id') : 0;

        //Check for shortcut param.  This will be available whenever a submit
        //is from a Serve Web Page Action.
        if ($pShortcut)
            Sql::getSwpData($pShortcut, $submitData);

        if ($pt)
            $this->getTrackData($pt, $submitData);

        if(!@$submitData["isProofOrPreview"] && (!$formToken || !\RightNow\Utils\Framework::isValidSecurityToken($formToken, 0, false))) {
            $title = getMessage(FORM_SUBMISSION_FAILED_MSG);
            $msg = getMessage(FORM_SUBMISSION_TOKEN_MATCH_EXP_LBL);
            $this->printErrorMessage($msg, $title, '');
            exit;
        }
        if ($submitData['flowID'] > 0)
        {
            if($submitData["surveyID"] === 0 && $submitData["campaignID"] === 0) {
                $msg = getMessage(ERR_SUBMITTING_FORM_DUE_INV_INPUT_LBL);
                $this->printErrorMessage($msg, '', '');
                exit;
            }
            // If we have a flow_id, then check to make sure it's active.
            if (Sql::isActiveFlow($submitData) === false)
            {
                $flowResult = ma_get_flow_default_url($submitData['flowID'], $submitData['flowType'], true);
                if(isset($flowResult->redirect_url) && ($flowResult->redirect_url != ''))
                {
                    header("Location: {$flowResult->redirect_url}");
                }
                exit;
            }

            if ($submitData['flowType'] == FLOW_CAMPAIGN_TYPE)
            {
                $transSource = array('lvl_id1' => SRC1_FLOW, 'lvl_id2' => SRC2_FLOW_CAMPAIGN);
            }
            else if ($submitData['flowType'] == FLOW_SURVEY_TYPE)
            {
                $transSource = array('lvl_id1' => SRC1_FLOW, 'lvl_id2' => SRC2_FLOW_SURVEY);
            }
        }
        else
        {
            if(!Sql::isValidDocId($submitData["docID"])) {
                $msg = getMessage(ERR_SUBMITTING_FORM_DUE_INV_INPUT_LBL);
                $this->printErrorMessage($msg, '', '');
                exit;
            }
            $transSource = array('lvl_id1' => SRC1_FLOW, 'lvl_id2' => SRC2_FLOW_MAILING_SUBMIT);
        }

        // Create an array that tracks all of the opt_in fields that are
        // checkboxes.  We'll then reconcile them at the end so we can
        // explicitly set opt_in fields to '0' if they don't show up
        // in the POST params.
        $surveyFields = $optInFields = $ssvFlds = array();
        // Always run run server-side validation
        $this->parseSubmitFields($_REQUEST, $surveyFields, $optInFields, $ssvFlds);
        $this->cleanSurveyResponses($_REQUEST, $surveyFields);
        if ($pPrevRequest !== true && !$this->validateSubmitFields($submitData, $ssvFlds, $surveyFields))
            exit;
        $surveyData = array();
        $this->parseSubmitParams($_REQUEST, $submitData, $surveyData, $optInFields);

        // the check for a question_session tells us if this is a submission
        // of the first page, or a subsequent page. we only want to check expiration after the first page
        if (!isset($surveyData['q_session_id']) && $submitData['surveyID'] > 0 && $this->checkSurveyExpiration(survey_get(array('id' => $submitData['surveyID'])), null))
            exit;

        if ($sentTimestamp = $this->CI->session->getSessionData('surveySentTimestamp'))
            $surveyData['sent_timestamp'] = $sentTimestamp;

        // check whether contact has previously submitted same survey
        if ($this->surveyAlreadySubmitted($submitData, $surveyData))
        {
            $title = getMessage(SURVEY_PREVIOUSLY_SUBMITTED_LBL);
            $msg = getMessage(RESPONSE_INPUT_APPRECIATED_MSG);
            $this->printErrorMessage($msg, $title, $submitData['surveyID']);
            exit;
        }

        // Add the tracking flags
        $surveyData['flags'] = isset($submitData['flags']) ? intval($submitData['flags']) : 0;

        // Add FWP ID
        $surveyData['flow_web_page_id'] = $submitData['wpID'];

        $typeData = $submitData['typeData'];
        $questionData = $submitData['questionData'];
        // Want to preserve the order for reporting purposes
        $questionOrder = $questionData;
        // We *must* sort the question_data since rules is expecting it sorted!
        uksort($questionData, array($this, 'matrixRowFilterSort'));
        uksort($typeData, array($this, 'matrixRowFilterSort'));

        $surveyData['question_count'] = intval($submitData['questionCount']);

        if ($this->CI->input->post('p_override_mobile'))
        {
            $surveyData['override_mobile'] = 1;
            destroyCookie('rnw_survey_login', '');
            destroyCookie('rnw_survey_preview', '');
        }

        //Back or Forward Button
        if ($pPrevRequest || ($pNextPage && !empty($pNextPage)))
        {
            $surveyData['doc_id'] = $submitData['docID'];
            $this->navigateInPage($submitData, $surveyData, $questionData, $questionOrder, $typeData);
            exit;
        }

        $contact = $submitData['cID'] > 0 ? Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0 ? Connect\Contact::fetch($submitData['cID']) : \RightNow\Connect\v1_3\Contact::fetch($submitData['cID']) : null;

        $this->matchContactIfChanged($submitData, $contact);

        if(!$this->isDocSubmissionValid($submitData, $surveyData, $questionData)) {
            $msg = getMessage(ERR_SUBMITTING_FORM_DUE_INV_INPUT_LBL);
            $this->printErrorMessage($msg, '', '');
            exit;
        }

        $flowResult = $this->runFlowRules($submitData, $surveyData, $questionData, $questionOrder, $typeData, $transSource);

        if ($this->flowResultHasError($submitData, $flowResult, $pShortcut, $contact))
            exit;

        $this->addSubmitTransaction($submitData, $flowResult);

        $this->setMaCookie($submitData, $flowResult);

        // Check if our next step is a serve web page.  If so, grab that web
        // page and serve it up directly.  Otherwise process the final
        // action (usually a redirect).
        $this->serveNextPageOrRedirect($flowResult, $submitData, $surveyData);
    }

    /**
     * Method for validating form submission fields against actual survey/campaign/document fields
     *
     * @param array $submitData Submit data
     * @param array $surveyData Survey data
     * @param array $questionData Question data
     *
     * @return bool true if form submission is valid
     */
    private function isDocSubmissionValid($submitData, $surveyData, $questionData){
        $formatID = array_key_exists('formatID', $submitData) ? $submitData['formatID'] : 0;
        $document = serve_doc_get($submitData['docID'], $submitData['cID'], 0, GENERIC_TRACK_TYPE_MA, $submitData['flowID'], $formatID, '', '', $submitData['flags'], -1, '', '', $surveyData, $questionData, -1, '', 0, 0, false);
        $validFormFields = array("abuse_challenge_response", "abuse_challenge_opaque", "recaptcha_challenge_field", "recaptcha_response_field", "f_tok", "p_next_id", "g-recaptcha-response");
        if(preg_match_all("/name=\"(.*?)(\[\])*?\"/", $document, $matches)) {
            //php is expected to replace spaces, open square brackets and dots in form field names by underscores
            $validFormFields = array_merge($validFormFields, preg_replace('/\s|\[|\./', '_', $matches[1]));
        }
        $submissionFields = isset($_POST) ? array_keys($_POST) : array();
        foreach($submissionFields as $submissionField) {
            //even if php fails to replace spaces/dots/opening square brackets, we should replace it before performing the match
            $sField = preg_replace('/\s|\[|\./', '_', $submissionField);
            if(!in_array($sField, $validFormFields)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Function to reduce the complexity of submitDocument
     *
     * @param object $flowResult Flow result
     * @param array $submitData Submit data
     * @param array $surveyData Survey data
     */
    private function serveNextPageOrRedirect($flowResult, $submitData, $surveyData) {
        if(isset($flowResult) && isset($flowResult->shortcut))
        {
            $this->serveNextPage($submitData, $surveyData, $flowResult);
        }
        else if (isset($flowResult) && isset($flowResult->redirect_url))
        {
            destroyCookie('rnw_survey_login', '');
            // NOTE: the flow_rules code sets redirect_url from the default config setting,
            // so if it's empty, then we really have no place to go.
            header("Location: {$flowResult->redirect_url}");
        }
    }

    /**
     * Function to reduce the complexity of submitDocument
     *
     * @return array a sumbit data array with everything set to 0
     */
    private function initializeSubmitData() {
        $submitData = array();
        foreach (array('campaignID', 'surveyID', 'stateID', 'docID', 'wpID', 'flowStatus', 'flowID', 'flowType', 'setCookie') as $key)
        {
            $submitData[$key] = 0;
        }
        return $submitData;
    }

    /**
     * Validates all fields (ssv stands for Server Side Validation)
     *
     * @param array $fields The fields that are being validated
     *
     * @return string the error message, null if no error
     */
    private function ssvCheckFields($fields)
    {
        $msg = '';
        for ($i = 0, $sz = count($fields); $i < $sz; $i++)
        {
            $val = $_POST[$fields[$i][SSV_NAME]];

            switch ($fields[$i][SSV_TYPE])
            {
                case CDT_MENU:
                    $msg .= $this->validateMenuField($fields[$i], $val);
                    break;
                case CDT_BOOL:
                case CDT_OPT_IN:
                    $msg .= $this->validateBoolField($fields[$i], $val);
                    break;
                case CDT_INT:
                    $msg .= $this->validateIntField($fields[$i], $val);
                    break;
                case CDT_VARCHAR:
                case CDT_MEMO:
                    $msg .= $this->validateStringField($fields[$i], $val);
                    break;

                case CDT_DATETIME:
                case CDT_DATE:
                    $msg .= $this->validateDateField($fields[$i]);
                    break;

                default:
                    $msg .= sprintf("'%s' %s<br />", $fields[$i][SSV_LABEL], 'Invalid data type');
                    break;
            }
        }

        return($msg);
    }

    /**
     * Validates date fields
     *
     * @param array $field The field that is being validated
     *
     * @return string the error message, null if no error
     */
    private function validateDateField($field)
    {
        $msg = '';

        $dtLbl = array(getMessage(MONTH_LBL, 'COMMON'),
                        getMessage(DAY_LBL, 'COMMON'),
                        getMessage(YEAR_LBL, 'COMMON'),
                        getMessage(HOUR_LBL, 'COMMON'),
                        getMessage(MINUTE_LBL, 'COMMON'));

        $fld[0] = $this->CI->input->post($field[SSV_NAME].'_mon');
        $fld[1] = $this->CI->input->post($field[SSV_NAME].'_day');
        $fld[2] = $this->CI->input->post($field[SSV_NAME].'_yr');
        $fsz = 3;  //field_sz for CDT_DATE is 3

        if ($field[SSV_TYPE] == CDT_DATETIME)
        {
            $fld[3] = $this->CI->input->post($field[SSV_NAME].'_hr');
            $fld[4] = $this->CI->input->post($field[SSV_NAME].'_min');
            $fsz = 5;  //field_sz for CDT_DATETIME is 5
        }

        if (!($field[SSV_FLAGS] & V_FLAG_REQ))  // not required
        {
            for ($j = $numSet = 0; $j < $fsz; $j++)
                $numSet += ($fld[$j] == '') ? 0 : 1;

            if (($numSet > 0) && ($numSet != $fsz))
            {
                // not all parts of the date/datetime field are filled out
                $msg .= sprintf("'%s' %s<br />", $field[SSV_LABEL], getMessage(NOT_COMPLETE_SPECIFIED_MSG));
            }
            return $msg;
        }

        for ($j = 0; $j < $fsz; $j++)
            if (($fld[$j] == ''))
                $msg .= sprintf("'%s (%s)' %s<br />", $field[SSV_LABEL], $dtLbl[$j], getMessage(VALUE_REQD_MSG));

        return $msg;
    }

    /**
     * Validates string fields
     *
     * @param array $field The field that is being validated
     * @param string $val The value that is being validated
     *
     * @return string the error message, null if no error
     */
    private function validateStringField($field, $val)
    {
        $val = rtrim(ltrim($val));
        $msg = '';

        if ($field[SSV_MAXSZ] && ($field[SSV_MAXSZ] < utf8_char_len($val)))
        {
            $msg .= sprintf(getMessage(TEXT_EXCEEDS_SIZE_MSG), $field[SSV_LABEL], $field[SSV_MAXSZ], strlen($val) - $field[SSV_MAXSZ]);
            $msg .= '<br />';
        }

        if (($field[SSV_FLAGS] & V_FLAG_REQ) && !strlen($val))
        {
            $msg .= sprintf("'%s' %s<br />", $field[SSV_LABEL], getMessage(VALUE_REQD_MSG));
            return $msg;
        }

        // Valid Email syntax checking
        if (($field[SSV_FLAGS] & V_FLAG_EMAIL) && $val && !isValidEmailAddress($val))
            $msg .= sprintf("'%s' %s<br />", $field[SSV_LABEL], getMessage(EMAIL_IS_INVALID_MSG));

        if (($field[SSV_FLAGS] & V_FLAG_ASCII) && (strlen($val) > 0) && !(preg_match('/^[\x20-\x7e]+$/', $val)))
            $msg .= sprintf("'%s' %s<br />", $field[SSV_LABEL], getMessage(MUST_ONLY_CONTAIN_ASCII_MSG));

        // Validate custom field masks
        if(($field[SSV_MASK]) && ($field[SSV_TYPE] == CDT_VARCHAR) && ($val != ''))
            $msg .= $this->checkMaskPhp($field[SSV_MASK], $val, $field[SSV_LABEL]);

        return $msg;
    }

    /**
     * Validates int fields
     *
     * @param array $field The field that is being validated
     * @param int $val The value that is being validated
     *
     * @return string the error message, null if no error
     */
    private function validateIntField($field, $val)
    {
        $val = rtrim(ltrim($val));
        $msg = '';
        if ($val && !preg_match('/[-+]?\\d+/', $val))
            $msg .= sprintf("'%s' %s<br />", $field[SSV_LABEL], getMessage(NOT_INTEGER_MSG));
        //only perform this check if maxval was specified when the field was printed out
        if($field[SSV_MAXV])
        {
            if ($val && (intval($val) > $field[SSV_MAXV]))
                $msg .= sprintf("'%s': %s '%s'", $field[SSV_LABEL], getMessage(VAL_ENT_GT_LG_VAL_FLD_MSG), $field[SSV_MAXV]);
            if ($val && (intval($val) < $field[SSV_MINV]))
                $msg .= sprintf("'%s': %s '%s'", $field[SSV_LABEL], getMessage(VAL_ENT_LT_SM_VAL_FLD_MSG), $field[SSV_MINV]);
        }

        if (($field[SSV_FLAGS] & V_FLAG_REQ) && !strlen($val))
            $msg .= sprintf("'%s' %s<br />", $field[SSV_LABEL], getMessage(VALUE_REQD_MSG));

        return $msg;
    }

    /**
     * Validates bool fields
     *
     * @param array $field The field that is being validated
     * @param string $val The value that is being validated
     *
     * @return string the error message, null if no error
     */
    private function validateBoolField($field, $val)
    {
        $msg = '';
        if (($field[SSV_FLAGS] & V_FLAG_REQ) && !strlen($val))
            $msg .= sprintf("'%s' %s<br />", $field[SSV_LABEL], getMessage(VALUE_REQD_MSG));
        return $msg;
    }

    /**
     * Validates menu fields
     *
     * @param array $field The field that is being validated
     * @param string $val The value that is being validated
     *
     * @return string the error message, null if no error
     */
    private function validateMenuField($field, $val)
    {
        $msg = '';
        if (($field[SSV_FLAGS] & V_FLAG_REQ) && !$val)
            $msg .= sprintf("'%s' %s<br />", $field[SSV_LABEL], getMessage(VALUE_REQD_MSG));
        return $msg;
    }

    /**
     * This function is a combination of the put_mask() and check_mask() functions located in enduser.js. It serves as PHP validation for custom fields.
     *
     * @param string $mask The fields mask i.e. F(M#M#M#F)
     * @param string $value The value entered by the enduser
     * @param string $fldlabel The label of the field as seen by the enduser
     *
     * @return string the error message or NULL if there is no error
     */
    private function checkMaskPhp($mask, $value, $fldlabel)
    {
        $mtmp = $ftmp = $dtmp = $message = '';
        $sl = strlen($mask);

        for($i = 0; $i < $sl; $i++)
        {
            $ftmp .= $mask[$i];

            if ($mask[$i] == 'F')
                $dtmp .= $mask[$i + 1];
            else
                $dtmp .= ($mask[$i + 1] == '#') ? '#' : '@';

            $mtmp .= $mask[++$i];
        }

        $ln = strlen($mtmp);
        $val = $code = $echar = $fchar = '';

        if (strlen($mtmp) < strlen($value))
        {
            $message = sprintf("'%s': %s", $fldlabel, getMessage(FLD_CONT_TOO_MANY_CHARS_MSG));
        }
        else
        {
            for ($i = 0; $i < $ln; $i++)
            {
                $val = $value[$i];
                $code = ord($value[$i]); // get ascii value of character
                $echar = $mtmp[$i];
                $fchar = $ftmp[$i];

                if ($fchar == 'F' && $val != $echar)
                {
                    if ($val == ' ')
                        $message = sprintf("'%s': %s", $fldlabel, getMessage(MUST_CONT_VALID_FMT_CHAR_MSG));
                    else
                        $message = sprintf("'%s': '%s' %s", $fldlabel, $val, getMessage(NOT_VALID_FMT_CHAR_MSG));
                    break;
                }
                else if (($echar == '#') && (!(($code >= 48) && ($code <= 57))))
                {
                    if ($val == '')
                        $message = sprintf("'%s': %s", $fldlabel, getMessage(MUST_CONT_VALID_NUM_MSG));
                    else
                        $message = sprintf("'%s': '%s' %s", $fldlabel, $val, getMessage(IS_NOT_A_VALID_NUM_MSG));
                    break;
                }
                else if (($echar == 'A') && (!((($code >= 48) && ($code <= 57)) || (($code >= 65) && ($code <= 90)) || (($code >= 97) && ($code <= 122)))))
                {
                    if ($val == '')
                        $message = sprintf("'%s': %s", $fldlabel, getMessage(MUST_CONT_VALID_ALPHA_NUMERIC_MSG));
                    else
                        $message = sprintf("'%s': '%s' %s", $fldlabel, $val, getMessage(IS_NOT_A_VALID_ALPHA_NUMERIC_MSG));
                    break;
                }
                else if (($echar == 'L') && (!((($code >= 65) && ($code <= 90)) || (($code >= 97) && ($code <= 122)))))
                {
                    if ($val == '')
                        $message = sprintf("'%s': %s", $fldlabel, getMessage(MUST_CONT_VALID_LETTER_MSG));
                    else
                        $message = sprintf("'%s': '%s' %s", $fldlabel, $val, getMessage(IS_NOT_VALID_LETTER_MSG));
                    break;
                }
                else if (($echar == 'C') && (!((($code >= 32) && ($code <= 126)) || (($code >= 128) && ($code <= 255)))))
                {
                    if ($val == '')
                        $message = sprintf("'%s': %s", $fldlabel, getMessage(MUST_CONT_VALID_CHAR_MSG));
                    else
                        $message = sprintf("'%s': '%s' %s", $fldlabel, $val, getMessage(IS_NOT_VALID_CHAR_MSG));
                    break;
                }
            }
        }
        if($message)
            $message .= sprintf(' %s %s <br />', getMessage(CORR_FMT_IS_MSG), $dtmp);
        return $message;
    }

    /**
     * This strips quotes from a string (double and single)
     *
     * @param string $string Something that might have quotes in (a string).
     *
     * @return the string without quotes.
     */
    private function removeQuotes($string)
    {
        $s = str_replace("'", '', $string);
        $s = str_replace('"', '', $s);
        return $s;
    }

    /**
     * This function removes 'Other' text if the other option isn't selected.
     *
     * @param array &$responses An array of submitted values
     * @param array $fields An array of survey question definitions on the current page
     */
    public function cleanSurveyResponses(array &$responses, array $fields)
    {
        foreach($fields as $field)
        {
            $otherMatch = '/^other_' . $this->removeQuotes($field[SSV_SURVEY_QUESTION_ID]) . '_/';
            $prevVal = '0';
            foreach ($responses as $key => $value)
            {
                if ((preg_match($otherMatch, $key)))
                {
                    list($qVar, $questionID, $choiceID) = explode('_', $key, 3);

                    //Clear out "other" text if other option isn't selected
                    if (strlen($value) > 0 && (intval($prevVal) <= 0 || (intval($prevVal) > 0 && intval($choiceID) > 0 && (strcmp($prevVal, $choiceID) !== 0))))
                    {
                        unset($responses[$key]);
                    }
                }
                $prevVal = $value;
            }
        }
    }

    /**
     * This is the server-side validation function for survey questions.
     *
     * @param array $fields An array of survey question definitions on the current page
     *
     * @return an array of error objects (error object = question_id + error text)
     */
    public function ssvCheckSurveyFields($fields)
    {
        $errors = array();
        $matrixResponses = array();

        for ($i = 0, $sz = count($fields); $i < $sz; $i++)
        {
            if (($fields[$i][SSV_SURVEY_MIN] > 0) || ($fields[$i][SSV_SURVEY_MAX] > 0))
            {
                $minValue = $fields[$i][SSV_SURVEY_MIN];
                $maxValue = $fields[$i][SSV_SURVEY_MAX];

                if($fields[$i][SSV_SURVEY_QUESTION_TYPE] == SURVEY_QUESTION_TYPE_TEXT)
                {
                    // Check the size of the response
                    $val = $_REQUEST['q_' . $this->removeQuotes($fields[$i][SSV_SURVEY_QUESTION_ID])];
                    $val = str_replace("\r\n", "\n", $val);
                    $valSize = utf8_char_len($val);
                    if ($valSize < 0)
                    {
                        $val = utf8_cleanse($val);
                        $valSize = utf8_char_len($val);
                    }

                    if($minValue > 0 && $valSize < $minValue)
                    {
                        $errors[] = array('question_text'  => $fields[$i][SSV_SURVEY_QUESTION_TEXT],
                                          'error_text'   => getMessage(TEXT_RESPONSE_NOT_LONG_ENOUGH_MSG));
                    }

                    if($maxValue > 0 && $valSize > $maxValue)
                    {
                        $errors[] = array('question_text'  => $fields[$i][SSV_SURVEY_QUESTION_TEXT],
                                          'error_text'   => getMessage(FLD_CONT_TOO_MANY_CHARS_MSG));
                    }
                }
                else
                {
                    // Check if we have a value for this question
                    $exactMatch = '/^q_' . $this->removeQuotes($fields[$i][SSV_SURVEY_QUESTION_ID]) . '$/';
                    $match = '/^q_' . $this->removeQuotes($fields[$i][SSV_SURVEY_QUESTION_ID]) . '_/';
                    $otherMatch = '/^other_' . $this->removeQuotes($fields[$i][SSV_SURVEY_QUESTION_ID]) . '_/';
                    $count = 0;
                    $prevVal = '0';
                    foreach ($_REQUEST as $key => $value)
                    {
                        if ((preg_match($exactMatch, $key)) || (preg_match($match, $key)))
                        {
                            // Need to look for an array and also a blank value since our '--' entries don't have a value.
                            if(is_array($value))
                                $count += count($value);
                            else if ($value)
                                $count++;
                        }
                        if ((preg_match($otherMatch, $key)))
                        {
                            list($qVar, $questionID, $choiceID) = explode('_', $key, 3);

                            if (strlen($value) > 0 && (intval($prevVal) <= 0 || (intval($prevVal) > 0 && intval($choiceID) > 0 && (strcmp($prevVal, $choiceID) != 0))))
                            {
                                $errors[] = array('question_text' => $fields[$i][SSV_SURVEY_QUESTION_TEXT],
                                                  'error_text' => getMessage(TEXT_ADDTL_INPUT_FIELD_SELECTED_MSG));
                            }
                        }
                        $prevVal = $value;
                    }

                    if($minValue > 0 && $count < $minValue)
                    {
                        $errors[] = array('question_text' => $fields[$i][SSV_SURVEY_QUESTION_TEXT],
                                          'error_text' => getMessage(NEED_TO_SELECT_MORE_OPTIONS_LBL));
                    }

                    if($maxValue > 0 && $count > $maxValue)
                    {
                        $errors[] = array('question_text' => $fields[$i][SSV_SURVEY_QUESTION_TEXT],
                                          'error_text' => getMessage(NEED_TO_SELECT_FEWER_OPTIONS_LBL));
                    }
                }
            }

            if ($fields[$i][SSV_SURVEY_QUESTION_TYPE] == SURVEY_QUESTION_TYPE_MATRIX)
            {
                $val = $_REQUEST['q_' . $this->removeQuotes($fields[$i][SSV_SURVEY_QUESTION_ID])];

                // This will enforce rankable matrix questions
                if ($val && $fields[$i][SSV_SURVEY_MATRIX_FORCE_RANKING] == 'true')
                {
                    if (!array_key_exists($val, $matrixResponses)) 
                    {
                        $matrixResponses[$val] = array();
                    }
                    else if (array_key_exists('q_id', $matrixResponses[$val]) && 
                        $matrixResponses[$val]['q_id'] != $fields[$i][SSV_SURVEY_QUESTION_ID])
                    {
                        $errors[] = array('choice_text' => $matrixResponses[$val]['text'] . " " . getMessage(AND_LBL) . " " . $fields[$i][SSV_SURVEY_QUESTION_TEXT],
                                          'error_text' => sprintf(getMessage(QUEST_PCT_S_FOLLOWING_CHOICES_LBL), $fields[$i][SSV_SURVEY_MATRIX_TITLE]));
                    }
                    $matrixResponses[$val]['q_id'] = $fields[$i][SSV_SURVEY_QUESTION_ID];
                    $matrixResponses[$val]['text'] = $fields[$i][SSV_SURVEY_QUESTION_TEXT];
                }
            }
        }

        return($errors);
    }

    /**
     * Creates a transaction for a service (message templates) view in browser
     *
     * @param TrackingObject $trackingDataObject The decoded tracking object
     * @param int $transType The transaction type (view, click, etc)
     * @param string $sessionID The session ID
     */
    private function serviceViewInBrowserCreateTrans($trackingDataObject, $transType, $sessionID)
    {
        if (!$this->isProofOrPreview($trackingDataObject) && is_int($trackingDataObject->c_id) && $trackingDataObject->c_id > 0)
        {
            $entry = array('c_id' => $trackingDataObject->c_id, 'trans_type' => $transType, 'created' => time(),
                            'doc_id' => $trackingDataObject->doc_id, 'email_type' => $trackingDataObject->email_type);

            if (is_int($trackingDataObject->thread_id) && $trackingDataObject->thread_id > 0)
                $entry['thread_id'] = $trackingDataObject->thread_id;

            if (!empty($sessionID))
                $entry['cs_session_id'] = (string) $sessionID;

            dqa_insert(DQA_MESSAGE_TRANS, json_encode($entry));
        }
    }

    /**
     * Creates a transaction for a marketing (mailings/survey invites) view in browser
     *
     * @param TrackingObject $track Decoded tracking object
     * @param int $transType The transaction type (view, click, etc)
     * @param date $created The created date
     */
    private function marketingViewInBrowserCreateTrans($track, $transType, $created)
    {
        $pairdata = array('type' => $transType,
                          'doc_id' => $track->doc_id,
                          'media' => $track->media);

        if ($track->format_id > 0)
            $pairdata['format_id'] = $track->format_id;

        if ($track->c_id > 0)
        {
            if($track->flags & GENERIC_TRACK_FLAG_FRIEND)
                $pairdata['ref_c_id'] = $track->c_id;
            else
                $pairdata['c_id'] = $track->c_id;
        }

        if (!$this->isProofOrPreview($track))
        {
            $entry = array('ref_c_id' => $pairdata['ref_c_id'], 'c_id' => $pairdata['c_id'],
                            'format_id' => $pairdata['format_id'], 'media' => $pairdata['media'],
                            'type' => $pairdata['type'], 'created' => $created,
                            'doc_id' => $pairdata['doc_id'], 'table' => 'ma_trans');
            $json = json_encode($entry);
            dqa_insert(DQA_DOCUMENT_STATS, $json);
        }
    }

    /**
     * Return the marketing cookie expiration delta time that is to be used when setting the marketing coookie (MA_COOKIE). If no marketing cookie is to be set -1 is returned.
     *
     * @return the marketing cookie expiration delta time
     */
    private function getMaCookieExpTime()
    {
        if (($expTime = getConfig(RNM_COOKIE_EXP, 'MA')) == -1)
            return -1;

        // If RNM_COOKIE_EXP is set to 0, then it should be valid
        // indefinitely. We define eternity as 10 years. Also, for some reason,
        // we do not allow it to be set less than 600 seconds (10 minutes)...
        // (it used to be 550 seconds - now rounded up to 10 minutes)
        $expTime = $expTime ? max($expTime * 60, 600) : 60 * 60 * 24 * 365 * 10;

        return ($expTime);
    }

    /**
     * Determines whether we should render the survey in mobile mode
     *
     * @param int $surveyID The survey ID
     * @param bool $isMobileUserAgent Set to true if the user is on a mobile device
     *
     * @return bool true if we should render the survey in mobile mode
     */
    public function renderInMobileMode($surveyID, $isMobileUserAgent)
    {
        if ($surveyID > 0 && $isMobileUserAgent)
        {
            $survey = survey_get(array('id' => $surveyID));
            if ($survey['attr'] & SURVEY_ATTR_MOBILE)
                return true;
        }
        return false;
    }

    /**
     * Determines if the user is on mobile
     *
     * @return bool true if the user is on a mobile device
     */
    public function isMobileUserAgent()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $userAgentData = $this->callUserAgentService($userAgent);

        if (!$userAgentData)
            return false;

        if (strlen($userAgentData->deviceType) < 1)
        {
            //we need a session to call Action Capture, we won't always have one in a preview situation
            if ($this->CI && $this->CI->session)
                ActionCapture::instrument('documents', 'userAgentDetectionIssue', \RightNow\InstrumentationLevel::ERROR);

            if (stristr($userAgent, 'android') || stristr($userAgent, 'ios') || stristr($userAgent, 'mobile'))
                return true;
        }
        else if ($userAgentData->deviceType === 'touch')
        {
            return true;
        }
        return false;
    }

    /**
     * Builds the prefill value string
     * Our customers can prefill for fields by giving us the values they want them set to via URL parameters
     *
     * @param array $params An array of url parameters
     *
     * @return string The prefill value
     */
    public function getPrefillString(array $params)
    {
        $prefillVal = '';
        foreach ($params as $key => $value)
        {
            if (beginsWith($key, 'wf_'))
            {
                $fieldIntVal = substr($key, 5, 6);
                $prefillVal .= "$fieldIntVal=$value|";
            }
        }
        return $prefillVal;
    }

    /**
     * Calls the user agent service to give us information about the user agent
     *
     * @param string $userAgent The raw user agent
     *
     * @return object the response from UAS
     */
    private function callUserAgentService($userAgent)
    {
        if(!extension_loaded('curl') && !@load_curl())
            return null;

        $timeout = self::UA_SERVICE_TIMEOUT_MS;
        $urlSafeUa = rawurlencode($userAgent);
        $host = getConfig(USER_AGENT_SERVICE_HOST);
        $port = getConfig(USER_AGENT_SERVICE_PORT);
        $protocol = $port === 443 ? 'https' : 'http';
        $url = "$protocol://$host:$port/api/1/userAgent/$urlSafeUa";
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT_MS => $timeout,
            CURLOPT_CONNECTTIMEOUT_MS => $timeout,
        );
        curl_setopt_array($ch, $options);
        $response = @curl_exec($ch);
        return json_decode($response);
    }

    /**
     * Determines if we should render in mobile mode
     *
     * @param int $surveyID The survey ID
     *
     * @return bool true if the user is on a mobile device
     */
    public function isMobile($surveyID)
    {
        return $this->renderInMobileMode($surveyID, $this->isMobileUserAgent());
    }

    /**
     * Checks to make sure that a contact recognized by cookie or the MA tracking string exists in the database
     *
     * @param int $contactID Contact ID to check
     *
     * @return 0 if that id does not exist and the c_id if it does
     */
    public function verifyContactID($contactID)
    {
        if ($contactID > 0)
        {
            if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0)
                $res = Connect\ROQL::query("SELECT C.ID FROM Contact C WHERE C.ID = $contactID");
            else
                $res = \RightNow\Connect\v1_3\ROQL::query("SELECT C.ID FROM Contact C WHERE C.ID = $contactID");
            $row = $res->next();
            $contactRow = $row->next();
            $cID = $contactRow['ID'];
            if (intval($cID) > 0)
                return $cID;
        }
        return 0;
    }

    /**
     * Prints an error message in a consistent format
     *
     * @param string $message The error message - no markup
     * @param string $title The title you want on the page
     * @param int $surveyID The survey ID
     */
    private function printErrorMessage($message, $title, $surveyID)
    {
        $metaTag = $this->isMobile($surveyID) ? '<meta name="viewport" content="width=device-width, initial-scale=1">' : '';
        echo <<<ERROR
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>$title</title>
$metaTag
</head>
<body>
ERROR;
        include get_cfg_var('doc_root') . '/ma/cci/top.phph';
        echo "<div style=\"background-color: #F5F5F5\"><h2>$title</h2><p>$message</p></div>";
        include get_cfg_var('doc_root') . '/ma/cci/bottom.phph';
        echo '</body></html>';
    }

    /**
     * Determines if we are in proof or preview mode
     *
     * @param TrackingObject $track The tracking object
     *
     * @return bool true If we are in proof/preview mode false otherwise
     */
    private function isProofOrPreview($track)
    {
        if ($track && $track->flags & (GENERIC_TRACK_FLAG_PREVIEW | GENERIC_TRACK_FLAG_PROOF))
            return true;

        return false;
    }

    /**
     * Determines if we should clear out our cookie
     *
     * @param TrackingObject $track The tracking object
     * @param string $authParameter A secret auth parameter to prevent people from flipping through survey IDs and gaining access to something they shouldn't
     * @param array $surveyData Array of values relevant to surveys
     *
     * @return bool true if we should clear the cookie false otherwise
     */
    private function shouldClearCookie($track, $authParameter, $surveyData)
    {
        // We want to clear the cookie when first loading a preview but we don't want to when going back to page 1 while previewing
        if ($track !== null && $track->flags & GENERIC_TRACK_FLAG_PREVIEW && strlen($authParameter) > 0 && $surveyData['surveyNavigation'] !== 1)
            return true;

        return false;
    }
}
