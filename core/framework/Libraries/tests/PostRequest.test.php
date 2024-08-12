<?php
\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\UnitTest\Helper,
    RightNow\Libraries\PostRequest,
    RightNow\Api;

class PostRequestTest extends CPTestCase {
    public $testingClass = 'Rightnow\Libraries\PostRequest';

    function __construct() {
        parent::__construct();
    }

    function tearDown() {
        PostRequest::clearMessages();
    }

    function getSessionData() {
        $session = \RightNow\Libraries\Session::getInstance(true);
        $sessionID = Api::generate_session_id();
        $time = time();
        $session->setSessionData(array('sessionID' => $sessionID));
        $session->setFlashData(array('filler_garbage' => 'garbage'));
        $urlSafeSessionID = urlencode(Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('session_id' => $sessionID))));
        $sessionString = '/session/' . base64_encode("/time/$time/sid/" . $urlSafeSessionID);
        $session->setSessionData(array('sessionString' => $sessionString));
        return array(
            Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('s' => array('s' => $sessionID, 'e' => $sessionString, 'l' => $time, 'i' => Api::intf_id())))),
            $sessionString,
        );
    }

    function testSendForm() {
        $result = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
            . urlencode(__FILE__) . '/' . __CLASS__ . '/sendFormProcessingIncidentCreateLoggedIn');
        $this->assertIdentical('', $result);

        $result = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
            . urlencode(__FILE__) . '/' . __CLASS__ . '/sendFormProcessingIncidentCreateAnonymous');
        $this->assertIdentical('', $result, "output: $result");

        $result = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
            . urlencode(__FILE__) . '/' . __CLASS__ . '/sendFormProcessingNoCookie');
        $this->assertIdentical('', $result, "output: $result");
    }

    function getPost($action, $successUrl, $formData, $contactID) {
        $data = array();
        foreach($formData as $name => $value) {
            $data[] = 'formData[' . $name . ']=' . urlencode($value);
        }
        $constraints = base64_encode(json_encode(array()));
        return implode('&', array_merge($data, array(
            'format[on_success_url]=' . urlencode($successUrl),
            'f_tok=' . urlencode(Framework::createCsrfToken(0, 1, $contactID, false)),
            'handler=' . urlencode('postRequest/sendForm'),
            'constraints=' . $constraints,
            'validationToken=' . Framework::createPostToken($constraints, $action, 'postRequest/sendForm')
        )));
    }

    function sendFormProcessingIncidentCreateLoggedIn() {
        //Incident Create
        $formData = array(
            'Incident.Subject' => 'How do I adjust my configuration?',
            'Incident.Threads' => 'Please'
        );
        list($sessionCookie, $sessionString) = $this->getSessionData();

        $cookies = Helper::logInUser();
        $options = array(
            'cookie' => 'cp_profile=' . $cookies['profile'] . ';cp_session=' . $cookies['session'],
            'post' => $this->getPost('/app/ask', '/app/ask_confirm', $formData, $cookies['rawProfile']->c_id),
            'justHeaders' => true
        );

        $result = $this->makeRequest('/app/ask', $options);
        Helper::logOutUser($cookies['rawProfile'], $cookies['rawSession']);
        //Should be redirected to the success page
        $this->assertTrue(Text::stringContains($result, '302 Moved Temporarily'));

        $expression = '@Location: /app/ask_confirm/i_id/(\d+)@';
        $matches = array();
        $this->assertEqual(1, preg_match($expression, $result, $matches));
        $this->logIn();
        $incident = $this->CI->model('incident')->get($matches[1])->result;
        $this->logOut();
        $this->assertIdentical($formData['Incident.Subject'], $incident->Subject);
        $this->assertIdentical($formData['Incident.Threads'], $incident->Threads[0]->Text);
    }

    function sendFormProcessingIncidentCreateAnonymous() {
        //Contact and Incident create not logged in
        $formData = array(
            'Contact.Emails.PRIMARY.Address' => 'canwebuildit.' . time() . '@yeswecan.com.invalid',
            'Incident.Subject' => 'How do I adjust my configuration?',
            'Incident.Threads' => 'Please'
        );
        list($sessionCookie, $sessionString) = $this->getSessionData();
        $options = array(
            'cookie' => 'cp_session=' . $sessionCookie,
            'post' => $this->getPost('/app/ask', '/app/ask_confirm', $formData, 0),
            'justHeaders' => true
        );

        $result = $this->makeRequest('/app/ask', $options);

        //Should be redirected to the ask_confirm page with a refno
        $this->assertTrue(Text::stringContains($result, 'Location: /app/ask_confirm/refno/'));
        $this->assertEqual(1, preg_match('@Set-Cookie:\s*cp_session=([^;]*);@', $result, $matches));
        $this->assertEqual(0, preg_match('@Set-Cookie:\s*cp_profile=([^;]*);@', $result, $matches));
    }

    function sendFormProcessingNoCookie() {
        //Contact and Incident create with no cookie
        $formData = array(
            'Contact.Emails.PRIMARY.Address' => 'canwebuildit2.' . time() . '@yeswecan.com.invalid',
            'Contact.Login' => 'testUser' . time(),
            'Incident.Subject' => 'How do I adjust my configuration?',
            'Incident.Threads' => 'Please'
        );
        list($sessionCookie, $sessionString) = $this->getSessionData();
        $options = array(
            'post' => $this->getPost('/app/ask', '/app/ask_confirm', $formData, 0),
            'justHeaders' => true
        );

        $result = $this->makeRequest('/app/ask' . $sessionString, $options);

        //Should be redirected to the error page
        $this->assertTrue(Text::stringContains($result, 'Location: /app/error/error_id/7/messages/'));
        $this->assertEqual(1, preg_match('@Set-Cookie:\s*cp_session=([^;]*);@', $result, $matches));
        $this->assertEqual(0, preg_match('@Set-Cookie:\s*cp_profile=([^;]*);@', $result, $matches));

        //Incident Update - Define me with question detail

        //Contact Create
        $formData = array(
            'Contact.Emails.PRIMARY.Address' => 'bob@builder.com' . time(),
            'Contact.Login' => 'BobIsBananas' . time(),
            'Contact.NewPassword' => 'password'
        );
        $options = array(
            'cookie' => 'cp_session=' . $sessionCookie,
            'post' => $this->getPost('/app/utils/create_account', '/app/account/overview', $formData, 0),
            'justHeaders' => true
        );

        $result = $this->makeRequest('/app/utils/create_account', $options);

        //Should be redirected to the account overview page and profile cookie set
        $this->assertTrue(Text::stringContains($result, 'Location: /app/account/overview'));
        $this->assertEqual(1, preg_match('@Set-Cookie:\s*cp_profile=([^;]*);@', $result, $matches));

        //Contact Update - Define me with profile page
    }

    function testSendFormInvalid() {
        $getPost = function($successUrl, $formData, $contactID) {
            $data = array();
            foreach($formData as $name => $value) {
                $data[] = 'formData[' . $name . ']=' . urlencode($value);
            }
            return implode('&', array_merge($data, array(
                'format[on_success_url]=' . urlencode($successUrl),
                'format[handler]=' . urlencode('postRequest/sendForm'),
                'constraints=' . urlencode('garbage'),
                'f_tok=' . urlencode(Framework::createCsrfToken(0, 1, $contactID, false))
            )));
        };

        $formData = array(
            'Incident.Subject' => 'How do I adjust my configuration?',
            'Incident.Threads' => 'Please'
        );
        $result = $this->makeRequest('/app/ask', array('post' => $getPost('/app/ask_confirm', $formData, 0)));

        //Should stay on the current page
        $this->assertFalse(Text::stringContains($result, '302 Moved Temporarily'));
        //TODO: $this->assertTrue(Text::stringContains($result, 'form constraints are not valid'));

        $getPost = function($successUrl, $formData, $contactID) {
            $data = array();
            foreach($formData as $name => $value) {
                $data[] = 'formData[' . $name . ']=' . urlencode($value);
            }
            return implode('&', array_merge($data, array(
                'format[on_success_url]=' . urlencode($successUrl),
                'format[handler]=' . urlencode('postRequest/sendForm'),
                'constraints=' . urlencode('{"Contact.Emails.PRIMARY.Address": {"required": true, "type": "email"}}'),
                'f_tok=' . urlencode(Framework::createCsrfToken(0, 1, $contactID, false))
            )));
        };

        $formData = array(
            'Incident.Subject' => 'How do I adjust my configuration?',
            'Incident.Threads' => 'Please'
        );
        $result = $this->makeRequest('/app/ask', array('post' => $getPost('/app/ask_confirm', $formData, 0)));

        //Should stay on the current page
        $this->assertFalse(Text::stringContains($result, '302 Moved Temporarily'));
        //TODO: $this->assertTrue(Text::stringContains($result, 'Contact.Emails.PRIMARY.Address - error finding Contact.Emails.PRIMARY.Address in fields during constraint processing'));

        $formData = array(
            'Contact.Emails.PRIMARY.Address' => '',
            'Incident.Subject' => 'How do I adjust my configuration?',
            'Incident.Threads' => 'Please'
        );
        $result = $this->makeRequest('/app/ask', array('post' => $getPost('/app/ask_confirm', $formData, 0)));

        //Should stay on the current page
        $this->assertFalse(Text::stringContains($result, '302 Moved Temporarily'));
        //TODO: $this->assertTrue(Text::stringContains($result, 'Contact.Emails.PRIMARY.Address - is required, but no value was provided'));

        $formData = array(
            'Contact.Emails.PRIMARY.Address' => 'bob',
            'Incident.Subject' => 'How do I adjust my configuration?',
            'Incident.Threads' => 'Please'
        );
        $result = $this->makeRequest('/app/ask', array('post' => $getPost('/app/ask_confirm', $formData, 0)));

        //Should stay on the current page
        $this->assertFalse(Text::stringContains($result, '302 Moved Temporarily'));
        //TODO: $this->assertTrue(Text::stringContains($result, 'Contact.Emails.PRIMARY.Address - is not a valid email address'));
    }

    function testSubmitAnswerRating() {
        $getPost = function($action, $answerRating = 2, $optionsCount = 2, $threshold = 1) {
            $constraints = base64_encode(json_encode(array()));

            return implode('&', array(
                'answerRating=' . $answerRating,
                'f_tok=' . urlencode(Framework::createTokenWithExpiration(0)),
                'answerFeedback[OptionsCount]=' . $optionsCount,
                'answerFeedback[Threshold]=' . $threshold,
                'format[on_success_url]=' . urlencode('/app/answers/submit_feedback'),
                'handler=' . urlencode('postRequest/submitAnswerRating'),
                'constraints=' . $constraints,
                'validationToken=' . Framework::createPostToken($constraints, $action, 'postRequest/submitAnswerRating')
            ));
        };

        // rating 1, options 3, threshold 1
        $options = array('justHeaders' => true, 'post' => $getPost('/app/answers/detail/a_id/52', 1, 3, 1));
        $result = $this->makeRequest('/app/answers/detail/a_id/52', $options);
        $this->assertTrue(Text::stringContains($result, '302 Moved Temporarily'));
        $this->assertTrue(Text::stringContains($result, 'Location: /app/answers/submit_feedback/a_id/52/options_count/3/threshold/1/rating/1'));

        //Test without a separate request
        $method = $this->getMethod('submitAnswerRating');
        $originalPost = $_POST;

        //No POST data
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array('Your request could not be completed at this time. Please try again later.'))
        ), PostRequest::getMessages());
        PostRequest::clearMessages();

        //Set a URL parameter for the below tests
        $this->addUrlParameters(array('a_id' => 52));

        // No rating
        $_POST = array(
            'answerFeedback' => array('OptionsCount' => 2, 'Threshold' => 1)
        );
        $this->assertFalse($method());
        $errors = PostRequest::getMessages();
        $this->assertTrue(is_array($errors['error']));
        $this->assertTrue(is_string($errors['error'][''][0]));
        PostRequest::clearMessages();

        //Rating is 0
        $_POST = array(
            'answerRating' => 0,
            'answerFeedback' => array('OptionsCount' => 2, 'Threshold' => 1)
        );
        $this->assertFalse($method());
        $errors = PostRequest::getMessages();
        $this->assertTrue(is_array($errors['error']));
        $this->assertTrue(is_string($errors['error'][''][0]));
        PostRequest::clearMessages();

        //Rating is less than 0
        $_POST = array(
            'answerRating' => -3,
            'answerFeedback' => array('OptionsCount' => 2, 'Threshold' => 1)
        );
        $this->assertFalse($method());
        $errors = PostRequest::getMessages();
        $this->assertTrue(is_array($errors['error']));
        $this->assertTrue(is_string($errors['error'][''][0]));
        PostRequest::clearMessages();

        // rating 2, options 2, threshold 1
        $_POST = array(
            'answerRating' => 2,
            'answerFeedback' => array('OptionsCount' => 2, 'Threshold' => 1)
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'info' => array('' => array('Your feedback was submitted successfully.'))
        ), PostRequest::getMessages());
        PostRequest::clearMessages();

        // rating 5, options 5, threshold 1
         $_POST = array(
            'answerRating' => 5,
            'answerFeedback' => array('OptionsCount' => 5, 'Threshold' => 1)
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'info' => array('' => array('Your feedback was submitted successfully.'))
        ), PostRequest::getMessages());
        PostRequest::clearMessages();

        // rating 5, options 3, threshold 1
         $_POST = array(
            'answerRating' => 5,
            'answerFeedback' => array('OptionsCount' => 3, 'Threshold' => 1)
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array('Your request could not be completed at this time. Please try again later.'))
        ), PostRequest::getMessages());
        PostRequest::clearMessages();

        //Reset the above URL parameter
        $this->restoreUrlParameters();
        $_POST = $originalPost;
    }

    function testSubmitFeedback() {
        $getPost = function($action, $email = 'test@test.invalid', $message = 'Message here') {
            $constraints = base64_encode(json_encode(array()));

            return implode('&', array(
                'formData[Contact.Emails.PRIMARY.Address]='. urlencode($email),
                'formData[Incident.Threads]=' . urlencode($message),
                'f_tok=' . urlencode(Framework::createTokenWithExpiration(0)),
                'answerFeedback[Rating]=1',
                'answerFeedback[AnswerId]=52',
                'answerFeedback[OptionsCount]=2',
                'answerFeedback[Threshold]=1',
                'format[on_success_url]=' . urlencode('/app/answers/detail'),
                'handler=' . urlencode('postRequest/submitFeedback'),
                'constraints=' . $constraints,
                'validationToken=' . Framework::createPostToken($constraints, $action, 'postRequest/submitFeedback')
            ));
        };

        //A successful submission should redirect to the detail page with a messages structure in the URL
        $options = array('justHeaders' => true, 'post' => $getPost('/app/answers/detail/a_id/52'));
        $result = $this->makeRequest('/app/answers/detail/a_id/52', $options);
        $this->assertTrue(Text::stringContains($result, '302 Moved Temporarily'));
        $this->assertTrue(Text::stringContains($result, 'Location: /app/answers/detail/a_id/52/messages/eyJtZXNzYWdlIjoiWW91ciBmZWVkYmFjayB3YXMgc3VibWl0dGVkIHN1Y2Nlc3NmdWxseS4iLCJ0eXBlIjoiaW5mbyIsImZpZWxkIjoiIn0='));

        //Test without a separate request
        $method = $this->getMethod('submitFeedback');
        $originalPost = $_POST;

        // complete data, but Rating doesn't meet constraints
        $_POST = array(
            'answerFeedback' => array(
                'Rating' => 15,
                'AnswerId' => 52,
                'OptionsCount' => 5,
                'Threshold' => 4
            ),
            'formData' => array(
                'Incident.Threads' => 'test%20feedback'
            ),
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array('Your request could not be completed at this time. Please try again later.'))
        ), PostRequest::getMessages());
        PostRequest::clearMessages();

        // cleanup
        $_POST = $originalPost;
    }

    function testDoLogin() {
        $result = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/'
            . urlencode(__FILE__) . '/' . __CLASS__ . '/doLogin');
        $this->assertSame('', $result);
    }

    function doLogin() {
        $getPost = function($action, $password = '', $url = 'app/ask', $params = '') {
            $constraints = base64_encode(json_encode(array()));

            return implode('&', array(
                'Contact_Login=slatest',
                "Contact_Password=$password",
                'f_tok=' . urlencode(Framework::createTokenWithExpiration(0)),
                'format[on_success_url]=' . urlencode($url),
                'format[add_params_to_url]=' . urlencode($params),
                'handler=' . urlencode('postRequest/doLogin'),
                'constraints=' . $constraints,
                'validationToken=' . Framework::createPostToken($constraints, $action, 'postRequest/doLogin')
            ));
        };

        $options = array('cookie' => 'cp_login_start=true', 'justHeaders' => true, 'post' => $getPost('/app/home'));

        $result = $this->makeRequest('/app/home', $options);
        $this->assertTrue(Text::stringContains($result, '302 Moved Temporarily'));
        $this->assertTrue(Text::stringContains($result, 'Location: /app/ask'));

        $options['post'] = $getPost('/app/home', '', 'app/ask', 'p/1/c/2');
        $result = $this->makeRequest('/app/home', $options);
        $this->assertTrue(Text::stringContains($result, '302 Moved Temporarily'));
        $this->assertTrue(Text::stringContains($result, 'Location: /app/ask/p/1/c/2'));

        $options['post'] = $getPost('/app/home', 'INVALIDPASSWORD');
        $result = $this->makeRequest('/app/home', $options);
        $this->assertTrue(Text::stringContains($result, '200 OK'));

        $method = $this->getMethod('doLogin');
        $_POST['Contact_Login'] = "abc";
        $_POST['format[on_success_url]'] = '/app/home';
        $this->assertFalse($method());
        $messages = PostRequest::getMessages();
        $this->assertTrue(is_array($messages['error']));
        $this->assertTrue(is_array($messages['error']['']));
        $this->assertTrue(is_string($messages['error'][''][0]));
        unset($_POST['Contact_Login']);
        unset($_POST['format[on_success_url]']);
    }

    function testEmailCredentials() {
        $this->downgradeErrorReporting();

        // incomplete POST data
        PostRequest::clearMessages();
        $method = $this->getMethod('emailCredentials');
        $this->assertFalse($method());
        $messages = PostRequest::getMessages();
        $this->assertFalse(is_array($messages['info']));
        $this->assertTrue(is_array($messages['error']));
        $this->assertTrue(is_array($messages['error']['']));
        $this->assertTrue(is_string($messages['error'][''][0]));

        // blank username
        PostRequest::clearMessages();
        $_POST['emailCredentials'] = array(
            'type'  => "password",
            'value' => ""
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array('A username is required.'))
        ), PostRequest::getMessages());

        // username with space
        PostRequest::clearMessages();
        $_POST['emailCredentials'] = array(
            'type'  => "password",
            'value' => "some user"
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array('Username must not contain spaces.'))
        ), PostRequest::getMessages());

        // username with double quote
        PostRequest::clearMessages();
        $_POST['emailCredentials'] = array(
            'type'  => "password",
            'value' => '"someuser'
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array('Username must not contain double quotes.'))
        ), PostRequest::getMessages());

        // username with <
        PostRequest::clearMessages();
        $_POST['emailCredentials'] = array(
            'type'  => "password",
            'value' => '<someuser'
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array("Username must not contain either '<' or '>'"))
        ), PostRequest::getMessages());

        // username with >
        PostRequest::clearMessages();
        $_POST['emailCredentials'] = array(
            'type'  => "password",
            'value' => 'someuser>'
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array("Username must not contain either '<' or '>'"))
        ), PostRequest::getMessages());

        // blank email address
        PostRequest::clearMessages();
        $_POST['emailCredentials'] = array(
            'type'  => "username",
            'value' => ""
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array("An email address is required."))
        ), PostRequest::getMessages());

        // invalid email address
        PostRequest::clearMessages();
        $_POST['emailCredentials'] = array(
            'type'  => "username",
            'value' => "adsfadsf"
        );
        $this->assertFalse($method());
        $this->assertIdentical(array(
            'error' => array('' => array("Email is not valid"))
        ), PostRequest::getMessages());

        // complete POST data
        PostRequest::clearMessages();
        $_POST['emailCredentials'] = array(
            'type'  => "username",
            'value' => "user@email.null"
        );
        $this->assertFalse($method());
        $messages = PostRequest::getMessages();
        $this->assertFalse(is_array($messages['error']));
        $this->assertTrue(is_array($messages['info']));
        $this->assertTrue(is_array($messages['info']['']));
        $this->assertTrue(is_string($messages['info'][''][0]));

        // cleanup
        unset($_POST['emailCredentials']);
        $this->CI->session->setSessionData(array('previouslySeenEmail' => null));
        $this->restoreErrorReporting();
    }

    function testResetPassword() {
        $getPost = function($action, $c_id=1156, $url='/app/utils/submit/password_changed') {
            $constraints = base64_encode(json_encode(array()));

            return implode('&', array(
                "formData[Contact.NewPassword]=",
                "formData[Contact.NewPassword#Validation]=",
                'pw_reset=' . \RightNow\Api::ver_ske_encrypt_urlsafe($c_id . '/' . strtotime('1/1/2020')),
                'f_tok=' . urlencode(Framework::createTokenWithExpiration(0)),
                'format[on_success_url]=' . urlencode($url),
                'handler=' . urlencode('postRequest/resetPassword'),
                'constraints=' . $constraints,
                'validationToken=' . Framework::createPostToken($constraints, $action, 'postRequest/resetPassword')
            ));
        };

        // incomplete POST data
        PostRequest::clearMessages();
        $method = $this->getMethod('resetPassword');
        $this->assertFalse($method());
        $messages = PostRequest::getMessages();
        $this->assertFalse(is_array($messages['info']));
        $this->assertTrue(is_array($messages['error']));
        $this->assertTrue(is_array($messages['error']['']));
        $this->assertTrue(is_string($messages['error'][''][0]));

        // invalid/missing pw_reset value
        PostRequest::clearMessages();
        $_POST['formData'] = array(
            'Contact.NewPassword'               => '',
            'Contact.NewPassword#Validation'    => '',
        );
        $_POST['format']['on_success_url'] = '';
        $this->assertFalse($method());
        $messages = PostRequest::getMessages();
        $this->assertFalse(is_array($messages['info']));
        $this->assertTrue(is_array($messages['error']));
        $this->assertTrue(is_array($messages['error']['']));
        $this->assertTrue(is_string($messages['error'][''][0]));

        // successful request
        \Rnow::updateConfig("CP_FORCE_PASSWORDS_OVER_HTTPS", 0);
        $options = array('justHeaders' => true, 'post' => $getPost('/app/account/reset_password'), 'cookie' => 'cp_session=' . get_instance()->sessionCookie);
        $result = $this->makeRequest('/app/account/reset_password', $options);
        $this->assertTrue(Text::stringContains($result, '200 OK'));

        // clean up
        \Rnow::updateConfig("CP_FORCE_PASSWORDS_OVER_HTTPS", 1);
        unset($_POST['formData']);
        unset($_POST['format']);
    }

    function testGetRedirectUrl() {
        $method = $this->getMethod('getRedirectUrl');

        $expected = '/';
        $this->assertEqual($expected, $method());
        $this->assertEqual($expected, $method(null));
        $this->assertEqual($expected, $method(null, null));

        $expected = '/app/home';
        $this->assertEqual($expected, $method('/app/home'));
        $this->assertEqual($expected, $method('app/home'));
        $this->assertEqual($expected, $method('app/home/'));
        $this->assertEqual($expected, $method('/app/home/'));
        $this->assertEqual($expected, $method('app/home', null));
        $this->assertEqual($expected, $method(null, 'app/home'));

        $expected = '/app/home/p/1/c/2';
        $this->assertEqual($expected, $method('/app/home', 'p/1/c/2'));
        $this->assertEqual($expected, $method('/app/home', '/p/1/c/2'));
        $this->assertEqual($expected, $method('/app/home', '/p/1/c/2/'));
        $this->assertEqual($expected, $method('/app/home', '/p/1/c/2/'));
        $this->assertEqual($expected, $method('/app/home///', '/p/1/c/2/'));
    }

    function testGetFieldProperties() {
        $method = $this->getMethod('getFieldProperties');

        //All of the standard values should be moved into the correct place on the resultant object
        //Also, unsupported constraints and unused labels should be removed
        $result = $method('Contact.Login', 'myLogin', array(
            'minValue' => 10,
            'unsupportedConstraint' => 15,
            'labels' => array(
                'label_error' => 'shouldDisplayInError',
                'label_input' => 'shouldBeOverridden',
                'label_validation' => 'unusedWithoutRequireValidation'
            )
        ));
        $expected = array(
            'name' => 'Contact.Login',
            'value' => 'myLogin',
            'required' => false,
            'constraints' => array('minValue' => 10),
            'label' => 'shouldDisplayInError',
        );
        $this->assertIdentical($expected, $result);

        //Pass through requireValidation, requireCurrent and the associated label
        $result = $method('Contact.CustomFields.c.text1', 'testValue', array(
            'name' => 'Contact.CustomFields.c.text1',
            'required' => true,
            'requireValidation' => true,
            'requireCurrent' => true,
            'labels' => array(
                'label_input' => 'testInputLabel',
                'label_validation' => 'usedWithRequireValidation'
            )
        ));
        $expected = array(
            'name' => 'Contact.CustomFields.c.text1',
            'value' => 'testValue',
            'required' => true,
            'constraints' => array(),
            'requireCurrent' => true,
            'requireValidation' => true,
            'label' => 'testInputLabel',
            'labelValidation' => 'usedWithRequireValidation',
        );
        $this->assertIdentical($expected, $result);

        //Checkbox field should have it's value changed from null to false
        $result = $method('Contact.CustomFields.c.yesno1', null, array(
            'isCheckbox' => true
        ));
        $expected = array(
            'name' => 'Contact.CustomFields.c.yesno1',
            'value' => false,
            'required' => false,
            'constraints' => array(),
        );
        $this->assertIdentical($expected, $result);
    }

    function testTransformBasicFields() {
        $method = $this->getMethod('transformBasicFields');

        //Test with standard fields and no constraints
        $fields = array(
            'Contact.Emails.PRIMARY.Address' => 'jim@joe.co',
            'Contact.Login' => 'jimIsBananas',
            'Contact.NewPassword' => ' password',
            'Contact.Name.First' => 'Jim',
            'Contact.Name.Last' => 'Joe',
            'Incident.Subject' => 'Question...',
            'Incident.Threads' => '    Seriously, what is this?    ',
            'Incident.Category' => 128,
            'Contact.Product' => 1
        );

        $result = $method($fields, array());
        foreach($result as $field) {
            $this->assertTrue(isset($fields[$field->name]));
            $this->assertIdentical($fields[$field->name], $field->value);
            $this->assertFalse($field->required);
            $this->assertIdentical(array(), $field->constraints);
        }

        //Test with date field pieces
        $fields = array(
            //In order / all inputs
            'Incident.CustomFields.c.date1#year' => 1999,
            'Incident.CustomFields.c.date1#month' => 11,
            'Incident.CustomFields.c.date1#day' => 12,
            'Incident.CustomFields.c.date1#hour' => 4,
            'Incident.CustomFields.c.date1#minute' => 16,

            //Out of order, no time fields. Since time is not used, the time field should be passed through as 00s
            'Incident.CustomFields.c.date2#month' => 11,
            'Incident.CustomFields.c.date2#day' => 12,
            'Incident.CustomFields.c.date2#year' => 1999,

            //Out of order / invalid input. Since time is not used, the time field should be passed through as 00s
            'Incident.CustomFields.c.date3#year' => 1999,

            //Dates with only the minute and hour field populated should be passed through as an invalid date
            'Incident.CustomFields.c.date4#year' => '',
            'Incident.CustomFields.c.date4#minute' => 16,
            'Incident.CustomFields.c.date4#hour' => 5,

            //Dates with only the minute or hour field populated should be passed through as an invalid date and invalid time
            'Incident.CustomFields.c.date5#year' => '',
            'Incident.CustomFields.c.date5#minute' => 16,
            'Incident.CustomFields.c.date5#hour' => '',

            'Incident.CustomFields.c.date6#year' => '',
            'Incident.CustomFields.c.date6#hour' => 16,
            'Incident.CustomFields.c.date6#minute' => '',

            //Date with only minute or hour shown will pass time values through as 00s
            'Incident.CustomFields.c.date7#year' => 1988,
            'Incident.CustomFields.c.date7#hour' => 16,

            'Incident.CustomFields.c.date8#year' => 1988,
            'Incident.CustomFields.c.date8#minute' => 16
        );

        $expected = array(
            'Incident.CustomFields.c.date1' => (object)array('name' => 'Incident.CustomFields.c.date1', 'value' => '1999-11-12 4:16:00', 'required' => false, 'constraints' => array()),
            'Incident.CustomFields.c.date2' => (object)array('name' => 'Incident.CustomFields.c.date2', 'value' => '1999-11-12 00:00:00', 'required' => false, 'constraints' => array()),
            'Incident.CustomFields.c.date3' => (object)array('name' => 'Incident.CustomFields.c.date3', 'value' => '1999-13-32 00:00:00', 'required' => false, 'constraints' => array()),
            'Incident.CustomFields.c.date4' => (object)array('name' => 'Incident.CustomFields.c.date4', 'value' => '-1-13-32 5:16:00', 'required' => false, 'constraints' => array()),
            'Incident.CustomFields.c.date5' => (object)array('name' => 'Incident.CustomFields.c.date5', 'value' => '-1-13-32 25:16:00', 'required' => false, 'constraints' => array()),
            'Incident.CustomFields.c.date6' => (object)array('name' => 'Incident.CustomFields.c.date6', 'value' => '-1-13-32 16:61:00', 'required' => false, 'constraints' => array()),
            'Incident.CustomFields.c.date7' => (object)array('name' => 'Incident.CustomFields.c.date7', 'value' => '1988-13-32 00:00:00', 'required' => false, 'constraints' => array()),
            'Incident.CustomFields.c.date8' => (object)array('name' => 'Incident.CustomFields.c.date8', 'value' => '1988-13-32 00:00:00', 'required' => false, 'constraints' => array())
        );

        $result = $method($fields, array());
        $this->assertIdentical($expected, $result);

        //Test with currentPassword and validation hashes generated by the PasswordInput or TextInput require_validation attribute
        $passwordField = 'Contact.NewPassword';
        $emailField = 'Contact.Emails.PRIMARY.Address';
        $fields = array(
            "$passwordField" => 'test',
            "$passwordField#currentpassword" => 'test2',
            "$passwordField#validation" => 'test3',
            "$emailField" => 'jim@joe.co',
            "$emailField#validation" => 'jim@joe.valid'
        );
        $result = $method($fields, array());

        $this->assertTrue(isset($result[$passwordField]));
        $this->assertIdentical('test', $result[$passwordField]->value);
        $this->assertIdentical('test2', $result[$passwordField]->currentValue);
        $this->assertIdentical('test3', $result[$passwordField]->validation);

        $this->assertTrue(isset($result[$emailField]));
        $this->assertIdentical('jim@joe.co', $result[$emailField]->value);
        $this->assertIdentical('jim@joe.valid', $result[$emailField]->validation);

        //Test that constraints not found in fields are added
        $fieldConstraints = array(
            //If the field is a checkbox and not provided, it should have a value of false
            'Incident.CustomFields.c.yesno1' => array(
                'required' => true,
                'isCheckbox' => true,
            ),
            //Otherwise, null
            'Incident.CustomFields.c.otherField' => array(
                'required' => false,
            ),
        );
        $expected = array(
            'Incident.CustomFields.c.yesno1' => (object)array('name' => 'Incident.CustomFields.c.yesno1', 'value' => false, 'required' => true, 'constraints' => array()),
            'Incident.CustomFields.c.otherField' => (object)array('name' => 'Incident.CustomFields.c.otherField', 'value' => null, 'required' => false, 'constraints' => array()),
        );
        $result = $method(array(), $fieldConstraints);
        $this->assertIdentical($expected, $result);

        //Fields which have hashes, but don't have valid transform functions should be left intact
        $fields = array(
            'Incident.Login#test1' => '123',
            'Incident.Login#test3' => 'abc',
            'Incident.Login#test2#test4' => '456'
        );

        $result = $method($fields, array());
        foreach($result as $field) {
            $this->assertTrue(isset($fields[$field->name]));
            $this->assertIdentical($fields[$field->name], $field->value);
        }
    }
}

class MessageApiTests extends CPTestCase {
    public $testingClass = 'Rightnow\Libraries\PostRequest';

    function tearDown() {
        PostRequest::clearMessages();
    }

    function testAddAndGetMessages() {
        $this->assertIdentical(array(), PostRequest::getMessages());

        PostRequest::addMessage('hiya', 'info');
        PostRequest::addMessage('uh-oh');
        PostRequest::addMessage('blah', 'CustomType');

        $field = 'Incident.Threads';
        PostRequest::addMessage('is required', 'error', $field);
        PostRequest::addMessage('is invalid', 'error', $field);
        PostRequest::addMessage('is wonky', 'error', $field);
        PostRequest::addMessage('fix it', 'error', $field);

        $expected = array('info' => array(
            '' => array('hiya'),
            ),
            'error' => array(
                '' => array('uh-oh'),
                'Incident.Threads' => array(
                    'is required',
                    'is invalid',
                    'is wonky',
                   'fix it',
                ),
            ),
            'CustomType' => array(
                '' => array('blah'),
            ),
        );
        $this->assertIdentical($expected, PostRequest::getMessages());

        try {
            PostRequest::addMessage(array('Not a' => 'string'));
            fail('Expected exception did not occur');
        }
        catch (\Exception $e) {
            $this->assertEqual("'message' is not a string", $e->getMessage());
        }

        try {
            PostRequest::addMessage('a message', array('Not a' => 'string'));
            fail('Expected exception did not occur');
        }
        catch (\Exception $e) {
            $this->assertEqual("'type' is not a string", $e->getMessage());
        }

        try {
            PostRequest::addMessage('a message', 'a type', array('Not a' => 'string'));
            fail('Expected exception did not occur');
        }
        catch (\Exception $e) {
            $this->assertEqual("'field' is not a string", $e->getMessage());
        }
    }

    function testAddMessagesFromRequest() {
        $this->assertIdentical(array(), PostRequest::getMessages());
        $addMessagesFromRequest = $this->getMethod('addMessagesFromRequest', true);
        $getMessageArray = $this->getMethod('getMessageArray', true);
        $encodedMessages = $getMessageArray('an error', 'error', 'a field', true);
        $addMessagesFromRequest($encodedMessages);
        $expected = array(
            'error' => array(
                'a field' => array('an error'),
            ),
        );
        $this->assertIdentical($expected, PostRequest::getMessages());

        $encodedMessages = $getMessageArray('an error with <script>alert("X\'SS");</script>', 'error', 'a field with <script>alert("XSS\'");</script>', true);
        $addMessagesFromRequest($encodedMessages);
        $expected = array(
            'error' => array(
                'a field' => array('an error'),
                'a field with &lt;script&gt;alert(&quot;XSS&#039;&quot;);&lt;/script&gt;' => array('an error with &lt;script&gt;alert(&quot;X&#039;SS&quot;);&lt;/script&gt;'),
            ),
        );
        $this->assertIdentical($expected, PostRequest::getMessages());

        PostRequest::clearMessages();
        $addMessagesFromRequest(array('Not a' => 'hash'));
        $expected = array(
            'error' => array(
                'REQUEST ERROR' => array('Invalid messages hash'),
            ),
        );
        $this->assertIdentical($expected, PostRequest::getMessages());
    }

    function testEncodeDecodeMessages() {
        $encode = $this->getMethod('encodeMessages', true);
        $decode = $this->getMethod('decodeMessages', true);
        $messages = array(
            array('message' => 'is required', 'type' => 'error', 'field' => 'Incident.Threads'),
            array('message' => 'not an integer', 'type' => 'error', 'field' => 'Tablename.IntegerField'),
            array('message' => 'Try Again..', 'type' => '', 'field' => ''),
        );
        $encoded = $encode($messages);
        $decoded = $decode($encoded);
        $expected = array(
            (object) array('message' => 'is required', 'type' => 'error', 'field' => 'Incident.Threads'),
            (object) array('message' => 'not an integer', 'type' => 'error', 'field' => 'Tablename.IntegerField'),
            (object) array('message' => 'Try Again..', 'type' => '', 'field' => ''),
        );
        $this->assertIdentical($expected, $decoded);
    }
}
