<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

Use \RightNow\UnitTest\Helper,
    \RightNow\Utils\Framework;

class DocumentsTest extends CPTestCase
{
    //@@@ QA#140801-000090 Security: Multiple Reflected XSS Vulnerabilities in Survey Pages
    //@@@ QA#150316-000144 ensure that you can't xss through the request parameter's name
    function testAbuseXSS()
    {
        $CI = get_instance();
        $_REQUEST['p_test'] = '<script>alert(1)</script>';
        $_REQUEST['<script>alert(1)</script>'] = 'test';
        $vars = array();
        $vars['path'] = CORE_FILES."compatibility/Views/ma/abuse/index.php";
        $response = $CI->load->file(CORE_FILES."compatibility/Views/ma/abuse/index.php", true);
        $this->assertFalse(strpos($response, "<script>"));
    }

    //@@@ QA#131003-000129 ensure survey pages have valid HTML
    //@@@ QA#130410-000022 ensure that fld_data.please_wait_message is populated
    //@@@ QA#140110-000043 add hidden question_session_type to output
    function testDocuments()
    {
        $documents = array(array("newsletterForm.html", "2/newsletter_form"),
                           array("favoriteColorSurvey.html", "5/5"));

        $this->setUpSql();

        foreach ($documents as $document)
        {
            $testFile = $document[0];
            $documentName = $document[1];
            $expected = $this->stripWhiteSpace(file_get_contents(CORE_FILES . "compatibility/Controllers/tests/marketingTestDocuments/$testFile"));
            $expected = str_replace("OE_WEB_SERVER", getConfig(OE_WEB_SERVER, 'COMMON'), $expected);
            $expected = preg_replace(array('/\<inputname="p_t".*?\>/', '/f_tok.*value.*?;/'), array('TRACKING_STRING', 'CSRF_TOKEN'), $expected);
            $url = "/detail/$documentName";
            $wgetCommand = $this->getWgetCommand($url);
            $output = $this->stripWhiteSpace($this->getOutput($wgetCommand));
            $output = preg_replace(array('/\<inputname="p_t".*?\>/', '/f_tok.*value.*?;/'), array('TRACKING_STRING', 'CSRF_TOKEN'), $output);
            if(!$this->assertTrue($output === $expected))
                $this->printError($testFile, $expected, $output);
        }
    }
    //@@@ QA#160519-000213 Make sure documents render with CP_CONTACT_LOGIN_REQUIRED set to true
    function testDocumentsWithCPLoginRequired()
    {
        $documents = array(array("newsletterForm.html", "2/newsletter_form"),
                           array("favoriteColorSurvey.html", "5/5"));

        $this->setUpSql();

        $contactLoginConfig = \RightNow\Utils\Config::getConfig(CP_CONTACT_LOGIN_REQUIRED);
        \Rnow::updateConfig('CP_CONTACT_LOGIN_REQUIRED', true);
        // CP deploys store the value of CP_CONTACT_LOGIN_REQUIRED in a file so you need to update that file also
        file_put_contents(OPTIMIZED_FILES . 'production/optimized/config/sandboxedConfigs', serialize(array('loginRequired' => true)));
        foreach ($documents as $document)
        {
            $testFile = $document[0];
            $documentName = $document[1];
            $expected = $this->stripWhiteSpace(file_get_contents(CORE_FILES . "compatibility/Controllers/tests/marketingTestDocuments/$testFile"));
            $expected = str_replace("OE_WEB_SERVER", getConfig(OE_WEB_SERVER, 'COMMON'), $expected);
            $expected = preg_replace(array('/\<inputname="p_t".*?\>/', '/f_tok.*value.*?;/'), array('TRACKING_STRING', 'CSRF_TOKEN'), $expected);
            $url = "/detail/$documentName";
            $wgetCommand = $this->getWgetCommand($url);
            $output = $this->stripWhiteSpace($this->getOutput($wgetCommand));
            $output = preg_replace(array('/\<inputname="p_t".*?\>/', '/f_tok.*value.*?;/'), array('TRACKING_STRING', 'CSRF_TOKEN'), $output);
            if(!$this->assertTrue($output === $expected))
                $this->printError($testFile, $expected, $output);
        }
        \Rnow::updateConfig('CP_CONTACT_LOGIN_REQUIRED', $contactLoginConfig);
        file_put_contents(OPTIMIZED_FILES . 'production/optimized/config/sandboxedConfigs', serialize(array('loginRequired' => $contactLoginConfig)));

    }
    // @@@ QA#130618-000129 tests for webform submits
    // @@@ QA#131212-000049 this feature test defect was caused by breaking this test
    // @@@ QA#170420-000130 tests for validation of document fields
    function testSubmit()
    {
        list($sessionCookie, $formToken, $devCookie) = $this->getSessionFormTokenDevCookie();

        $originalCount = sql_get_int("SELECT COUNT(*) FROM question_responses WHERE choice_id=56");

        $wgetCommand = $this->getWgetCommand("/submit",
            "--header='Cookie: cp_session={$sessionCookie};location=development~{$devCookie}' --post-data \"p_shortcut=Flow_10_Webpage_1_Doc_36&q_12=56&val_q_12=2712%27%2C-1%2C7%2C2%2C%27q_12_53%3Aq_12_54%3Aq_12_55%3Aq_12_56%3Aq_12_57%3Aq_12_58%3Aq_12_59%27%2C%27What+is+your+favorite+color%3F%27%2Cfalse&p_t=AvMG%7Ewr%7EDv8S6xb%7EGv8e%7EyK3Jv8q%7Ey77Mv9a%7Ez7%7EPv_T&_survey_score=0&_last_page_num=1&p_val_validated=0&f_tok={$formToken}\"");
        $response = $this->getOutput($wgetCommand);
        sql_commit();

        //ensure that the question response was recorded to the database
        $count = sql_get_int("SELECT COUNT(*) FROM question_responses WHERE choice_id=56");

        $this->assertTrue($count > $originalCount);

        //test case to validate document submit security check, submission without f_tok should be blocked with an error response
        $wgetCommand = $this->getWgetCommand("/submit",
            "--header='Cookie: cp_session={$sessionCookie};location=development~{$devCookie}' --post-data \"p_shortcut=Flow_10_Webpage_1_Doc_36&q_12=56&val_q_12=2712%27%2C-1%2C7%2C2%2C%27q_12_53%3Aq_12_54%3Aq_12_55%3Aq_12_56%3Aq_12_57%3Aq_12_58%3Aq_12_59%27%2C%27What+is+your+favorite+color%3F%27%2Cfalse&p_t=AvMG%7Ewr%7EDv8S6xb%7EGv8e%7EyK3Jv8q%7Ey77Mv9a%7Ez7%7EPv_T&_survey_score=0&_last_page_num=1&p_val_validated=0\"");
        $response = $this->getOutput($wgetCommand);
        $this->assertTrue(strpos($response, getMessage(FORM_SUBMISSION_TOKEN_MATCH_EXP_LBL)) > 0);

        //test case to check whether validation for trackString/campaignID/surveyID/docID is working fine
        $wgetCommand = $this->getWgetCommand("/submit",
            "--header='Cookie: cp_session={$sessionCookie};location=development~{$devCookie}' --post-data \"p_shortcut=Flow_10_Webpage_1_Doc_36&q_12=56&val_q_12=2712%27%2C-1%2C7%2C2%2C%27q_12_53%3Aq_12_54%3Aq_12_55%3Aq_12_56%3Aq_12_57%3Aq_12_58%3Aq_12_59%27%2C%27What+is+your+favorite+color%3F%27%2Cfalse&p_t=AvMG~wr~Dv8ShRb~Gv8e~yJ5Jv8q~y77Mv8n~z7~Pv~u&_survey_score=0&_last_page_num=1&p_val_validated=0&f_tok={$formToken}\"");
        $response = $this->getOutput($wgetCommand);
        $this->assertTrue(strpos($response, getMessage(ERR_SUBMITTING_FORM_DUE_INV_INPUT_LBL)) > 0);

        //adding an invalid field q_50 to the submission request, we should block document submissions with invalid fields
        $wgetCommand = $this->getWgetCommand("/submit",
            "--header='Cookie: cp_session={$sessionCookie};location=development~{$devCookie}' --post-data \"p_shortcut=Flow_10_Webpage_1_Doc_36&q_12=56&val_q_12=2712%27%2C-1%2C7%2C2%2C%27q_12_53%3Aq_12_54%3Aq_12_55%3Aq_12_56%3Aq_12_57%3Aq_12_58%3Aq_12_59%27%2C%27What+is+your+favorite+color%3F%27%2Cfalse&p_t=AvMG%7Ewr%7EDv8S6xb%7EGv8e%7EyK3Jv8q%7Ey77Mv9a%7Ez7%7EPv_T&_survey_score=0&_last_page_num=1&p_val_validated=0&f_tok={$formToken}&q_50=80\"");
        $response = $this->getOutput($wgetCommand);
        $this->assertTrue(strpos($response, getMessage(ERR_SUBMITTING_FORM_DUE_INV_INPUT_LBL)) > 0);

        //replacing invalid field q_50 with a valid field other_12_59, now we should not block the request
        $wgetCommand = $this->getWgetCommand("/submit",
            "--header='Cookie: cp_session={$sessionCookie};location=development~{$devCookie}' --post-data \"p_shortcut=Flow_10_Webpage_1_Doc_36&q_12=56&val_q_12=2712%27%2C-1%2C7%2C2%2C%27q_12_53%3Aq_12_54%3Aq_12_55%3Aq_12_56%3Aq_12_57%3Aq_12_58%3Aq_12_59%27%2C%27What+is+your+favorite+color%3F%27%2Cfalse&p_t=AvMG%7Ewr%7EDv8S6xb%7EGv8e%7EyK3Jv8q%7Ey77Mv9a%7Ez7%7EPv_T&_survey_score=0&_last_page_num=1&p_val_validated=0&f_tok={$formToken}&other_12_59=userchoice\"");
        $response = $this->getOutput($wgetCommand);
        $this->assertFalse(strpos($response, getMessage(ERR_SUBMITTING_FORM_DUE_INV_INPUT_LBL)));

        // @@@ QA#170613-000014
        //sending special fields should not block the request
        $wgetCommand = $this->getWgetCommand("/submit",
            "--header='Cookie: cp_session={$sessionCookie};location=development~{$devCookie}' --post-data \"p_shortcut=Flow_10_Webpage_1_Doc_36&q_12=56&val_q_12=2712%27%2C-1%2C7%2C2%2C%27q_12_53%3Aq_12_54%3Aq_12_55%3Aq_12_56%3Aq_12_57%3Aq_12_58%3Aq_12_59%27%2C%27What+is+your+favorite+color%3F%27%2Cfalse&p_t=AvMG%7Ewr%7EDv8S6xb%7EGv8e%7EyK3Jv8q%7Ey77Mv9a%7Ez7%7EPv_T&_survey_score=0&p_next_id=&_last_page_num=1&p_val_validated=0&f_tok={$formToken}&other_12_59=userchoice\"");
        $response = $this->getOutput($wgetCommand);
        $this->assertFalse(strpos($response, getMessage(ERR_SUBMITTING_FORM_DUE_INV_INPUT_LBL)));
    }
    // @@@ QA#130618-000129 tests for webform submits
    function testCampaignSubmit()
    {
        list($sessionCookie, $formToken, $devCookie) = $this->getSessionFormTokenDevCookie();

        $expectedFirstName = "testy";
        $expectedLastName = "Testerson";
        $expectedIntOne = 9;
        $email = "test@test.cp";

        test_sql_exec_direct("DELETE FROM _contacts_custom WHERE c_id = (SELECT c_id FROM _contacts WHERE email = '$email')");
        test_sql_exec_direct("DELETE FROM _contacts WHERE email = '$email'");
        sql_commit();

        $wgetCommand = $this->getWgetCommand("/submit",
            "--header='Cookie: cp_session={$sessionCookie};location=development~{$devCookie}' --post-data \"wf_2_100004=test%40test.cp&val_wf_2_100004=%27wf_2_100004%27%2C%27Email+Address%27%2C5%2C80%2C4&wf_2_100003=$expectedFirstName&val_wf_2_100003=%27wf_2_100003%27%2C%27First+Name%27%2C5%2C80%2C0&wf_2_100002=$expectedLastName&val_wf_2_100002=%27wf_2_100002%27%2C%27Last+Name%27%2C5%2C80%2C0&val_wf_2_46=%27wf_2_46%27%2C%27date1%27%2C7%2C0%2C0&wf_2_46_mon=&val_wf_2_46_mon=%27wf_2_46_mon%27%2C%27date1%27%2C7%2C0%2C0&wf_2_46_day=&val_wf_2_46_day=%27wf_2_46_day%27%2C%27date1%27%2C7%2C0%2C0&wf_2_46_yr=&val_wf_2_46_yr=%27wf_2_46_yr%27%2C%27date1%27%2C7%2C0%2C0&val_wf_2_47=%27wf_2_47%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_mon=&val_wf_2_47_mon=%27wf_2_47_mon%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_day=&val_wf_2_47_day=%27wf_2_47_day%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_yr=&val_wf_2_47_yr=%27wf_2_47_yr%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_hr=&val_wf_2_47_hr=%27wf_2_47_hr%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_min=&val_wf_2_47_min=%27wf_2_47_min%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_48=$expectedIntOne&val_wf_2_48=%27wf_2_48%27%2C%27int1%27%2C3%2C2%2C0%2C0%2C0%2C10&wf_2_49=&val_wf_2_49=%27wf_2_49%27%2C%27menu1%27%2C1%2C1%2C0&wf_2_52=&val_wf_2_52=%27wf_2_52%27%2C%27text1%27%2C5%2C80%2C0&wf_2_51=&val_wf_2_51=%27wf_2_51%27%2C%27textarea1%27%2C6%2C4000%2C0&val_wf_2_50=%27wf_2_50%27%2C%27optin%27%2C8%2C0%2C0&val_wf_2_53=%27wf_2_53%27%2C%27yesno1%27%2C2%2C0%2C0&p_t=AvMG%7Ewr%7EDv8S5Rb%7EGv8e%7EyKfJv8q%7Ey77Mv_8%7Ez7%7EPv91&p_shortcut=masterPageOne&_survey_score=0&_last_page_num=-1&p_val_validated=0&f_tok={$formToken}\"");
        $response = $this->getOutput($wgetCommand);
        sql_commit();
        $trackingStringInput = '<input name="p_t" type="hidden" value="';
        $trackingString = substr(strstr($response, $trackingStringInput), strlen($trackingStringInput), 55);
        $trackingString = explode('"', $trackingString);
        $trackingString = $trackingString[0];

        //ensure that a contact was created
        $cID = sql_get_int("SELECT c_id FROM contacts WHERE email='$email'");
        $this->assertTrue($cID > 0);

        $intOne = sql_get_int('SELECT c$int1 FROM contacts WHERE c_id=\'' . $cID . '\'');
        $this->assertTrue($intOne === $expectedIntOne);

        //ensure that prefill worked for first and last name
        $firstName = sql_get_str('SELECT first_name FROM contacts WHERE c_id=\'' .$cID . '\'', 80);
        $lastName = sql_get_str('SELECT last_name FROM contacts WHERE c_id=\'' .$cID . '\'', 80);

        $this->assertTrue($firstName == $expectedFirstName);
        $this->assertTrue($lastName == $expectedLastName);

        //clean up
        test_sql_exec_direct("DELETE FROM _contacts_custom WHERE c_id = (SELECT c_id FROM _contacts WHERE email = '$email')");
        test_sql_exec_direct("DELETE FROM _contacts WHERE email = '$email'");
        sql_commit();
    }

    function testCampaignSubmitUnsetField()
    {
        list($sessionCookie, $formToken, $devCookie) = $this->getSessionFormTokenDevCookie();

        $expectedFirstName = "Aaron";
        $expectedLastName = "Schubert";
        $email = "aschubert@rightnow.com.invalid";

        test_sql_exec_direct('UPDATE _contacts_custom cc JOIN _contacts cb ON cc.c_id = cb.c_id SET c$int1=12 WHERE email=\'' . $email . '\'');
        test_sql_exec_direct("UPDATE flow_web_pages SET sec_cookie=1, sec_tracking=1 WHERE shortcut='masterPageOne'");
        sql_commit();

        $wgetCommand = $this->getWgetCommand("/submit",
            "--header='Cookie: rnw_ma_login=%7C%7Caschubert%40rightnow.com.invalid%7CAvMG%7Ewr%7EDv8S%7Exb%7EGv8e%7EyL%7ELv8qMi7%7EMv%7EX%7Ezr%7EPv8e;cp_session={$sessionCookie};location=development~{$devCookie}' --post-data \"wf_2_100004=aschubert%40rightnow.com.invalid&val_wf_2_100004=%27wf_2_100004%27%2C%27Email+Address%27%2C5%2C80%2C4&wf_2_100003=Aaron&val_wf_2_100003=%27wf_2_100003%27%2C%27First+Name%27%2C5%2C80%2C0&wf_2_100002=Schubert&val_wf_2_100002=%27wf_2_100002%27%2C%27Last+Name%27%2C5%2C80%2C0&val_wf_2_46=%27wf_2_46%27%2C%27date1%27%2C7%2C0%2C0&wf_2_46_mon=4&val_wf_2_46_mon=%27wf_2_46_mon%27%2C%27date1%27%2C7%2C0%2C0&wf_2_46_day=4&val_wf_2_46_day=%27wf_2_46_day%27%2C%27date1%27%2C7%2C0%2C0&wf_2_46_yr=2007&val_wf_2_46_yr=%27wf_2_46_yr%27%2C%27date1%27%2C7%2C0%2C0&val_wf_2_47=%27wf_2_47%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_mon=3&val_wf_2_47_mon=%27wf_2_47_mon%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_day=6&val_wf_2_47_day=%27wf_2_47_day%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_yr=2003&val_wf_2_47_yr=%27wf_2_47_yr%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_hr=4&val_wf_2_47_hr=%27wf_2_47_hr%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_47_min=3&val_wf_2_47_min=%27wf_2_47_min%27%2C%27datetime1%27%2C4%2C0%2C0&wf_2_48=&val_wf_2_48=%27wf_2_48%27%2C%27int1%27%2C3%2C2%2C0%2C0%2C0%2C10&wf_2_49=23&val_wf_2_49=%27wf_2_49%27%2C%27menu1%27%2C1%2C1%2C0&wf_2_52=nothing+exciting&val_wf_2_52=%27wf_2_52%27%2C%27text1%27%2C5%2C80%2C0&wf_2_51=awesome&val_wf_2_51=%27wf_2_51%27%2C%27textarea1%27%2C6%2C4000%2C0&wf_2_50=1&val_wf_2_50=%27wf_2_50%27%2C%27optin%27%2C8%2C0%2C0&wf_2_53=0&val_wf_2_53=%27wf_2_53%27%2C%27yesno1%27%2C2%2C0%2C0&p_t=AvMG%7Ewr%7EDv8S5Rb%7EGv8e%7EyKfLv8qMi77Mv9o%7Ez7%7EPv_h&p_shortcut=masterPageOne&_survey_score=0&p_question_session_type=1&_last_page_num=-1&p_val_validated=0&f_tok={$formToken}\"");
        $response = $this->getOutput($wgetCommand);
        sql_commit();

        //ensure that custom field was set from 12 to NULL
        $intOne = sql_get_int('SELECT c$int1 FROM contacts WHERE email=\'' . $email . '\'');
        $this->assertIdentical($intOne, false);
    }

    // @@@ QA#140417-000041 test to ensure survey submit in proof mode is logged as a proof
    function testSurveyProofSubmit()
    {
        list($sessionCookie, $formToken, $devCookie) = $this->getSessionFormTokenDevCookie();

        $lastQuestionSessionID = 0;
        $proofMode = 0;
        $createdQuestionSessionID = 0;

        $lastQuestionSessionID = sql_get_int("SELECT MAX(q_session_id) FROM question_sessions");

        $cookies = Helper::logInUser();

        $wgetCommand = $this->getWgetCommand("/submit",
                                             "--header='Cookie: cp_session={$sessionCookie};location=development~{$devCookie}' --post-data \"q_10=46&val_q_10=%2710%27%2C1%2C10%2C2%2C%27q_10_43%3Aq_10_44%3Aq_10_45%3Aq_10_46%3Aq_10_47%3Aq_10_48%3Aq_10_49%3Aq_10_50%3Aq_10_51%3Aq_10_52%27%2C%27How+would+you+rate+our+service%3F%27%2Cfalse&q_11=&val_q_11=%2711%27%2C-1%2C4000%2C1%2C%27%27%2C%27Please+provide+any+additional+comments+or+feedback%3A%27%2Cfalse%2C%27%27%2C%27%27&p_t=AvMG%7Ewr%7EDv8S7xb%7EGv8e%7EyK7Lv84ri77Mv%7E5%7Ez7%7EPv8w&p_shortcut=Flow_8_Webpage_1_Doc_34&_survey_score=0&_source=1&_source_id=141&p_question_session_type=1&_last_page_num=1&p_val_validated=0&f_tok={$formToken}\"");

        $this->getOutput($wgetCommand);
        sql_commit();

        $createdQuestionSessionID = sql_get_int("SELECT MAX(q_session_id) FROM question_sessions");

        // First make sure we created a question_session
        $this->assertIdentical($lastQuestionSessionID, $createdQuestionSessionID - 1);

        //ensure that session is in proof mode
        $proofMode = sql_get_int("SELECT proof_mode FROM question_sessions WHERE q_session_id = $lastQuestionSessionID + 1");
        $this->assertIdentical($proofMode, 1);

        Helper::logOutUser($cookies['rawProfile'], $cookies['rawSession']);
    }

    function testVerifyContactLoggedOut() {
        $response = $this->getOutput($this->getWgetCommand("/verifyContact"));
        $this->assertIdentical('var existingRightNowContactID = 0;', $response);
    }

    function testVerifyContactLoggedIn() {
        $cookies = Helper::logInUser();
        $wgetCommand = $this->getWgetCommand("/verifyContact",
            "--header='Cookie: cp_session=".$cookies['session'].";cp_profile=".$cookies['profile']."'");
        $response = $this->getOutput($wgetCommand);
        $this->assertIdentical('var existingRightNowContactID = 1286;', $response);
        Helper::logOutUser($cookies['rawProfile'], $cookies['rawSession']);
    }

    function testVerifyContactInvalidCookie() {
        $wgetCommand = $this->getWgetCommand("/verifyContact",
            "--header='Cookie: cp_profile=banananananananananan " .
            "cp_session=" . $_COOKIE['cp_session'] . "'");
        $response = $this->getOutput($wgetCommand);
        $this->assertIdentical('var existingRightNowContactID = 0;', $response);
    }

    function testVerifyContactMACookie() {
        $wgetCommand = $this->getWgetCommand("/verifyContact",
            "--header='Cookie: rnw_ma_login=login|password|email|" . generic_track_encode(1286) . "'"
        );
        $response = $this->getOutput($wgetCommand);
        $this->assertIdentical('var existingRightNowContactID = 1286;', $response);
    }

    function testVerifyContactMACookieInvalid() {
        $wgetCommand = $this->getWgetCommand("/verifyContact",
            "--header='Cookie: rnw_ma_login=login|password|email|" . generic_track_encode(1286342342342) . "'"
        );
        $response = $this->getOutput($wgetCommand);
        $this->assertIdentical('var existingRightNowContactID = 0;', $response);
    }

    //@@@ QA#150724-000107 : Need to restrict access to surveys via proxy url
    function testSurveyByProxySecurity()
    {
        $url = "/detail/5/5/12/0364af5d0b33420eaedbf19cd07417819701fdd6/8/proxy/p_c_id/5";
        $output = $this->getOutput($this->getWgetCommand($url));
        if(!$this->assertTrue(strpos($this->stripWhiteSpace($output), "AccessDenied") !== FALSE))
        {
            echo "<h2 style='color: red'>Marketing Test Failure testSurveyByProxy</h2>";
            echo "Expected: <pre>Access Denied</pre>";
            echo "Actual: <pre>$output</pre><br />";
        }
    }

    //HELPERS
    private function getSessionFormTokenDevCookie()
    {
        $sessionCookie = Helper::getSessionCookie();
        return array($sessionCookie["session"], Framework::createToken(0), Framework::createLocationToken("development"));
    }

    private function getWgetCommand($url, $additionalHeaders = '')
    {
        $oeWebServer = getConfig(OE_WEB_SERVER, 'COMMON');
        return "wget --header='Host: $oeWebServer'"
            . " --header='RNT_REFERRER: $oeWebServer/ci/unitTest/rendering/getTestPage/widgets/'"
            . " $additionalHeaders"
            . " -q --output-document=- "
            . "'http://$oeWebServer/ci/documents$url' 2>&1";
    }

    private function getOutput($wgetCommand)
    {
        $handle = popen($wgetCommand, 'r');
        $output = '';
        while (!feof($handle)){
            $fragment = fread($handle, 512);
            $output .= $fragment;
        }
        fclose($handle);
        return $output;
    }

    private function printError($testFile, $expected, $recieved)
    {
        echo "<h2 style='color: red'>Marketing Test Failure $testFile</h2>";
        echo "Expected: <pre>$expected</pre>";
        echo "Recieved: <pre>$recieved</pre><br />";
    }

    private function stripWhiteSpace($input)
    {
        return preg_replace('/\s+/', '', $input);
    }

    private function setUpSql()
    {
        $sql = "update flow_web_pages set sec_login=0 where flow_web_page_id=5";
        test_sql_exec_direct($sql);
        sql_commit();
    }
}
