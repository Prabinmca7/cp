<?php
namespace RightNow\Controllers;

if (!IS_HOSTED){
    require_once CORE_FILES . 'compatibility/Mappings/Functions.php';
}

/**
 * Methods for handling survey/campaign/document submission and display
 */
final class Documents extends Base
{
    const TYPE_PARAMETER = 8;

    function __construct()
    {
        parent::__construct();
        parent::_setClickstreamMapping(array(
            "detail" => "document_detail",
            "verifyContact" => "document_verify_contact",
            "view" => "document_view",
            "submit" => "document_submit"

        ));
        parent::_setMethodsExemptFromContactLoginRequired(array(
            "detail",
            "verifyContact",
            "view",
            "submit"
        ));  
    }

    /**
     * The starting endpoint for all campaigns and surveys
     *
     * @return void
     */
    function detail(){
        redirectToHttpsIfNecessary();
        $params = $this->uri->uri_to_assoc(3);
        $trackString = '';
        $type = '';
        $previewMobile = '';
        $clearCookie = false;
        foreach ($params as $key => $value) {
            switch ($key) {
                case MA_QS_ENCODED_PARM:
                    $trackString = $value;
                    break;
                case self::TYPE_PARAMETER:
                    $type = $value;
                    break;
                case 'p_pre_mobile':
                    $previewMobile = $value;
                    break;
                case 'p_clear_cookie':
                    $clearCookie = ($value == 1);
                    break;
            }
        }
        if ($clearCookie) {
            destroyCookie('rnw_ma_login');
        }
        else {
            $maCookie = MA_COOKIE;
            $rememberCookie = SURVEY_COOKIE;
            if (array_key_exists('cp_profile', $_COOKIE)) {
                $cpCookie = $_COOKIE['cp_profile'];
            }
        }
        $prefillVal = $this->model('Document')->getPrefillString($params);
        $surveyData = $this->getSurveyData($params);
        if ($type === 'proxy') // we are in proxy mode
        {
            $contactID  = $incidentID = $opportunityID = $accountID = 0;
            foreach ($params as $key => $value) {
                switch ($key) {
                    case 'p_c_id':
                        $contactID = $value;
                        break;
                    case 'p_i_id':
                        $incidentID = $value;
                        break;
                    case 'p_op_id':
                        $opportunityID = $value;
                        break;
                    case 'p_acct_id':
                        $accountID = $value;
                        break;
                }
            }
            $account = $this->_getAgentAccount();
            if ($account === false) {
                echo getMessage(ACCESS_DENIED_LBL);
                exit;
            }
            if ($incidentID > 0)
            {
                if ($this->_doesAccountHavePermission(ACCESS_INCIDENT_VIEW, 'css'))
                {
                    $source = VTBL_INCIDENTS;
                    $sourceID = $incidentID;
                }
                else
                {
                    echo getMessage(ACCESS_DENIED_LBL);
                    exit;
                }
            }
            else if ($opportunityID > 0)
            {
                if ($this->_doesAccountHavePermission(ACCESS_SA_OPP_VIEW, 'sa'))
                {
                    $source = VTBL_OPPORTUNITIES;
                    $sourceID = $opportunityID;
                }
                else
                {
                    echo getMessage(ACCESS_DENIED_LBL);
                    exit;
                }
            }
            if ($contactID > 0)
            {
                $trackString = generic_track_encode($contactID);
            }
            else if (!array_key_exists('p_force_anon', $params) || 
                $params['p_force_anon'] !== '1') //Need to print error message and exit.
            {
                $ipMsg = getMessage(CONT_ASSOC_ASSOC_CONT_PERFORM_SAVE_MSG);
                echo $ipMsg;
                exit;
            }
        }
        else
        {
            $source = $sourceID = $accountID = 0;
            foreach ($params as $key => $value) {
                switch ($key) {
                    case MA_QS_SOURCE_PARM:
                        $source = intval(urldecode($value));
                        break;
                    case MA_QS_SOURCE_ID_PARM:
                        $sourceID = intval(urldecode($value));
                        break;
                    case MA_QS_ACCOUNT_ID_BASE64:
                        $accountID = base64_decode(urldecode($value));
                        break;
                }
            }
        }
        if ($source > 0 && $sourceID <= 0)
        {
            echo getMessage(ACCESS_DENIED_LBL);
            exit;
        }
        $docId = $formShortcut = '';
        $options = array(
                         'accountID' => $accountID, 
                         'forceTracking' => false,
                         'formSubmit' => null,
                         'maCookie' => $maCookie, 
                         'mailingIDs' => '',
                         'newID' => 0, 
                         'newSource' => 0,
                         'prefillVal' => $prefillVal, 
                         'preview' => false,
                         'previewMobile' => $previewMobile, 
                         'rememberCookie' => $rememberCookie, 
                         'source' => $source, 
                         'sourceID' => $sourceID,
                         'trackString' => $trackString, 
                         'type' => $type, 
                         'useCookie' => true, 
                         );
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'p_mailing_id':
                    $options['mailingIDs'] = $value;
                    break;
                case 'p_preview':
                    $options['preview'] = $value;
                    break;
                case 'p_stats':
                    $options['stats'] = $value;
                    break;
                case MA_QS_SURVEY_AUTH_PARM:
                    $options['authParameter'] = $value;
                    break;
                case MA_QS_SOURCE_PARM:
                    $options['relatedTbl'] = $value;
                    break;
                case MA_QS_SOURCE_ID_PARM:
                    $options['relatedID'] = $value;
                    break;
                case MA_QS_ITEM2_PARM:
                    $docId = $value;
                    break;
                case MA_QS_ITEM_PARM:
                    $formShortcut = $value;
                    break;
            }
        }
        $documentHtml = $this->model('Document')->getDocument($docId, $surveyData, null, $formShortcut, $options);
        if ($documentHtml !== null)
            $this->model('Document')->insertJavascriptAndEchoDocument($documentHtml);
    }

    /**
     * A function that was requested by the CX site team that someone was dumb enough to actually put into the product for them
     * You can verify the contact is logged in via either CP cookie or MA cookie
     *
     * @return void
     */
    function verifyContact()
    {
        $contactID = 0;
        if($this->session && isLoggedIn())
        {
            $contactID = $this->session->getProfileData('c_id');
        }
        else if (MA_COOKIE)
        {
            list(, , , $maCookieTrackString) = explode('|', MA_COOKIE);

            if ($maCookieTrackString)
                $cookieTrack = generic_track_decode($maCookieTrackString);
            if ($cookieTrack && $this->model('Document')->verifyContactID($cookieTrack->c_id) > 0)
                $contactID = $cookieTrack->c_id;
        }

        writeContentWithLengthAndExit("var existingRightNowContactID = $contactID;", 'text/JavaScript');
    }

    /**
     * The endpoint for 'view this email in your browser' links
     *
     * @return void
     */
    function view()
    {
        $params = $this->uri->uri_to_assoc(3);

        $session = $params['session'];
        $sessionVerified = $params['sverified'];

        if ($session)
        {
            $sessionID = end(explode('/', base64_decode(urldecode($session))));
        }
        else if ($sessionVerified && $this->session)
        {
            $sessionID = $this->session->getSessionData('sessionID');
        }

        $trackString = $params[MA_QS_ENCODED_PARM];
        $cloud = $params[MA_QS_CLOUD_PARM];
        $surveyData = $this->getSurveyData($params);
        $this->model('Document')->viewDocument($trackString, $cloud, $surveyData, $sessionID);
    }

    /**
     * The endpoint for all survey/campaign submissions
     *
     * @return void
     */
    function submit()
    {
        // Convert from recaptcha into generic for ADS
        if (array_key_exists('g-recaptcha-response', $_POST))
            $_POST['abuse_challenge_response'] = $_POST['g-recaptcha-response'];
        $_POST['abuse_challenge_opaque'] = 'reCaptcha2';

        if (\RightNow\Libraries\AbuseDetection::isAbuse())
        {
            $this->load->file(CORE_FILES."compatibility/Views/ma/abuse/index.php");
        }
        else
        {
            $this->model('Document')->submitDocument();
        }
    }

    /**
     * Helper function to set up the survey data array
     *
     * @param array $params Array of params to be put into the surveyData array
     *
     * @return array an array of data associated with the survey
     */
    function getSurveyData($params)
    {
        $surveyData   = array();
        $surveyData['q_session_id'] = 0;
        $surveyData['score'] = 0;
        $surveyData['last_page_num'] = 0;
        $surveyData['sent_timestamp'] = 0;
        $surveyData['survey_id'] = -1;
        $surveyData['sent_timestamp'] = 0;
        $surveyData['override_mobile'] = 0;
        $surveyData['source'] = 0;
        $surveyData['source_id'] = '';

        foreach($params as $key => $value) {
            switch ($key) {
                case MA_QS_SURVEY_PARM:
                    $surveyData['survey_id'] = intval($value);
                    break;
                case MA_QS_SOURCE_PARM:
                    $surveyData['source'] = $value;
                    break;
                case MA_QS_SOURCE_ID_PARM:
                    $surveyData['source_id'] = strval($value);
                    break;
                case MA_QS_SURVEY_SENT_PARM:
                    $surveyData['sent_timestamp'] = intval(decode_base64_urlsafe(urldecode($value)));
                    break;
                case MA_QS_MOBILE_PARM:
                    $surveyData['override_mobile'] = intval($value);
                    break;
            }
        }

        if ($surveyData['sent_timestamp'] > 0 && $this->session)
            $this->session->setSessionData(array('surveySentTimestamp' => $surveyData['sent_timestamp']));

        return ($surveyData);
    }

}
