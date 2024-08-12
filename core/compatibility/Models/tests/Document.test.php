<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class DocumentTest extends CPTestCase
{
    public $documentModel;
    public $CI;

    function __construct()
    {
        parent::__construct();
        $this->documentModel = $this->CI->model('Document');
        $this->CI = get_instance();
    }

    private function getMethodInvoker($methodName) {
        return \RightNow\UnitTest\Helper::getMethodInvoker('\RightNow\Models\Document', $methodName);
    }

    // @@@QA#150407-000136 test for handleOtherLoginScenarios
    function testHandleOtherLoginScenarios()
    {
        $track = generic_track_decode("AvMG~wrTDv8S~xb~Gv8e~yIDLP8q04T7dedRDj7~Pv_Y");
        $clickCID = $track->c_id;
        $cID = $track->c_id;
        $useCID = $track->c_id;
        $useCookie = false;
        $secTracking = false;
        $forceTracking = false;
        $secCookie = false;
        $maCookieEmail = '';
        $maCookieTrackString = '';
        $surveyType = '';
        $this->documentModel->handleOtherLoginScenarios($secCookie, $secTracking, $useCookie, $forceTracking, $surveyType, $clickCID, $maCookieEmail, $maCookieTrackString, $cID, $track, $useCID);

        $this->assertEqual($useCID, 0);
    }

    // @@@150716-000099 : Flow Runtime / User Identification -- form fields don't pre-fill for identified contact in Survey
    function testHandleOtherLoginScenarios2()
    {
        $track = '';
        $clickCID = '';
        $cID = 1352;
        $useCID = 0;
        $useCookie = true;
        $secTracking = false;
        $forceTracking = false;
        $secCookie = true;
        $maCookieEmail = '';
        $maCookieTrackString = '';
        $surveyType = '';
        $this->documentModel->handleOtherLoginScenarios($secCookie, $secTracking, $useCookie, $forceTracking, $surveyType, $clickCID, $maCookieEmail, $maCookieTrackString, $cID, $track, $useCID);

        $this->assertEqual($cID, 1352);
    }

    // @@@150716-000099 : Flow Runtime / User Identification -- form fields don't pre-fill for identified contact in Survey
    function testDoNotIdentify()
    {
        $track = '';
        $clickCID = '';
        $cID = 1352;
        $useCID = 0;
        $useCookie = true;
        $secTracking = false;
        $forceTracking = false;
        $secCookie = false;
        $maCookieEmail = '';
        $maCookieTrackString = '';
        $surveyType = '';
        $this->documentModel->handleOtherLoginScenarios($secCookie, $secTracking, $useCookie, $forceTracking, $surveyType, $clickCID, $maCookieEmail, $maCookieTrackString, $cID, $track, $useCID);

        $this->assertEqual($cID, 0);
    }

    //@@@ 151208-000078 Pair Error is thrown when back button used for other type question with value but not selected
    function testCleanSurveyResponses()
    {
        $requests = array('q_19' => '87', 'other_19_85' => 'aaaaaa');
        $data = array(array('19', -1, 3, 2, 'q_19_83:q_19_84:q_19_85', 'Choose your own adventure:', false));
        $this->documentModel->cleanSurveyResponses($requests, $data);
        $this->assertFalse(array_key_exists('other_19_85', $requests));
    }

    //@@@ 141008-000006 : Survey - Pair error thrown when survey is taken with out selecting the other type choice question
    function testCheckSurveyFields()
    {
        $backup = $_REQUEST;

        $data = array(array('19', -1, 3, 2, 'q_19_83:q_19_84:q_19_85', 'Choose your own adventure:', false));
        $_REQUEST = array('other_19_85' => 'aaaaaa');
        $errors = $this->documentModel->ssvCheckSurveyFields($data);
        $this->assertTrue(count($errors) > 0);

        $_REQUEST = array('q_19_84' => '84', 'other_19_85' => 'aaaaaa');
        $errors = $this->documentModel->ssvCheckSurveyFields($data);
        $this->assertTrue(count($errors) > 0);

        $_REQUEST = array('q_19_85' => '85', 'other_19_85' => 'aaaaaa');
        $errors = $this->documentModel->ssvCheckSurveyFields($data);
        $this->assertTrue(count($errors) == 0);

        $_REQUEST = $backup;
    }

    //@@@ 140516-000091 : Improve handling of changes to a completed survey
    function testSurveyAlreadySubmitted()
    {
        $data = array('flowID' => 6, 'surveyID' => 1, 'cID' => 0);
        $surveyData = array('q_session_id' => 15, 'source' => VTBL_CONTACTS, 'source_id' => 0);

        $this->assertFalse($this->documentModel->surveyAlreadySubmitted($data, $surveyData));

        $surveyData['q_session_id'] = 16;
        $this->assertTrue($this->documentModel->surveyAlreadySubmitted($data, $surveyData));
    }

    //@@@ 140505-000068 : Back/Resume issue continued
    function testBuildSurveyCookie()
    {
        $data = array('flowID' => 14, 'wpID' => 37);
        $flowResult = new stdClass();
        $flowResult->q_session_id = 75;

        $cookieVal = $this->documentModel->buildSurveyCookie(NULL, $data, $flowResult);
        $this->assertTrue("||14_75_37" === $cookieVal);

        $data['wpID'] = 38;
        $cookieVal = $this->documentModel->buildSurveyCookie("||14_75_37", $data, $flowResult);
        $this->assertTrue("||14_75_37:38" === $cookieVal);

        //Resume fell back to previous page, wpID is the same as the last element in the breadcrumbs
        $cookieVal = $this->documentModel->buildSurveyCookie("||14_75_37:38", $data, $flowResult);
        $this->assertTrue("||14_75_37:38" === $cookieVal);

        //User has used survey back button to return to first page and submit a different response
        $data['wpID'] = 37;
        $cookieVal = $this->documentModel->buildSurveyCookie("||14_75_37:38:39?37", $data, $flowResult);
        $this->assertTrue("||14_75_37" === $cookieVal);
    }

    function testBackInPreview()
    {

        $track = generic_track_decode("AvUG~wr~Dv8S~xb~Gv8e~_j~Jv8q~y7~Mv~i~zr~");

        $invoke = $this->getMethodInvoker('shouldClearCookie');

        $track->flags = GENERIC_TRACK_FLAG_PREVIEW;
        $authparameter = "the length is greater than 0";
        $surveyData['surveyNavigation'] = 1;

        $result = $invoke($track, $authparameter, $surveyData);
        $this->assertFalse($result);

        $surveyData['surveyNavigation'] = 0;
        $result = $invoke($track, $authparameter, $surveyData);
        $this->assertTrue($result);

    }

    ///@@@ QA#140206-000153 avoid ISE when using back button when no responses have been logged
    function testBackNoResponses()
    {
        $resumeArgs = array(
            'flow_id' => 6,
            'q_session_id' => 400,
            'flow_web_page_id' => 0,
            'survey' => array('survey_id' => 1, 'q_session_id' => 400),
            'questions' => array(),
            'question_order' => array(),
            'question_types' => array(),
            'rebuild_crumbs' => FALSE);

        $resume = survey_resume_shortcut($resumeArgs, TRUE);
        // Assert is just topical here.  Before my fix, the survey_resume_shortcut call would result in a seg fault
        $this->assertTrue(true);
    }

    function testMaxResponses()
    {
        $surveyID = 5;
        $authParameter = "0364af5d0b33420eaedbf19cd07417819701fdd6";
        $surveyData = array('q_session_id' => 0, 'score' => 0, 'survey_id' => $surveyID, 'last_page_num' => 0, 'source' => '', 'source_id'=> '');
        putenv("API_TEST=1"); // workaround to allow direct sql to the surveys table from development builds.
        $prevMaxResponses = sql_get_int("SELECT max_responses FROM surveys WHERE survey_id = $surveyID");
        $prevNumStarted = sql_get_int("SELECT num_started FROM surveys WHERE survey_id = $surveyID");


        // Survey should not be expired - result should be populated
        test_sql_exec_direct("UPDATE surveys set max_responses = 2, num_started = 1 WHERE survey_id = $surveyID");
        sql_commit();

        $options = array('trackString' => '', 'useCookie' => true, 'source' => 0, 'sourceID' => 0,
                         'mailingIDs' => '', 'preview' => true, 'forceTracking' => false, 'stats' => '',
                         'maCookie' => '', 'prefillVal' => '', 'formSubmit' => false, 'type' => '',
                         'rememberCookie' => '', 'newID' => 0, 'newSource' => 0, 'authParameter' => $authParameter,
                         'accountID' => 0, 'previewMobile' => 0);

        $result = $this->documentModel->getDocument('', $surveyData, null, '', $options);

        $this->assertTrue(strlen($result) > 0);

        // Survey should be expired - result should be null
        test_sql_exec_direct("UPDATE surveys set max_responses = 1, num_started = 1 WHERE survey_id = $surveyID");
        sql_commit();

        $result = $this->documentModel->getDocument('', $surveyData, null, '', $options);

        $this->assertTrue($result === null);

        if ($prevMaxRepsonses === null)
            $prevMaxResponses = 'NULL';
        if ($prevNumStarted === null)
            $prevNumStarted = 'NULL';

        test_sql_exec_direct("UPDATE surveys set max_responses = $prevMaxResponses, num_started = $prevNumStarted WHERE survey_id = $surveyID");
        sql_commit();
    }

    ///@@@ QA#140204-000071 do not resume when they are clicking 'switch to desktop'
    function testCheckResumeSwitchToDesktop()
    {
        $flowID = 6;
        $rememberCookie = "||6_39_23:24";
        $source = 0;
        $sourceID = 0;
        $surveyData = array('override_mobile' => 1);

        $this->documentModel->checkResumeSurvey($flowID, $rememberCookie, $surveyData, $formShortcut, $resumeSurvey, $source, $sourceID, $cID);

        $this->assertFalse($cID === 1154);
        $this->assertTrue($resumeSurvey === FALSE);
    }


    ///@@@ QA#140117-000110 keep c_id on survey resume
    function testCheckResume()
    {
        $flowID = 6;
        $rememberCookie = "||6_39_23:24";
        $source = 0;
        $sourceID = 0;

        $this->documentModel->checkResumeSurvey($flowID, $rememberCookie, $surveyData, $formShortcut, $resumeSurvey, $source, $sourceID, $cID);

        $this->assertTrue($cID == 1154);
    }

    function testMobileDetection()
    {
        $surveyID = 5;
        test_sql_exec_direct("UPDATE surveys SET attr=1 WHERE survey_id=$surveyID");
        $authParameter = "0364af5d0b33420eaedbf19cd07417819701fdd6";
        $surveyData = array('q_session_id' => 0, 'score' => 0, 'survey_id' => $surveyID, 'last_page_num' => 0, 'source' => '', 'source_id'=> '');

        $originalUserAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.2; en-gb; GT-P1000 Build/FROYO) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';

        $options = array('trackString' => '', 'useCookie' => true, 'source' => 0, 'sourceID' => 0,
                         'mailingIDs' => '', 'preview' => true, 'forceTracking' => false, 'stats' => '',
                         'maCookie' => '', 'prefillVal' => '', 'formSubmit' => false, 'type' => '',
                         'rememberCookie' => '', 'newID' => 0, 'newSource' => 0, 'authParameter' => $authParameter,
                         'accountID' => 0, 'previewMobile' => 0);

        $result = $this->documentModel->getDocument('', $surveyData, null, '', $options);

        //Search for mobile js
        $this->assertTrue(strlen(strstr($result, '<script src="/rnt/rnw/jquery_mobile_1.3.2/jquery.mobile-1.3.2.min.js" type="text/javascript">')) > 0);
        test_sql_exec_direct("UPDATE surveys SET attr=0 WHERE survey_id=$surveyID");
        $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
    }

    ///@@@ QA#131218-000082 test for back button functionality
    function testBackButton()
    {
        $surveyID = 7;
        $surveyData = array('q_session_id' =>500, 'score' => 0, 'survey_id' => $surveyID, 'last_page_num' => 0, 'source' => '', 'source_id'=> '',
                            'surveyNavigation' => 1);

        $track = generic_track_encode(1254);

        $options = array('trackString' => $track, 'useCookie' => true, 'source' => 0, 'sourceID' => 0,
                         'mailingIDs' => '', 'preview' => true, 'forceTracking' => false, 'stats' => '',
                         'maCookie' => '', 'prefillVal' => '', 'formSubmit' => false, 'type' => '',
                         'rememberCookie' => '', 'newID' => 0, 'newSource' => 0, 'authParameter' => null,
                         'accountID' => 0, 'previewMobile' => 0);

        $result = $this->documentModel->getDocument('', $surveyData, null, 'Flow_12_Webpage_3_Doc_46', $options);

        //Search for the question text of the third page which should be returned in the result
        $this->assertTrue(strlen(strstr($result, 'What is your favorite color')) > 0);
    }

    ///@@@ QA#140107-000122 ensure that desktop link keeps tracking string
    function testMobileSurveyDesktopLinkPresent()
    {
        $surveyID = 5;
        $track = generic_track_encode(1254);
        test_sql_exec_direct("UPDATE surveys SET attr=1 WHERE survey_id=$surveyID");
        $authParameter = "0364af5d0b33420eaedbf19cd07417819701fdd6";
        $surveyData = array('q_session_id' => 0, 'score' => 0, 'survey_id' => $surveyID, 'last_page_num' => 0, 'source' => '', 'source_id'=> '');

        $originalUserAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.2; en-gb; GT-P1000 Build/FROYO) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';

        $options = array('trackString' => $track, 'useCookie' => true, 'source' => 0, 'sourceID' => 0,
                         'mailingIDs' => '', 'preview' => true, 'forceTracking' => false, 'stats' => '',
                         'maCookie' => '', 'prefillVal' => '', 'formSubmit' => false, 'type' => '',
                         'rememberCookie' => '', 'newID' => 0, 'newSource' => 0, 'authParameter' => $authParameter,
                         'accountID' => 0, 'previewMobile' => 0);

        $result = $this->documentModel->getDocument('', $surveyData, null, '', $options);

        //Search for the switch to desktop link
        $this->assertTrue(strlen(strstr($result, "0364af5d0b33420eaedbf19cd07417819701fdd6/13/MA!!/14/1")) > 0);
        $this->assertTrue(strlen(strstr($result, "ci/documents/detail/1/Av")) > 0);
        test_sql_exec_direct("UPDATE surveys SET attr=0 WHERE survey_id=$surveyID");
        $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
    }

    ///@@@ QA#150921-000244 make sure the link associates acct id, source and source id
    function testSwitchToDesktopLink()
    {
        $surveyID = 5;
        test_sql_exec_direct("UPDATE surveys SET attr=1 WHERE survey_id=$surveyID");
        $authParameter = "0364af5d0b33420eaedbf19cd07417819701fdd6";
        $surveyData = array('q_session_id' => 0, 'score' => 0, 'survey_id' => $surveyID, 'last_page_num' => 0, 'source' => '', 'source_id'=> '');

        $originalUserAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.2; en-gb; GT-P1000 Build/FROYO) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';

        $options = array('trackString' => '', 'useCookie' => true, 'source' => 1, 'sourceID' => 99,
                         'mailingIDs' => '', 'preview' => true, 'forceTracking' => false, 'stats' => '',
                         'maCookie' => '', 'prefillVal' => '', 'formSubmit' => false, 'type' => '',
                         'rememberCookie' => '', 'newID' => 0, 'newSource' => 0, 'authParameter' => $authParameter,
                         'accountID' => 99, 'previewMobile' => 0);

        $result = $this->documentModel->getDocument('', $surveyData, null, '', $options);

        //Search for mobile js
        $this->assertTrue(strlen(strstr($result, '15/OTk!/7/99/6/1')) > 0);
        test_sql_exec_direct("UPDATE surveys SET attr=0 WHERE survey_id=$surveyID");
        $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
    }

   function testOverrideMobile()
    {
        $surveyID = 5;
        test_sql_exec_direct("UPDATE surveys SET attr=1 WHERE survey_id=$surveyID");
        $authParameter = "0364af5d0b33420eaedbf19cd07417819701fdd6";
        $surveyData = array('q_session_id' => 0, 'score' => 0, 'survey_id' => $surveyID, 'last_page_num' => 0, 'source' => '', 'source_id'=> '', 'override_mobile' => 1);
        putenv("HTTP_USER_AGENT=ANDROID"); // workaround to allow direct sql to the surveys table from development builds.

        $options = array('trackString' => '', 'useCookie' => true, 'source' => 0, 'sourceID' => 0,
                         'mailingIDs' => '', 'preview' => true, 'forceTracking' => false, 'stats' => '',
                         'maCookie' => '', 'prefillVal' => '', 'formSubmit' => false, 'type' => '',
                         'rememberCookie' => '', 'newID' => 0, 'newSource' => 0, 'authParameter' => $authParameter,
                         'accountID' => 0, 'previewMobile' => 0);

        $result = $this->documentModel->getDocument('', $surveyData, null, '', $options);

        //Search for the switch to desktop link
        $this->assertFalse(strstr($result, '<script src="/rnt/rnw/jquery_mobile_1.3.2/jquery.mobile-1.3.2.js">'));
        test_sql_exec_direct("UPDATE surveys SET attr=0 WHERE survey_id=$surveyID");
    }

    ///@@@ QA#130909-000029 test for isMobile()
    function testIsMobile()
    {
        $originalUserAgent = $_SERVER['HTTP_USER_AGENT'];
        $surveyID = 5;

        $_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36";
        $isMobile = $this->documentModel->isMobile($surveyID);
        $this->assertFalse($isMobile);
        test_sql_exec_direct("UPDATE surveys SET attr=1 WHERE survey_id=$surveyID");
        $isMobile = $this->documentModel->isMobile($surveyID);
        $this->assertFalse($isMobile);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.2; en-gb; GT-P1000 Build/FROYO) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';
        $isMobile = $this->documentModel->isMobile($surveyID);
        $this->assertTrue($isMobile);

        test_sql_exec_direct("UPDATE surveys SET attr=0 WHERE survey_id=$surveyID");
        $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
    }

    ///@@@ QA#140207-000047 test for isMobileUserAgent()
    function testIsMobileUserAgentWithService()
    {
        $originalUserAgent = $_SERVER['HTTP_USER_AGENT'];
        $originalHost = getConfig(USER_AGENT_SERVICE_HOST);
        $originalPort = getConfig(USER_AGENT_SERVICE_PORT);

        Rnow::updateConfig('USER_AGENT_SERVICE_HOST', 'marias.us.oracle.com.com');
        Rnow::updateConfig('USER_AGENT_SERVICE_PORT', 5566);
        $_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36";

        $isMobile = $this->documentModel->isMobileUserAgent();
        $this->assertFalse($isMobile);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.2; en-gb; GT-P1000 Build/FROYO) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';
        $isMobile = $this->documentModel->isMobileUserAgent();
        $this->assertTrue($isMobile);

        Rnow::updateConfig('USER_AGENT_SERVICE_HOST', $originalHost);
        Rnow::updateConfig('USER_AGENT_SERVICE_PORT', $originalPort);
        $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
    }

    ///@@@ QA#140207-000047 test for isMobileUserAgent()
    function testIsMobileUserAgentWithoutService()
    {
        $originalUserAgent = $_SERVER['HTTP_USER_AGENT'];
        $originalHost = getConfig(USER_AGENT_SERVICE_HOST);
        $originalPort = getConfig(USER_AGENT_SERVICE_PORT);
        $_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36";

        //ensure that the call to the service will fail so we hit our fallback code
        Rnow::updateConfig('USER_AGENT_SERVICE_HOST', 'doesnotexist.us.oracle.com');
        Rnow::updateConfig('USER_AGENT_SERVICE_PORT', 1010);

        $isMobile = $this->documentModel->isMobileUserAgent();
        $this->assertFalse($isMobile);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.2; en-gb; GT-P1000 Build/FROYO) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';
        $isMobile = $this->documentModel->isMobileUserAgent();
        $this->assertTrue($isMobile);

        Rnow::updateConfig('USER_AGENT_SERVICE_HOST', $originalHost);
        Rnow::updateConfig('USER_AGENT_SERVICE_PORT', $originalPort);
        $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
    }

    ///@@@ QA#140102-000070 test for getQuestionSessionType
    function testgetQuestionSessionType()
    {
        $this->assertTrue($this->documentModel->getQuestionSessionType(true, false, false) === QUESTION_SESSION_TYPE_PROXY);
        $this->assertTrue($this->documentModel->getQuestionSessionType(true, false, true) === QUESTION_SESSION_TYPE_PROXY);
        $this->assertTrue($this->documentModel->getQuestionSessionType(true, true, true) === QUESTION_SESSION_TYPE_PROXY);
        $this->assertTrue($this->documentModel->getQuestionSessionType(true, true, false) === QUESTION_SESSION_TYPE_PROXY);

        $this->assertTrue($this->documentModel->getQuestionSessionType(false, true, false) === QUESTION_SESSION_TYPE_MOBILE);

        $this->assertTrue($this->documentModel->getQuestionSessionType(false, true, true) === QUESTION_SESSION_TYPE_WEB);
        $this->assertTrue($this->documentModel->getQuestionSessionType(false, false, true) === QUESTION_SESSION_TYPE_WEB);
        $this->assertTrue($this->documentModel->getQuestionSessionType(false, false, false) === QUESTION_SESSION_TYPE_WEB);
    }

    //@@@ QA#140110-000036 fix for newline issue with server side validation
    //chrome and firefox like to consider newlines as simply \n instead of \r\n on the javascript side of things
    //the problem is that then they send in \r\n when they actually submit the form (they are forced to do this by the spec).
    //So our javascript count says - hey you pass length requirements, but our server side validtion wouldn't.
    //The fix was to replace \r\n with \n when doing the counting, but we still insert \r\n. This seems
    //like it could cause issues except that survey fields are maxed out at 4000 characters and we have a
    //mediumtext (16,777,215) on the actual db field so we don't care if they go over 4000 due to a few newlines
    function testSurveyServerSideValidationNewlineIssue()
    {
        $invoke = $this->getMethodInvoker('ssvCheckSurveyFields');

        $fields = array('0' => array('0' => '11', '1' => -1, '2' => 10, '3' => 1, '4' => '', '5' => 'Please provide any additional comments or feedback:', '6' => false, '7' => "'#878787','#D40D12'" ));
        $_REQUEST['q_11'] = "0\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n9";
        $result = $invoke($fields);
        $this->assertTrue(sizeof($result) === 0);

        $_REQUEST['q_11'] = "0\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n910";
        $result = $invoke($fields);
        $this->assertTrue($result[0]['error_text'] === 'Field contains too many characters.');
    }

    //@@@ QA#140117-000116 test emoji characters
    function testEmoji()
    {
        $invoke = $this->getMethodInvoker('ssvCheckSurveyFields');

        $fields = array('0' => array('0' => '12', '1' => 1, '2' => 20, '3' => 1, '4' => '', '5' => 'Favorite color', '6' => false, '7' => "''" ));
        $_REQUEST['q_12'] = "pink\xF0\x9F\x99\x89";
        $result = $invoke($fields);
        $this->assertTrue(sizeof($result) === 0);
    }

    //@@@ QA#150814-000169 test FFFF character
    function testFFFF()
    {
        $invoke = $this->getMethodInvoker('ssvCheckSurveyFields');

        $fields = array('0' => array('0' => '12', '1' => 1, '2' => 20, '3' => 1, '4' => '', '5' => 'Favorite color', '6' => false, '7' => "''" ));
        $_REQUEST['q_12'] = "pink\xEF\xBF\xBF";
        $result = $invoke($fields);
        $this->assertTrue(sizeof($result) === 0);
    }

    //@@@ QA#150708-000082 test to ensure we get the question_session_id correctly.
    function testGetQSessionID()
    {
        $invoke = $this->getMethodInvoker('getQSessionID');

        $remQsID = 0;
        $resumeResult = (object) array('questionSessionID' => 5);
        $expected = 5;
        $result = $invoke($remQsID, $resumeResult);
        $this->assertTrue($expected === $result);

        $remQsID = 4;
        $resumeResult = (object) array('questionSessionID' => 5);
        $expected = 4;
        $result = $invoke($remQsID, $resumeResult);
        $this->assertTrue($expected === $result);

        $remQsID = "blah";
        $resumeResult = (object) array('questionSessionID' => 5);
        $expected = 5;
        $result = $invoke($remQsID, $resumeResult);
        $this->assertTrue($expected === $result);

        $remQsID = -5;
        $resumeResult = (object) array('questionSessionID' => 5);
        $expected = 5;
        $result = $invoke($remQsID, $resumeResult);
        $this->assertTrue($expected === $result);

    }

    //@@@ QA#140208-000002 test for getRedirectPath
    //@@@ QA#140312-000138 ensure that we keep params that dictate whether we go to mobile or not
    //@@@ QA#140313-000083 need more special mobile handling
    function testGetRedirectPath()
    {
        $invoke = $this->getMethodInvoker('getRedirectPath');
        $oeWebServer = getConfig(OE_WEB_SERVER);
        $previewParm = "%2Fp_preview%2F1";
        $base = "https://$oeWebServer/app/utils/login_form/redirect/%2Fci%2Fdocuments%2Fdetail%2F2%2Ftest%2Fsverified%2F1";
        $expected = $base.$previewParm;

        //standard preview tracking string
        $trackString = 'AvUG~wr~Dv8S~xb~Gv8e~_j~Jv8q~y7~Mv~i~zr~';
        $track = generic_track_decode($trackString);
        $formShortcut = 'test';

        $result = $invoke($track, $trackString, $formShortcut, 0, "", 0);
        $this->assertTrue($expected === $result);

        $expected = $base;

        //should keep the track string in normal mode
        $newExpected = "$expected"."%2F1%2FAvUG%7Ewr%7EDv8S%7Exb%7EGv8e%7E_j%7EJv8q%7Ey7%7EMv%7Ei%7Ezr%7E";
        $track->flags = 0; //take off the preview flag
        $result = $invoke($track, $trackString, $formShortcut, null, $undefined, $undefined);
        $this->assertTrue($newExpected === $result);

        //wont have a track in the case of a website link survey
        $result = $invoke($undefined, $undefined, $formShortcut, $undefined, null, $undefined);
        $this->assertTrue($expected === $result);

        $expected = "https://$oeWebServer/app/utils/login_form/redirect/%2Fci%2Fdocuments%2Fdetail%2F2%2Ftest%2Fsverified%2F1%2F5%2F8%2F12%2FAUTH_PARAM";
        $result = $invoke($undefined, $undefined, $formShortcut, 8, "AUTH_PARAM", $undefined);
        $this->assertTrue($expected === $result);

        $expected = "https://$oeWebServer/app/utils/login_form/redirect/%2Fci%2Fdocuments%2Fdetail%2F2%2Ftest%2Fsverified%2F1%2F5%2F8%2F12%2FAUTH_PARAM%2Fp_pre_mobile%2F1";
        $result = $invoke($undefined, $undefined, $formShortcut, 8, "AUTH_PARAM", 1);
        $this->assertTrue($expected === $result);
    }

    //@@@ 140709-000078 : Fix exception when setting cookie
    function testsetMaCookie()
    {
        $flow_result = "";
        $data['setCookie'] = 1;
        $expected = true;
        $result = $this->documentModel->setMaCookie($data, $flowResult);
        $this->assertTrue($result === $expected);
    }

    //@@@ 140905-000086 : Fix reminder click and views not being recorded
    function testlogClickAndViewTransaction()
    {
        $expected = 2;
        $track = generic_track_decode("AvMG~wr9Dv8S4xb~Gv8e~yIPJv_q9e77VXESNj7~Pv~b");
        $clickCID = $track->c_id;
        $flowID = $track->flow_id;
        $cID = 0;
        $useCID = $track->c_id;
        $wpID = 37;
        $formSubmit = false;
        $surveyID = 1;
        $docID = $track->doc_id;
        $flags = 0;

        $this->documentModel->logClickAndViewTransaction($track, $clickCID, $cID, $useCID, $wpID, $formSubmit, $flowID, $surveyID, $docID, true);

        // Call again as a preview.  This shouldn't change anything since preview should not update ma_trans.
        $track->flags = GENERIC_TRACK_FLAG_PREVIEW;
        $this->documentModel->logClickAndViewTransaction($track, $clickCID, $cID, $useCID, $wpID, $formSubmit, $flowID, $surveyID, $docID, true);

        sql_commit();

        $result = sql_get_int("SELECT COUNT(*) FROM ma_trans WHERE (type = 2 OR type = 8 OR type = 18) AND flow_id = $track->flow_id");
        test_sql_exec_direct("DELETE FROM ma_trans WHERE (type = 2 OR type = 8 OR type = 18) AND flow_id = $track->flow_id");
        sql_commit();

        $this->assertTrue($result === $expected);
    }

    //@@@ QA#140331-000111 test for getPrefillString
    function testGetPrefillString()
    {
        $params = array();
        $params['wf_2_48'] = '86';
        $params['wf_2_52'] = 'testing';

        $prefillVal = $this->documentModel->getPrefillString($params);
        $this->assertIdentical($prefillVal, '48=86|52=testing|');
    }

    // @@@ 140725-000115 Handle logging from sales generated message viewing
    function testServiceViewInBrowserCreateTrans()
    {
        $invoke = $this->getMethodInvoker('serviceViewInBrowserCreateTrans');

        // Make sure normal values work
        $messageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $trackingDataObject = array('c_id'=>101, 'trans_type'=>2, 'thread_id'=>1,
                                    'doc_id'=>51, 'email_type'=>2);
        $invoke((object)$trackingDataObject, 2, 'abcd1234');
        sql_commit();
        $newMessageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $this->assertEqual($messageTransCount + 1, $newMessageTransCount);

        // Missing or zero c_id should not be added - usually associated with opportunity org based message
        $messageTransCount = $newMessageTransCount;
        $trackingDataObject = array('trans_type'=>2, 'thread_id'=>1,
                           'doc_id'=>10, 'email_type'=>2);
        $invoke((object)$trackingDataObject, 2, 'abcd1234');
        sql_commit();
        $newMessageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $this->assertEqual($messageTransCount, $newMessageTransCount);

        $messageTransCount = $newMessageTransCount;
        $trackingDataObject = array('trans_type'=>2, 'thread_id'=>1, 'c_id'=>0,
                           'doc_id'=>10, 'email_type'=>2);
        $invoke((object)$trackingDataObject, 2, 'abcd1234');
        sql_commit();
        $newMessageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $this->assertEqual($messageTransCount, $newMessageTransCount);

        // Missing or zero thread_id should be added - usually associated with opportunity based message
        $messageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $trackingDataObject = array('c_id'=>102, 'trans_type'=>2, 'thread_id'=>0,
                                    'doc_id'=>51, 'email_type'=>2);
        $invoke((object)$trackingDataObject, 2, 'abcd1234');
        sql_commit();
        $newMessageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $this->assertEqual($messageTransCount, $newMessageTransCount);

        $messageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $trackingDataObject = array('c_id'=>103, 'trans_type'=>2,
                                    'doc_id'=>51, 'email_type'=>2);
        $invoke((object)$trackingDataObject, 2, 'abcd1234');
        sql_commit();
        $newMessageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $this->assertEqual($messageTransCount, $newMessageTransCount);

        // @@@ QA 240309-000000 cs_session_id property is not included in transaction if its session id is null,
        // transaction should be recorded
        $messageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $trackingDataObject = array('c_id'=>105, 'trans_type'=>2, 'thread_id'=>1,
                                    'doc_id'=>51, 'email_type'=>2);
        $cs_session_id = null;
        $invoke((object)$trackingDataObject, 2, $cs_session_id);
        sql_commit();
        $newMessageTransCount = sql_get_int("SELECT count(*) FROM message_trans");
        $this->assertEqual($messageTransCount + 1, $newMessageTransCount);
        test_sql_exec_direct("DELETE FROM message_trans WHERE cs_session_id IS NULL");

        test_sql_exec_direct("DELETE FROM message_trans WHERE cs_session_id = 'abcd1234'");
        sql_commit();
    }

}
