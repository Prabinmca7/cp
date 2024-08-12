<?php
use RightNow\Connect\v1_4 as Connect,
    RightNow\Controllers,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Text,
    RightNow\Utils\Config;

\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class AjaxRequestTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\AjaxRequest';

    function __construct() {
        parent::__construct();
    }

    function testGetClickstreamActionTag() {
        $ajaxRequest = new \RightNow\Controllers\AjaxRequest();
        $response = $ajaxRequest->_getClickstreamActionTag("getReportData");
        $this->assertEqual($response, "report_data_service");
    }

    function testIsContactAllowed() {
        $sandboxedConfigs = new \ReflectionClass('RightNow\Internal\Libraries\SandboxedConfigs');
        $sandboxedConfigsInitialized = $sandboxedConfigs->getProperty('initialized');
        $sandboxedConfigsConfigCache = $sandboxedConfigs->getProperty('configCache');
        $sandboxedConfigsInitialized->setAccessible(true);
        $sandboxedConfigsConfigCache->setAccessible(true);
        $sandboxedConfigsInitialized->setValue(false);
        $sandboxedConfigsConfigCache->setValue(null);

        $sandboxedConfigs = file_get_contents(OPTIMIZED_FILES . 'production/optimized/config/sandboxedConfigs');
        $CI = get_instance();
        $method = $CI->router->fetch_method();
        $configs = Helper::getConfigValues(array('CP_CONTACT_LOGIN_REQUIRED'));

        $ajaxRequest = new \ReflectionClass($this->testingClass);
        $method = $ajaxRequest->getMethod('_isContactAllowed');
        $method->setAccessible(true);
        $instance = $ajaxRequest->newInstance();

        $CI->router->set_method('getNewFormToken');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('sendForm');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('checkForExistingContact');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('doLogin');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('getChatQueueAndInformation');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('getReportData');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('emailAnswer');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('submitAnswerFeedback');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        Helper::setConfigValues(array('CP_CONTACT_LOGIN_REQUIRED' => true));
        file_put_contents(OPTIMIZED_FILES . 'production/optimized/config/sandboxedConfigs', serialize(array('loginRequired' => true)));
        $sandboxedConfigsInitialized->setValue(false);
        $sandboxedConfigsConfigCache->setValue(null);

        $CI->router->set_method('getNewFormToken');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('sendForm');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('checkForExistingContact');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('doLogin');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('getChatQueueAndInformation');
        $response = $method->invoke($instance);
        $this->assertTrue($response);

        $CI->router->set_method('getReportData');
        $response = $method->invoke($instance);
        $this->assertFalse($response);

        $CI->router->set_method('emailAnswer');
        $response = $method->invoke($instance);
        $this->assertFalse($response);

        $CI->router->set_method('submitAnswerFeedback');
        $response = $method->invoke($instance);
        $this->assertFalse($response);

        Helper::setConfigValues($configs);
        $CI->router->set_method($method);
        file_put_contents(OPTIMIZED_FILES . 'production/optimized/config/sandboxedConfigs', $sandboxedConfigs);
        $sandboxedConfigsInitialized->setValue(false);
        $sandboxedConfigsConfigCache->setValue(null);
    }

    function testGetReportData() {
        $requestToken = \RightNow\Utils\Framework::createToken(176);
        $filters = '{
            "recordKeywordSearch": true,
            "searchType": {
                "filters": {
                    "rnSearchType": "searchType",
                    "fltr_id": 5,
                    "data": 5,
                    "oper_id": 1,
                    "report_id": 176
                },
                "type": "searchType"
            },
            "keyword": {
                "w_id": "KeywordText_12",
                "filters": {
                    "searchName": "keyword",
                    "data": "",
                    "rnSearchType": "keyword",
                    "report_id": 176
                }
            },
            "page": 1,
            "report_id": 176,
            "per_page": 0,
            "sort_args": {
                "w_id": "SortList_11",
                "filters": {
                    "searchName": "sort_args",
                    "report_id": 176,
                    "data": {
                        "col_id": -1,
                        "sort_direction": 1
                    }
                }
            }
        }';

        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/getReportData',
            array(
                'post' => sprintf('filters=%s&report_id=%d&r_tok=%s',
                    $filters,
                    176,
                    $requestToken
                ),
                'cookie' => 'cp_session=' . get_instance()->sessionCookie,
            )
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertNull($response->error);
        $this->assertIsA($response->data, 'array');
        $this->assertTrue(count($response->data) > 0);
    }

    function testGetAnswer() {
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/getAnswer',
            array('post' => 'objectID=65')
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->result);
        $this->assertNotNull($response->result->ID);
        $this->assertEqual($response->result->ID, 65);
    }

    function testGetAnswerWithGuidedAssistance() {
        $answerID = 67;
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/getAnswer',
            array('post' => "objectID={$answerID}")
        ));

        $this->assertEqual($response->result->ID, $answerID);
        $this->assertEqual($response->result->GuidedAssistance->ID, 3);
    }

    function testSubmitAnswerFeedback() {
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/submitAnswerFeedback',
            array(
                'post' => sprintf("a_id=%d&rate=%d&f_tok=%s&message=%s&email=%s&threshold=%s&options_count=%s",
                    65,
                    1,
                    $token,
                    "turrable",
                    "chuckb@bball1.invalid",
                    1,
                    2
                ),
                'cookie' => 'cp_session=' . get_instance()->sessionCookie,
            )
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertIsA($response->ID, 'int');
    }

    function testSubmitAnswerRating() {
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/submitAnswerRating',
            array('post' => sprintf("a_id=%d&rate=%d&options_count=%s", 65, 1, 2)
            )
        ));

        // Submitting an answer rating just returns 1 immediately
        $this->assertEqual(1, $response);
    }

    function testDeleteNotification() {
        $postData = array('post' => "filter_type=answer&id=65&f_tok=" . \RightNow\Utils\Framework::createTokenWithExpiration(0),
                          'cookie' => 'cp_session=' . get_instance()->sessionCookie);

        $createResponse = json_decode($this->makeRequest(
            '/ci/AjaxRequest/addOrRenewNotification',
            $postData
        ));

        // Make sure there aren't any errors with the create
        $this->assertNull($createResponse->warning);
        $this->assertEqual($createResponse->error, 'Unable to determine contact.');

        $postData = array('post' => "filter_type=answer&id=65&f_tok=" . \RightNow\Utils\Framework::createTokenWithExpiration(0),
                          'cookie' => 'cp_session=' . get_instance()->sessionCookie);
        $deleteResponse = json_decode($this->makeRequest(
            '/ci/AjaxRequest/deleteNotification',
            $postData
	));

        // Make sure no errors came back from the delete
        $this->assertEqual("", $deleteResponse->warning);
        $this->assertEqual($deleteResponse->error, 'Unable to determine contact.');
    }

    function testAddOrRenewNotification() {
        $result = json_decode($this->makeRequest(
            '/ci/ajaxRequest/addOrRenewNotification',
            array('post' => "filter_type=product&id=129&cid=1172&f_tok=" . \RightNow\Utils\Framework::createTokenWithExpiration(0),
                  'cookie' => 'cp_session=' . get_instance()->sessionCookie)
            ));
        $this->assertIsA($result, 'stdClass');
        $this->assertNull($result->error);
        $this->assertIsA($result->notifications, 'array');
        $this->assertTrue(in_array($result->action, array('renew', 'create')));

        $result = json_decode($this->makeRequest(
            '/ci/ajaxRequest/addOrRenewNotification',
            array('post' => "filter_type=notaproduct&id=129&cid=1172&f_tok=" . \RightNow\Utils\Framework::createTokenWithExpiration(0),
                  'cookie' => 'cp_session=' . get_instance()->sessionCookie)
        ));
        $this->assertIsA($result, 'stdClass');
        $this->assertEqual("Invalid filter type: 'notaproduct'", $result->error);
        $this->assertIsA($result->notifications, 'array');
        $this->assertEqual(0, count($result->notifications));
        $this->assertNull($result->action);
    }

    function sendFormAssertions($response, $expectations) {
        $this->assertNotNull($response->result);
        $this->assertNotNull($response->result->sessionParam);
        $this->assertIsA($response->result, 'stdClass');
        if($expectations['redirect']) {
            $this->assertNotNull($response->result->redirectOverride);
            $this->assertEqual($response->result->redirectOverride, '/app/error/error_id/' . $expectations['redirect']);
        }
        if($expectations['contact']) {
            $this->assertNotNull($response->result->transaction);
            $this->assertNotNull($response->result->transaction->contact);
            $this->assertNotNull($response->result->transaction->contact->value);
            $this->assertIsA($response->result->transaction->contact->value, 'int');
            if($expectations['contact'] !== true)
                $this->assertEqual($response->result->transaction->contact->value, $expectations['contact']);
        }
        if($expectations['i_id'] || $expectations['refno']) {
            $this->assertNotNull($response->result->transaction);
            $this->assertNotNull($response->result->transaction->incident);
            $this->assertNotNull($response->result->transaction->incident->value);
            if($expectations['i_id']) {
                $this->assertIsA($response->result->transaction->incident->value, 'int');
                $this->assertNotNull($response->result->transaction->incident->key);
                $this->assertIsA($response->result->transaction->incident->key, 'string');
                $this->assertEqual($response->result->transaction->incident->key, 'i_id');
                if($expectations['i_id'] !== true)
                    $this->assertEqual($response->result->transaction->incident->value, $expectations['i_id']);
            }
            else {
                $this->assertIsA($response->result->transaction->incident->value, 'string');
                $this->assertNotNull($response->result->transaction->incident->key);
                $this->assertIsA($response->result->transaction->incident->key, 'string');
                $this->assertEqual($response->result->transaction->incident->key, 'refno');
            }
        }
    }

    function testSendForm() {
        $randomData = time() . rand();
        $contacts = array();
        $incidents = array();

        // invalid form token
        $user = "chuckb.$randomData@bball1.invalid";
        $formData = '[{"name": "Contact.Emails.PRIMARY.Address", "value": "' . $user . '", "required": true},
            {"name": "Contact.Name.First", "value": "Chuck", "required": true}]';
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/sendForm',
            array(
                'post' => "form=$formData&f_tok=0&updateIDs={\"i_id\":null,\"asset_id\":null}",
                'cookie' => 'cp_session=' . get_instance()->sessionCookie,
            )
        ));

        $this->sendFormAssertions($response, array('redirect' => 5));

        //Contact create no cookies
        $formData = '[{"name": "Contact.Emails.PRIMARY.Address", "value": "' . $user . '", "required": true},
            {"name": "Contact.Login", "value": "' . $user . '", "required": true}]';
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/sendForm' . get_instance()->sessionString,
            array(
                'post' => "form=$formData&f_tok=$token&updateIDs={\"i_id\":null,\"asset_id\":null}",
            )
        ));

        $this->sendFormAssertions($response, array('redirect' => 7, 'contact' => true));
        $contacts[] = $response->result->transaction->contact->value;

        //Contact create
        $user = "chuckb.$randomData@bball2.invalid";
        $formData = '[{"name": "Contact.Emails.PRIMARY.Address", "value": "' . $user . '", "required": true},
            {"name": "Contact.Login", "value": "' . $user . '", "required": true},
            {"name": "Contact.NewPassword", "value": ""}]';
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/sendForm',
            array(
                'post' => "form=$formData&f_tok=$token&updateIDs={\"i_id\":null,\"asset_id\":null}",
                'cookie' => 'cp_login_start=1;cp_session=' . get_instance()->sessionCookie,
            )
        ));
        Connect\ConnectAPI::commit();

        $this->sendFormAssertions($response, array('contact' => true));
        $contactIDToCompare = $response->result->transaction->contact->value;
        $contacts[] = $contactIDToCompare;

        // Incident create not logged in, doesn't login
        $user = "chuckb.$randomData@bball3.invalid";
        $formData = '[{"name": "Contact.Emails.PRIMARY.Address", "value": "' . $user . '", "required": true},
            {"name": "Contact.Login", "value": "' . $user . '", "required": true},
            {"name": "Incident.Subject", "value": "My phone is broken", "required": true},
            {"name": "Incident.Threads", "value": "It does not work. Fix it!", "required": true}]';
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/sendForm',
            array(
                'post' => "form=$formData&f_tok=$token&updateIDs={\"i_id\":null,\"asset_id\":null}",
                'cookie' => 'cp_login_start=1;cp_session=' . get_instance()->sessionCookie,
            )
        ));

        $this->sendFormAssertions($response, array('contact' => true, 'refno' => true));
        $contacts[] = $response->result->transaction->contact->value;
        $incidents[] = $this->CI->model('Incident')->getIncidentIDFromRefno($response->result->transaction->incident->value)->result;

        // Incident create not logged in, does login
        $user = "chuckb.$randomData@bball4.invalid";
        $formData = '[{"name": "Contact.Emails.PRIMARY.Address", "value": "' . $user . '", "required": true},
            {"name": "Contact.Login", "value": "' . $user . '", "required": true},
            {"name": "Contact.NewPassword", "value": ""},
            {"name": "Incident.Subject", "value": "My phone is broken", "required": true},
            {"name": "Incident.Threads", "value": "It does not work. Fix it!", "required": true}]';
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/sendForm',
            array(
                'post' => "form=$formData&f_tok=$token&updateIDs={\"i_id\":null,\"asset_id\":null}",
                'cookie' => 'cp_login_start=1;cp_session=' . get_instance()->sessionCookie,
            )
        ));

        $this->sendFormAssertions($response, array('contact' => true, 'i_id' => true));
        $contacts[] = $contactIDToCompare = $response->result->transaction->contact->value;
        $incidents[] = $response->result->transaction->incident->value;
        Connect\ConnectAPI::commit();

        //Contact update
        $cookies = Helper::logInUser($user);
        $this->logIn($user);

        $formData = '[{"name": "Contact.Name.First", "value": "Chuck", "required": true}]';
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/sendForm',
            array('post' => "form=$formData&f_tok=$token&updateIDs={\"i_id\":null,\"asset_id\":null}", 'cookie' => 'cp_profile=' . $cookies['profile'] . ';cp_session=' . get_instance()->sessionCookie)
        ));

        $this->sendFormAssertions($response, array('contact' => $contactIDToCompare));

        // Incident create logged in
        $formData = '[{"name": "Incident.Subject", "value": "My phone is broken", "required": true},
            {"name": "Incident.Threads", "value": "It does not work. Fix it!", "required": true}]';
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/sendForm',
            array('post' => "form=$formData&f_tok=$token&updateIDs={\"i_id\":null,\"asset_id\":null}", 'cookie' => 'cp_profile=' . $cookies['profile'] . ';cp_session=' . get_instance()->sessionCookie)
        ));

        $this->sendFormAssertions($response, array('i_id' => true));
        $incidents[] = $incidentID = $response->result->transaction->incident->value;

        // Incident update
        $formData = '[{"name": "Incident.Subject", "value": "My phone is really broken", "required": true},
            {"name": "Incident.Threads", "value": "It does not work. Fix it now!", "required": true}]';
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/sendForm',
            array('post' => "form=$formData&f_tok=$token&updateIDs={\"i_id\":\"$incidentID\",\"asset_id\":null}", 'cookie' => 'cp_profile=' . $cookies['profile'] . ';cp_session=' . get_instance()->sessionCookie)
        ));

        $this->sendFormAssertions($response, array('i_id' => $incidentID));

        Helper::logOutUser($cookies['rawProfile'], $cookies['rawSession']);
        $this->logOut();

        Connect\ConnectAPI::commit();
        foreach ($incidents as $incidentID) {
            $incidentID = intval($incidentID);
            if ($incidentID >= 1) {
                $this->assertIsA($incidentID, 'int');
                $this->destroyObject(Connect\Incident::fetch($incidentID));
            }
        }
        foreach ($contacts as $contactID) {
            $this->destroyObject(Connect\Contact::fetch($contactID));
        }
        Connect\ConnectAPI::commit();
    }

    function testFlashData() {
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = $this->makeRequest(
            '/ci/AjaxRequest/sendForm',
            array(
                'cookie' => 'cp_login_start=1;cp_session=' . get_instance()->sessionCookie,
                'post' => "form={\"hi\": \"hello\"}&f_tok=$token&flash_message=spooooon",
                'justHeaders' => true
            ));

        preg_match_all("/cp_session=(.*);\ path/", $response, $sessionCookies);
        $sessionCookie = @json_decode(\RightNow\Api::ver_ske_decrypt(urldecode($sessionCookies[1][1])));
        $this->assertEqual($sessionCookie->f->i, 'spooooon');
    }

    function testGetNewFormToken() {
        $originalToken = \RightNow\Utils\Framework::createTokenWithExpiration(1);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/getNewFormToken',
            array('post' => "formToken=$originalToken")
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->newToken);
        $this->assertIsA($response->newToken, 'string');
        $this->assertNotEqual($response->newToken, $originalToken);

        $tokenIdentifier=2;
        $originalToken = \RightNow\Utils\Framework::createTokenWithExpiration($tokenIdentifier);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/getNewFormToken',
            array('post' => "formToken=$originalToken&tokenIdentifier=$tokenIdentifier")
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->newToken);
        $this->assertIsA($response->newToken, 'string');
        $this->assertNotEqual($response->newToken, $originalToken);
    }

    function testCheckForExistingContact() {
        // Existing contact
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/checkForExistingContact',
            array(
                'post' => sprintf("email=%s&contactToken=%s",
                    "mat@indigenous.example.invalid",
                    \RightNow\Utils\Framework::createTokenWithExpiration(1)
                ),
                'cookie' => 'cp_session=' . get_instance()->sessionCookie,
            )
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->message);
        $this->assertIsA($response->message, 'string');
        $this->assertTrue(Text::stringContains($response->message, "Email address already in use"));

        // Nonexistent contact
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/checkForExistingContact',
            array('post' => sprintf("email=%s&contactToken=%s",
                    "chuckb@bball3.invalid",
                    \RightNow\Utils\Framework::createTokenWithExpiration(1)
                ),
                'cookie' => 'cp_session=' . get_instance()->sessionCookie,
            )
        ));

        $this->assertNotNull($response);
        $this->assertFalse($response);

        // 180808-000244 don't look up by username
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/checkForExistingContact',
            array('post' => sprintf("login=%s&contactToken=%s",
                    "slatest",
                    \RightNow\Utils\Framework::createTokenWithExpiration(1)
                ),
                'cookie' => 'cp_session=' . get_instance()->sessionCookie,
            )
        ));

        $this->assertNotNull($response);
        $this->assertFalse($response);
    }

    function testCreateSocialUser() {
        // create a Contact so we know it doesn't have a CommunityUser
        $contact = new Connect\Contact();
        // use a unique username
        $contact->Login = 'TestUser_' . __FUNCTION__ . '_' . time();
        $contact->Email = "{$contact->Login}@invalid.org";
        $contact->FirstName = $contact->Login;
        $contact->LastName = $contact->Login;
        $contact->NewPassword = '';
        RightNow\Libraries\AbuseDetection::check();
        $contact->save();
        Connect\ConnectAPI::commit();

        // log in since createSocialUser() gets the contact ID from the session
        $cookies = Helper::logInUser($contact->Login);

        $displayName = "WesleyMouch";
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/createSocialUser',
            array('post' => "displayName=$displayName",
                'cookie' => "cp_session={$cookies['session']}; cp_profile={$cookies['profile']}")
        ));

        $this->assertTrue($response->success);
        $this->assertEqual(0, count($response->errors), print_r($response->errors, true));
        $this->assertEqual(0, count($response->warnings), print_r($response->warnings, true));
        Connect\ConnectAPI::commit();

        // verify that the new socialuser exists and has the right DisplayName + Contact
        $socialUser = Connect\CommunityUser::fetch($response->socialUserID);
        $this->assertNotNull($socialUser, "Could not find new CommunityUser");
        $this->assertEqual($displayName, $socialUser->DisplayName, "Wrong display name");

        // should get an error calling it again (e.g. called by a contact who already has a social profile)
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/createSocialUser',
            array('post' => "displayName=$displayName",
                'cookie' => "cp_session={$cookies['session']}; cp_profile={$cookies['profile']}")
        ));

        $this->assertEqual(0, $response->success);
        $this->assertEqual(1, count($response->errors), print_r($response->errors, true));
        $this->assertEqual(0, count($response->warnings), print_r($response->warnings, true));

        // clean up
        Helper::logOutUser($cookies['rawProfile'], $cookies['rawSession']);
    }

    function testGetChatQueueAndInformation() {
        $response = $this->makeRequest(
            '/ci/AjaxRequest/getChatQueueAndInformation',
            array('post' => sprintf("prod=%s&avail_type=%s&cacheable=%s",
                "1",
                "sessions",
                "true",
                "myCallback"
                ),
            'includeHeaders' => true
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: " . \RightNow\Utils\Url::getShortEufBaseUrl()));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));

        // 230105-000065-Deliver a friendly message when 500 errors occur when invalid Product/Category ids are passed it returns false
        $response = $this->makeRequest(
            '/ci/AjaxRequest/getChatQueueAndInformation',
            array('post' => sprintf("prod=%s&avail_type=%s&cacheable=%s",
                "324890",
                "sessions",
                "true",
                "myCallback"
                ),
                'includeHeaders' => true
            )
            );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Location: /app/error404"));

        // 180808-000240 do not display stack trace for invalid c_id
        $response = $this->makeRequest(
            '/ci/AjaxRequest/getChatQueueAndInformation',
            array('post' => sprintf("c_id=%s",
                "11111111111111"
                ),
            'includeHeaders' => true
            )
        );
        $this->assertNotNull($response);
        $this->assertFalse(Text::stringContains($response, "PHP Call Stack"), "Should not return stack trace for invalid c_id");
    }

    function xtestOkcsBrowseArticles() {
        $filters = '{
            "channelRecordID": {
                "value": "FAQ"
            },
            "limit": {
                "value": 100
            },
            "pageSize": {
                "value": 10
            },
            "truncate": {
                "value": 10
            },
            "browseAction": {
                "value": "paginate"
            },
            "browsePage": {
                "value": 1
            }
        }';

        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/getOkcsData',
            array('post' => sprintf('filters=%s', $filters))
        ));

        $this->assertNotNull($response);
        foreach ($response->articles as $article) {
            $this->assertNotNull($article->title);
            $this->assertFalse(strlen($article->title) > 13); // add 3 to truncate value for ellipsis '...'
            $this->assertNotNull($article->version);
            $this->assertNotNull($article->publishDate);
            $this->assertNotNull($article->createDate);
            $this->assertNotNull($article->owner->name);
            $this->assertNotNull($article->dateModified);
            $this->assertNotNull($article->published);
        }
    }

    function testCheckForValidFormToken() {
        // Check various functions calling into checkForValidFormToken
        $functionsToCheck = array('addOrRenewNotification'   => 'error',
                                  'deleteNotification'       => 'error',
                                  'addSocialSubscription'    => 'errors',
                                  'deleteSocialSubscription' => 'errors',
                                  'doLogin'                  => 'message',
                                  'submitAnswerFeedback'     => null);

        foreach($functionsToCheck as $fun => $err) {
            // Invalid formToken halts the call
            $result = json_decode($this->makeRequest(
                '/ci/ajaxRequest/' . $fun,
                array('post' => "filter_type=notaproduct&id=129&cid=1172")
            ));
            $this->assertEqual($err ? $result->{$err} : $result, Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG),
                               'The form token for ' . $fun . ' was improperly verified as a valid form token.');

            // Valid formToken passes the call through
            $result = json_decode($this->makeRequest(
                '/ci/ajaxRequest/' . $fun,
                array('post' => "filter_type=notaproduct&id=129&cid=1172&f_tok=" . \RightNow\Utils\Framework::createTokenWithExpiration(0),
                      'cookie' => 'cp_session=' . get_instance()->sessionCookie)
            ));
            $this->assertNotEqual($err ? $result->{$err} : $result, Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG),
                                  'The form token for ' . $fun . ' was improperly rejected.');
        }

        // Check checkForValidFormToken itself
        $argsToCheck = array('noFtok'      => null,
                             'emptyFtok'   => '',
                             'invalidFtok' => 'HiMom!');

        $checkForValidFormToken = $this->getMethod('checkForValidFormToken');
        foreach($argsToCheck as $name => $arg) {
            $_POST = array('filter_type' => 'notaproduct',
                           'id' => '129',
                           'cid' => '1172',
                           'f_tok' => $arg);
            $result = $checkForValidFormToken();
            $this->assertFalse($result, $name . " was improperly verified as a valid form token.");
        }

        $_POST = array('filter_type' => 'notaproduct',
                       'id' => '129',
                       'cid' => '1172',
                       'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0));
        $result = $checkForValidFormToken();
        $this->assertTrue($result, "A valid form token was improperly rejected.");
    }

    function testFormChallengeCheck()
    {
        $token = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequest/formChallengeCheck',
            array(
                'post' => "formToken=$token&challengeName=''",
                'cookie' => 'cp_login_start=1;cp_session=' . get_instance()->sessionCookie,
            )
        ));
        $this->assertIsA($response, 'stdClass');
        $this->assertTrue($response->status, true);
    }
}
