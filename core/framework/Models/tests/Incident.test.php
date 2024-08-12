<?php
use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\UnitTest\Helper as TestHelper;

TestHelper::loadTestedFile(__FILE__);

class IncidentModelTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Incident';
    protected $hookEndpointClass = __CLASS__;
    protected $hookEndpointFilePath = __FILE__;

    function __construct() {
        parent::__construct();
        $this->incidentID = 3;
        $this->user1 = (object) array(
            'login' => 'jerry@indigenous.example.com.invalid.070503.invalid',
            'email' => 'jerry@indigenous.example.com.invalid',
        );
        $this->user2 = (object) array(
            'login' => 'slatest',
            'email' => 'perpetualslacontactnoorg@invalid.com',
        );
        $this->maxLength = 240;
        $this->model = $this->CI->model('Incident');
    }

    function responseIsValid($response, $expectedReturn = 'object:RightNow\Connect\v1_4\Incident', $errorCount = 0, $warningCount = 0) {
        $status = true;
        $expectedType = object;
        $actualType = gettype($response);
        $expectedClass = 'RightNow\Libraries\ResponseObject';
        $actualClass = get_class($response);

        if (($expectedType !== $actualType) || ($expectedClass !== $actualClass)) {
            print("<strong>Expected response to be of type: '$expectedType', class: '$expectedClass'. Got type:'$actualType' class:'$actualClass'.</strong><br/>");
            $status = false;
        }

        list($expectedReturnType, $expectedReturnClass) = explode(':', $expectedReturn);
        $actualReturnType = gettype($response->result);
        $actualReturnClass = get_class($response->result) ?: null;
        if (($expectedReturnType !== $actualReturnType) || ($expectedReturnClass !== $actualReturnClass)) {
            print("<strong>Expected return to be of type: '$expectedReturnType' class: '$expectedReturnClass'. Got type: '$actualReturnType' class: '$actualReturnClass'.</strong><br/>");
            $status = false;
        }

        if (count($response->errors) !== $errorCount) {
            printf("<strong>Expected %d error(s), got %d</strong><br/>", $errorCount, count($response->errors));
            foreach($response->errors as $error) {
                print("&nbsp;&nbsp;&nbsp;&nbsp;{$error}<br/>");
            }
            $status = false;
        }
        if (count($response->warnings) !== $warningCount) {
            printf("<strong>Expected %d warning(s), got %d</strong><br/>", $warningCount, count($response->warnings));
            foreach($response->warnings as $warning) {
                print("&nbsp;&nbsp;&nbsp;&nbsp;{$warning}<br/>");
            }
            $status = false;
        }
        return $status;
    }

    function testBlank() {
        $response = $this->model->getBlank();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_object($response->result));
        $this->assertTrue(is_object($response->result->CustomFields));
        $this->assertTrue(is_object($response->result->CustomFields->c));
    }

    function testInvalidIncidentID() {
        $this->logIn();
        $invalidIDs = array(null, false, '', 0, 'abc', 'null', 'false');
        foreach($invalidIDs as $ID) {
            $response = $this->model->get($ID);
            $this->assertIdentical($response->errors[0]->externalMessage, "Invalid Incident ID: $ID");
            $this->assertNull($response->result);
        }
        $this->logOut();
    }

    function testGetBlank() {
        $response = $this->model->getBlank();
        $this->assertTrue($this->responseIsValid($response));
    }

    function testGet() {
        $response = $this->model->get($this->incidentID);
        if (Framework::isLoggedIn()) {
            $this->assertTrue($this->responseIsValid($response));
        }
        else {
            $this->assertIdentical($response->errors[0]->externalMessage, 'Your session has expired. Please login again to continue.');
        }
    }

    function testCreate() {

        $response = $this->model->create(array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'bananas'),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        $this->logout('');

        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'bananas'),
            'Incident.PrimaryContact' => 2,
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $this->assertIdentical('ejergan@latisan.example.invalid', $response->result->PrimaryContact->Emails[0]->Address);
        $this->assertIdentical('bananas', $response->result->Subject);

        $this->downgradeErrorReporting(); // Profile cookie is written out.
        $this->logIn($this->user1->login);
        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'Incident for Asset'),
            'Incident.Asset' => (object) array('value' => 8),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $this->assertIdentical(8, $response->result->Asset->ID);
        $this->restoreErrorReporting();

        $this->logOut();
        $subject = str_pad('bananas', $this->maxLength, 'bananas');

        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => "{$subject}IsNowTooLong"),
            'Incident.PrimaryContact' => $this->CI->model('Contact')->get(2)->result,
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(Text::stringContains($response->error, 'exceeds its size limit'));

        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => $subject),
            'Incident.PrimaryContact' => $this->CI->model('Contact')->get(2)->result,
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $this->assertIdentical('ejergan@latisan.example.invalid', $response->result->PrimaryContact->Emails[0]->Address);
        $this->assertIdentical($subject, $response->result->Subject);

        // Verify incident gets associated w/ logged-in user, but prevent log in from occurring.
        $profileData = $this->logIn($this->user2->login);
        $profileObject = $profileData->getValue($this->CI->session);
        $profileObject->disabled = true;
        $profileData->setValue($this->CI->session, $profileObject);

        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'MOAR bananas'),
            'Incident.Category' => (object) array('value' => 153),
            'Incident.Product' => (object) array('value' => null),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors), var_export($response->errors, true));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $this->assertIdentical($this->user2->email, $response->result->PrimaryContact->Emails[0]->Address);
        $this->assertIdentical('MOAR bananas', $response->result->Subject);
        $this->assertIdentical(153, $response->result->Category->ID);
        $this->assertNull($response->result->Product->ID);

        $this->logout();

        // Verify Incident create for empty thread & required flag set as false
        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'iphone'),
            'Incident.Threads' => (object) array('value' => '', 'required' => false),
            'Incident.PrimaryContact' => $this->CI->model('Contact')->get(2)->result,
        ), true);

        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));

        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'iphone'),
            'Incident.Threads' => (object) array('value' => null, 'required' => false),
            'Incident.PrimaryContact' => $this->CI->model('Contact')->get(101)->result,
        ), true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->logout();

        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'iphone'),
            'Incident.Threads' => (object) array('value' => '', 'required' => true),
            'Incident.PrimaryContact' => $this->CI->model('Contact')->get(101)->result,
        ), true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->logout();

        // QA 200428-000173 date and datetime custom field handling
        list($date1, $date2, $date3, $date4) = array('2001-01-01', '2002-02-02', '2003-03-03', '2004-04-04');
        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'bananas'),
            'Incident.PrimaryContact' => 2,
            'Incident.CustomFields.CO.FieldDttm' => (object) array('value' => $date1),
            'Incident.CustomFields.c.dttm1' => (object) array('value' => $date2),
            'Incident.CustomFields.CO.FieldDate' => (object) array('value' => $date3),
            'Incident.CustomFields.c.date1' => (object) array('value' => $date4),
        ), true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertEqual((new \DateTime($date1))->getTimestamp(), $response->result->CustomFields->CO->FieldDttm);
        $this->assertEqual((new \DateTime($date2))->getTimestamp(), $response->result->CustomFields->c->dttm1);
        $this->assertEqual($date3, $response->result->CustomFields->CO->FieldDate);
        $this->assertEqual($date4, $response->result->CustomFields->c->date1);
        $this->logout();
    }

    function testCreateWithResponseEmailPriorityPrimaryEmail() {
        $login = 'bananaSplit' . microtime(true);
        $email = 'banana@split.com.' . microtime(true);
        $contact = $this->CI->model('Contact')->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.Emails.1.Address' => (object) array('value' => $email . '.alt'),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last' => (object) array('value' => 'bananaLast'),
        ))->result;
        Connect\ConnectAPI::commit();

        $incident = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'MOAR bananas'),
            'Incident.PrimaryContact' => $contact->ID,
            // this field is labeled as primary, but contact_match will match on any of the email addresses
            'Contact.Emails.PRIMARY.Address' => (object) array('value' => $email),
        ))->result;

        $this->assertNotNull($incident);
        $this->assertTrue($incident->ID > 0);
        $this->assertSame(0, $incident->ResponseEmailAddressType->ID);

        // clean-up
        Connect\ConnectAPI::rollback();
        $this->destroyObject($contact);
        Connect\ConnectAPI::commit();
    }

    function testCreateWithResponseEmailPriorityAltEmail() {
        $login = 'bananaSplit' . microtime(true);
        $email = 'banana@split.com.' . microtime(true);
        $contact = $this->CI->model('Contact')->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email . '.primary'),
            'Contact.Emails.1.Address' => (object) array('value' => $email),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last' => (object) array('value' => 'bananaLast'),
        ))->result;
        Connect\ConnectAPI::commit();

        $incident = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'MOAR bananas'),
            'Incident.PrimaryContact' => $contact->ID,
            // this field is labeled as primary, but contact_match will match on any of the email addresses
            'Contact.Emails.PRIMARY.Address' => (object) array('value' => $email),
        ))->result;

        $this->assertNotNull($incident);
        $this->assertTrue($incident->ID > 0);
        $this->assertSame(1, $incident->ResponseEmailAddressType->ID);

        // clean-up
        Connect\ConnectAPI::rollback();
        $this->destroyObject($contact);
        Connect\ConnectAPI::commit();
    }

    function testCreateWithResponseEmailPriorityLoggedIn() {
        $this->logIn();

        $this->downgradeErrorReporting(); // Profile cookie is written out.
        $incident = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'MOAR bananas'),
        ))->result;
        $this->restoreErrorReporting();

        $this->assertNotNull($incident);
        $this->assertTrue($incident->ID > 0);
        $this->assertSame(0, $incident->ResponseEmailAddressType->ID);

        $this->logOut();
    }

    function testCreateSmartAssistant() {
        $originalConfigValue = \Rnow::updateConfig('KFAPI_SSS_ENABLED', true);
        $response = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/createSmartAssistant');
        \Rnow::updateConfig('KFAPI_SSS_ENABLED', $originalConfigValue);
        $this->assertNotNull($response);
    }

    function createSmartAssistant() {
        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'iphone and mms messaging'),
            'Incident.Threads' => (object) array('value' => 'iphone'),
            'Incident.PrimaryContact' => $this->CI->model('Contact')->get(101)->result,
            'Incident.CustomFields.CO.store' => (object) array('value' => false),
            'Incident.Category' => (object) array('value' => null),
            'Incident.Product' => (object) array('value' => 160),
        ), true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertIsA($response->result, 'array');
        $this->assertTrue(array_key_exists('suggestions', $response->result));
        $suggestions = $response->result['suggestions'];
        $this->assertSame($suggestions[0]['type'], 'AnswerSummary');
        $this->assertNull($suggestions[1]['type']);
        $this->assertNotNull($suggestions[0]['list'][0]['title']);
        $this->assertTrue(array_key_exists('canEscalate', $response->result));
        $this->assertTrue(array_key_exists('token', $response->result));
    }

    function testUpdate() {
        $return = $this->model->update('asdf', array());
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));

        $return = $this->model->update('1', array());
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));

        $subject = str_pad('bananas', $this->maxLength, 'bananas');
        $return = $this->model->update('1', array(
            'Incident.Subject' => (object) array('value' => $subject),
            'Incident.Thread' => (object) array('value' => 'banana'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));

        $this->logIn($this->user2->login);

        $return = $this->model->update('1', array(
            'Incident.Subject' => (object) array('value' => $subject),
            'Incident.Thread' => (object) array('value' => 'banana'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));

        $this->logIn($this->user1->login);

        $return = $this->model->update('1', array(
            'Incident.Subject' => (object) array('value' => "{$subject}IsNowTooLong"),
            'Incident.Threads.1' => (object) array('value' => 'banana'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(Text::stringContains($return->error, 'exceeds its size limit'));

        $return = $this->model->update('1', array(
            'Incident.Subject' => (object) array('value' => $subject),
            'Incident.Threads.1' => (object) array('value' => 'banana'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');

        $this->assertSame(0, count($return->warnings), var_export($return->warnings, true));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertSame($subject, $incident->Subject);
        $this->assertSame($this->user1->email, $incident->PrimaryContact->Emails[0]->Address);

        $this->logout();
        $this->logIn($this->user1->login);

        // Status set to 'updated'
        $return = $this->model->update('1', array(
            'Incident.StatusWithType.Status' => (object) array('value' => 0),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertSame(STATUS_UPDATED, $incident->StatusWithType->Status->ID);
        $this->assertSame($this->user1->email, $incident->PrimaryContact->Emails[0]->Address);

        // Status set to 'solved'
        $return = $this->model->update('1', array(
            'Incident.StatusWithType.Status' => (object) array('value' => 12),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertSame(STATUS_SOLVED, $incident->StatusWithType->Status->ID);
        $this->assertSame($this->user1->email, $incident->PrimaryContact->Emails[0]->Address);

        // Incident thread is required
        $return = $this->model->update('1', array(
            'Incident.StatusWithType.Status' => (object) array('value' => 0),
            'Incident.Threads' => (object) array('value' => '', 'required' => false),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(1, count($return->errors));

        // Status set back to 'updated' despite missing
        $return = $this->model->update('1', array(
            'Incident.Threads' => (object) array('value' => 'bananas'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertSame(STATUS_UPDATED, $incident->StatusWithType->Status->ID);
        $this->assertSame($this->user1->email, $incident->PrimaryContact->Emails[0]->Address);

        $this->logout();
    }

    function testSubmitFeedback() {
        Framework::removeCache('existingContactEmailunknown@mail.null');
        $return = $this->model->submitFeedback(1, 2, 2, 'test', 'message', null, 2);
        $this->assertTrue($this->isAnsFeedbackMsgBaseAllowed($return));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertTrue(Text::stringContains($incident->Subject, '1'));
        $this->assertTrue(Text::stringContains($incident->Subject, \RightNow\Utils\Config::getMessage(HELPFUL_LBL)));
        $this->assertSame('test', $incident->PrimaryContact->Name->First);
        $this->assertSame('unknown@mail.null', $incident->PrimaryContact->Emails[0]->Address);

        $this->setMockSession();
        $this->CI->session->expectCallCount('setSessionData', 10);
        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => 'foo@bar.com')));

        // Value of givenEmail may be null or false or ''
        Framework::removeCache('existingContactEmailunknown@mail.null');
        $return = $this->model->submitFeedback(1, 2, 2, 'test', 'message', false, 2);
        $this->assertTrue($this->isAnsFeedbackMsgBaseAllowed($return));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertTrue(Text::stringContains($incident->Subject, '1'));
        $this->assertTrue(Text::stringContains($incident->Subject, \RightNow\Utils\Config::getMessage(HELPFUL_LBL)));
        $this->assertSame('unknown@mail.null', $incident->PrimaryContact->Emails[0]->Address);

        Framework::removeCache('existingContactEmailunknown@mail.null');
        $return = $this->model->submitFeedback(1, 2, 2, 'test', 'message', '', 2);
        $this->assertTrue($this->isAnsFeedbackMsgBaseAllowed($return));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertTrue(Text::stringContains($incident->Subject, '1'));
        $this->assertTrue(Text::stringContains($incident->Subject, \RightNow\Utils\Config::getMessage(HELPFUL_LBL)));
        $this->assertSame('unknown@mail.null', $incident->PrimaryContact->Emails[0]->Address);

        // no-op due to rating
        $return = $this->model->submitFeedback(null, 5, 4, 'banana', 'message', 'foo@bar.com');
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->warnings));

        // no-op due to invalid email
        $return = $this->model->submitFeedback(null, 5, 4, 'banana', 'message', 'foosarcom');
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(1, count($return->errors));

        // site feedback
        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => 'foo@bar.invalid.com')));
        $return = $this->model->submitFeedback(null, 4, 5, 'banana', 'message', 'foo@bar.invalid.com');
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertSame(\RightNow\Utils\Config::getMessage(SITE_FEEDBACK_HDG), $incident->Subject);
        $this->assertSame('banana', $incident->PrimaryContact->Name->First);

        // Abuse
        $this->setIsAbuse();
        $expected = \RightNow\Utils\Config::getMessage(REQUEST_PERFORMED_SITE_ABUSIVE_MSG);
        $response = $this->model->submitFeedback(null, 4, 5, 'banana', 'message', 'foo@bar.invalid.com');
        $this->assertEqual($expected, $response->errors[0]->externalMessage);
        $this->clearIsAbuse();

        // answer feedback: no then yes
        $return = $this->model->submitFeedback(1, 1, 1, 'banana', 'message', 'foo@bar.invalid.com', 2);
        $this->assertTrue($this->isAnsFeedbackMsgBaseAllowed($return));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertTrue(Text::stringContains($incident->Subject, '1'));
        $this->assertTrue(Text::stringContains($incident->Subject, \RightNow\Utils\Config::getMessage(NOT_HELPFUL_LBL)));
        $this->assertSame('banana', $incident->PrimaryContact->Name->First);

        $return = $this->model->submitFeedback(1, 2, 2, 'banana', 'message', 'foo@bar.invalid.com', 2);
        $this->assertTrue($this->isAnsFeedbackMsgBaseAllowed($return));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertTrue(Text::stringContains($incident->Subject, '1'));
        $this->assertTrue(Text::stringContains($incident->Subject, \RightNow\Utils\Config::getMessage(HELPFUL_LBL)));
        $this->assertSame('banana', $incident->PrimaryContact->Name->First);

        // answer feedback: rating
        $return = $this->model->submitFeedback(1, 1, 3, 'banana', 'message', 'foo@bar.invalid.com', 3);
        $this->assertTrue($this->isAnsFeedbackMsgBaseAllowed($return));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertTrue(Text::stringContains($incident->Subject, '1'));
        $this->assertTrue(Text::stringContains($incident->Subject, \RightNow\Utils\Config::getMessage(RANK_0_LBL)));
        $this->assertSame('banana', $incident->PrimaryContact->Name->First);

        $return = $this->model->submitFeedback(1, 2, 3, 'banana', 'message', 'foo@bar.invalid.com', 5);
        $this->assertTrue($this->isAnsFeedbackMsgBaseAllowed($return));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertTrue(Text::stringContains($incident->Subject, '1'));
        $this->assertTrue(Text::stringContains($incident->Subject, \RightNow\Utils\Config::getMessage(RANK_25_LBL)));
        $this->assertSame('banana', $incident->PrimaryContact->Name->First);

        //Answer feedback at threshold with rating
        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => 'foo@bar.invalidnew.com')));
        $return = $this->model->submitFeedback(1, 3, 3, 'Jerry', 'message', 'foo@bar.invalidnew.com', 3);
        $this->assertTrue($this->isAnsFeedbackMsgBaseAllowed($return));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertTrue(Text::stringContains($incident->Subject, '1'));
        $this->assertTrue(Text::stringContains($incident->Subject, \RightNow\Utils\Config::getMessage(RANK_100_LBL)));
        $this->assertSame('Jerry', $incident->PrimaryContact->Name->First);

        // logged in
        $this->logIn($this->user1->login);
        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => 'foo@bar.invalid.com')));
        $return = $this->model->submitFeedback(1, 2, 3, 'banana', 'message', 'foo@bar.invalid.com', 5);
        $this->assertTrue($this->isAnsFeedbackMsgBaseAllowed($return));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));
        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $incident = $return->result;
        $this->assertTrue(Text::stringContains($incident->Subject, '1'));
        $this->assertTrue(Text::stringContains($incident->Subject, \RightNow\Utils\Config::getMessage(RANK_25_LBL)));
        $this->assertSame('banana', $incident->PrimaryContact->Name->First);
        $this->logout();

        $this->unsetMockSession();
    }

    function testSubmitFeedbackWithResponseEmailPriority() {
        $this->setMockSession();

        $login = 'bananaSplit' . microtime(true);
        $email = 'banana@split.com.' . microtime(true);
        $contact = $this->CI->model('Contact')->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email . '.primary'),
            'Contact.Emails.1.Address' => (object) array('value' => $email),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last' => (object) array('value' => 'bananaLast'),
        ))->result;
        Connect\ConnectAPI::commit();

        $incident = $this->model->submitFeedback(null, 4, 5, 'banana', 'message', $email)->result;
        $this->assertNotNull($incident);
        $this->assertTrue($incident->ID > 0);
        $this->assertSame(1, $incident->ResponseEmailAddressType->ID);

        Connect\ConnectAPI::rollback();
        $this->destroyObject($contact);
        Connect\ConnectAPI::commit();

        $this->unsetMockSession();
    }

    function testGetIncidentIDFromRefno(){
        $response = $this->model->getIncidentIDFromRefno('060606-000016');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(75, $response->result);
        $this->assertSame(0, count($return->warnings));
        $this->assertSame(0, count($return->errors));

        $response = $this->model->getIncidentIDFromRefno('060628-000003');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(111, $response->result);
        $this->assertSame(0, count($response->warnings));
        $this->assertSame(0, count($response->errors));

        $response = $this->model->getIncidentIDFromRefno('asdf');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(false, $response->result);
        $this->assertSame(0, count($response->warnings));
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getIncidentIDFromRefno(12);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(false, $response->result);
        $this->assertSame(0, count($response->warnings));
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getIncidentIDFromRefno('Hacker McGee " Select Contact.Password from Contacts');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(false, $response->result);
        $this->assertSame(0, count($response->warnings));
        $this->assertSame(1, count($response->errors));
    }

    function testGetSmartAssistantResults(){
        $method = $this->getMethod('getSmartAssistantResults');

        $content = new \RightNow\Connect\Knowledge\v1\SmartAssistantContentSearch();

        $content->Summary = 'TEMPLATETEST';
        $results = $method($content, array());
        $this->assertTrue(is_array($results));
        $this->assertTrue($results['canEscalate']);
        $this->assertTrue(is_string($results['token']));
        $this->assertTrue(is_array($results['suggestions']));
        $this->assertIdentical('StandardContent', $results['suggestions'][0]['type']);
        $this->assertIsA($results['suggestions'][0]['content'], 'string');

        $content->Summary = 'ANSWERTEST';
        $results = $method($content, array());
        $this->assertTrue(is_array($results));
        $this->assertTrue($results['canEscalate']);
        $this->assertTrue(is_string($results['token']));
        $this->assertTrue(is_array($results['suggestions']));
        $this->assertIdentical('Answer', $results['suggestions'][0]['type']);
        $this->assertIsA($results['suggestions'][0]['title'], 'string');
        $this->assertIsA($results['suggestions'][0]['content'], 'string');

        $content->Summary = 'SASEARCHTEST';
        $content->DetailedDescription = 'iPhone Android';
        $results = $method($content, array());
        $this->assertTrue(is_array($results));
        $this->assertTrue($results['canEscalate']);
        $this->assertTrue(is_string($results['token']));
        $this->assertTrue(is_array($results['suggestions']));
        $this->assertIdentical('AnswerSummary', $results['suggestions'][0]['type']);
        $this->assertTrue(is_array($results['suggestions'][0]['list']));
        $this->assertTrue(count($results['suggestions'][0]['list']) <= 5);

        $content->Summary = 'ESCALATETEST';
        $content->DetailedDescription = 'iPhone Android';
        $results = $method($content, array());
        $this->assertTrue(is_array($results));
        $this->assertFalse($results['canEscalate']);
        $this->assertTrue(is_string($results['token']));
        $this->assertTrue(is_array($results['suggestions']));

        $content->Summary = 'SARESPONSES';
        $content->DetailedDescription = 'iPhone Android';
        $results = $method($content, array());
        $this->assertTrue(is_array($results));
        $this->assertTrue($results['canEscalate']);
        $this->assertTrue(is_string($results['token']));
        $this->assertTrue(is_array($results['suggestions']));
        $this->assertIdentical('StandardContent', $results['suggestions'][0]['type']);
        $this->assertIdentical('AnswerSummary', $results['suggestions'][1]['type']);
        $this->assertTrue(is_array($results['suggestions'][1]['list']));
        $this->assertTrue(count($results['suggestions'][1]['list']) <= 5);

        $content->Summary = 'asdfl;nasegl;naseglkase';
        $content->DetailedDescription = 'geaseasge ;lkaseg;lkna seglh asgel;hasg';
        $results = $method($content, array());
        $this->assertTrue(is_array($results));
        $this->assertTrue(array_key_exists('suggestions', $results));
        $this->assertIdentical(0, count($results['suggestions']));
        $this->assertTrue($results['canEscalate']);
        $this->assertTrue(is_string($results['token']));
    }

    function testConvertFormDataToSmartAssistantSearch() {
        $method = $this->getMethod('convertFormDataToSmartAssistantSearch');

        $testContact = get_instance()->model('Contact')->get(1)->result;

        // order matters - test first without logging in and then with logging in (otherwise, you get cached SecurityOptions)
        // not logged in
        $result = $method(array(), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertIsA($result[0], KF_NAMESPACE_PREFIX . '\SmartAssistantContentSearch');
        $this->assertNull($result[0]->SecurityOptions);
        $this->assertIsA($result[0]->Summary, 'string');
        $this->assertNull($result[0]->DetailedDescription);
        $this->assertTrue(is_array($result[1]));
        $this->assertIdentical(count($result[1]), 0);

        // logged in
        $this->logIn($testContact->Login);
        $result = $method(array(), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertIsA($result[0], KF_NAMESPACE_PREFIX . '\SmartAssistantContentSearch');
        $this->assertIsA($result[0]->SecurityOptions, KF_NAMESPACE_PREFIX . '\ContentSecurityOptions');
        $this->assertIdentical($result[0]->SecurityOptions->Contact, $testContact);
        $this->assertIsA($result[0]->Summary, 'string');
        $this->assertNull($result[0]->DetailedDescription);
        $this->assertTrue(is_array($result[1]));
        $this->assertIdentical(count($result[1]), 0);
        $this->logOut();

        $result = $method(array('Incident.Subject' => 'Incident Title'), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertIdentical('Incident Title', $result[0]->Summary);
        $this->assertNull($result[0]->DetailedDescription);
        $this->assertTrue(is_array($result[1]));
        $this->assertIdentical(count($result[1]), 0);

        $result = $method(array('Incident.Subject' => 'Incident Title', 'Incident.Threads' => 'Incident content'), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertIdentical('Incident Title', $result[0]->Summary);
        $this->assertIdentical('Incident content', $result[0]->DetailedDescription);
        $this->assertTrue(is_array($result[1]));
        $this->assertIdentical(count($result[1]), 0);

        $result = $method(array('Incident.Subject' => 'Incident Title', 'Incident.Threads' => 'Incident content', 'Incident.Product.ID' => 1), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertIdentical('Incident Title', $result[0]->Summary);
        $this->assertIdentical('Incident content', $result[0]->DetailedDescription);
        $this->assertIsA($result[0]->Filters, KF_NAMESPACE_PREFIX . '\ContentFilterArray');
        $this->assertIsA($result[0]->Filters[0], KF_NAMESPACE_PREFIX . '\ServiceProductContentFilter');
        $this->assertTrue(is_array($result[1]));
        $this->assertIdentical(count($result[1]), 0);

        $result = $method(array('Incident.Subject' => 'Incident Title', 'Incident.Threads' => 'Incident content', 'Incident.Category.ID' => 68), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertIdentical('Incident Title', $result[0]->Summary);
        $this->assertIdentical('Incident content', $result[0]->DetailedDescription);
        $this->assertIsA($result[0]->Filters, KF_NAMESPACE_PREFIX . '\ContentFilterArray');
        $this->assertIsA($result[0]->Filters[0], KF_NAMESPACE_PREFIX . '\ServiceCategoryContentFilter');
        $this->assertTrue(is_array($result[1]));
        $this->assertIdentical(count($result[1]), 0);

        $result = $method(array('Incident.Subject' => 'Incident Title', 'Incident.Threads' => 'Incident content', 'Incident.Product.ID' => 754), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertIdentical('Incident Title', $result[0]->Summary);
        $this->assertIdentical('Incident content', $result[0]->DetailedDescription);
        $this->assertIsA($result[0]->Filters, KF_NAMESPACE_PREFIX . '\ContentFilterArray');
        $this->assertIdentical(count($result[0]->Filters), 0);
        $this->assertTrue(is_array($result[1]));
        $this->assertIdentical(count($result[1]), 0);

        $result = $method(array('Incident.Disposition' => 7, 'Incident.Language' => 'en-US', 'Incident.Queue' => 1, 'Incident.Severity.ID' => 2, 'Incident.StatusWithType' => STATUS_SOLVED), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[1]));
        $this->assertIdentical(7, $result[1]['Incident.Disposition']);
        $this->assertIdentical('en-US', $result[1]['Incident.Language']);
        $this->assertIdentical(1, $result[1]['Incident.Queue']);
        $this->assertIdentical(2, $result[1]['Incident.Severity.ID']);
        $this->assertIdentical(STATUS_SOLVED, $result[1]['Incident.StatusWithType']);

        $result = $method(array('Incident.CustomFields.c.foo' => 'bar', 'Incident.CustomFields.c.text1' => 'custom text', 'Incident.Subject' => 'Incident Title', 'Incident.Threads' => 'Incident content'), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[1]));
        $this->assertIdentical('bar', $result[1]['Incident.CustomFields.c.foo']);
        $this->assertIdentical('custom text', $result[1]['Incident.CustomFields.c.text1']);

        $result = $method(array('Incident.CustomFields.c.foo' => '', 'Incident.CustomFields.c.text1' => '', 'Incident.Subject' => 'Incident Title', 'Incident.Threads' => 'Incident content'), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[1]));
        $this->assertFalse(array_key_exists('Incident.CustomFields.c.foo', $result[1]));
        $this->assertFalse(array_key_exists('Incident.CustomFields.c.text1', $result[1]));

        // QA 200428-000173 need to pass numeric custom datetime values to smart assistant
        list($date1, $date2, $date3, $date4) = array('2001-01-01', '2002-02-02', '2003-03-03', '2004-04-04');
        $result = $method(array('Incident.CustomFields.CO.FieldDttm' => $date1, 'Incident.CustomFields.c.dttm1' => $date2), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[1]));
        $this->assertTrue(array_key_exists('Incident.CustomFields.CO.FieldDttm', $result[1]));
        $this->assertEqual((new \DateTime($date1))->getTimestamp(), $result[1]['Incident.CustomFields.CO.FieldDttm']);
        $this->assertTrue(array_key_exists('Incident.CustomFields.c.dttm1', $result[1]));
        $this->assertEqual((new \DateTime($date2))->getTimestamp(), $result[1]['Incident.CustomFields.c.dttm1']);

        $result = $method(array('Incident.CustomFields.CO.FieldDate' => $date3, 'Incident.CustomFields.c.date1' => $date4), $testContact);
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[1]));
        $this->assertTrue(array_key_exists('Incident.CustomFields.CO.FieldDate', $result[1]));
        $this->assertEqual((new \DateTime($date3))->getTimestamp(), $result[1]['Incident.CustomFields.CO.FieldDate']);
        $this->assertTrue(array_key_exists('Incident.CustomFields.c.date1', $result[1]));
        $this->assertEqual((new \DateTime($date4))->getTimestamp(), $result[1]['Incident.CustomFields.c.date1']);
    }

    function testIsContactAllowedToReadIncident() {
        $isContactAllowedToReadIncident = $this->getMethod('isContactAllowedToReadIncident');
        $incident = Connect\Incident::fetch($this->incidentID);
        if($incident){
            $isAllowed = $isContactAllowedToReadIncident($incident);
            $this->assertIsA($isAllowed, 'bool');
            if (!Framework::isLoggedIn()) {
                $this->assertFalse($isAllowed);
            }
        }

        //TODO: create an incident and assert contact allowed to read.
    }

    function testSetFieldValue(){
        $setFieldValue = $this->getMethod('setFieldValue');
        $mockIncident = $this->model->getBlank()->result;

        $setFieldValue($mockIncident, null, 0, 'Incident.StatusWithType.Status');
        $this->assertNull($mockIncident->StatusWithType->Status);

        $setFieldValue($mockIncident, null, 'test incident thread', 'Thread');
        $this->assertIdentical('test incident thread', $mockIncident->Threads[0]->Text);

        $setFieldValue($mockIncident, null, 2, 'AssignedSLAInstance');
        $this->assertIdentical(2, $mockIncident->SLAInstance->NameOfSLA->ID);

        // QA 200428-000173 date and datetime custom field handling
        list($date1, $date2, $date3, $date4) = array('2001-01-01', '2002-02-02', '2003-03-03', '2004-04-04');

        // datetime values are numeric
        $setFieldValue($mockIncident, 'Incident.CustomFields.CO.FieldDttm', $date1, 'DateTime');
        $this->assertEqual((new \DateTime($date1))->getTimestamp(), $mockIncident->CustomFields->CO->FieldDttm);
        $setFieldValue($mockIncident, 'Incident.CustomFields.c.dttm1', $date2, 'DateTime');
        $this->assertEqual((new \DateTime($date2))->getTimestamp(), $mockIncident->CustomFields->c->dttm1);

        // date values can be strings
        $setFieldValue($mockIncident, 'Incident.CustomFields.CO.FieldDate', $date3, 'Date');
        $this->assertEqual($date3, $mockIncident->CustomFields->CO->FieldDate);
        $setFieldValue($mockIncident, 'Incident.CustomFields.c.date1', $date4, 'Date');
        $this->assertEqual($date4, $mockIncident->CustomFields->c->date1);
    }

    function testCreateThreadEntry(){
        $createThreadEntry = $this->getMethod('createThreadEntry');

        $mockIncident = $this->model->getBlank()->result;

        $createThreadEntry($mockIncident, 'Test thread content');
        $this->assertIsA($mockIncident->Threads, CONNECT_NAMESPACE_PREFIX . '\ThreadArray');
        $this->assertIsA($mockIncident->Threads[0], CONNECT_NAMESPACE_PREFIX . '\Thread');
        $this->assertIsA($mockIncident->Threads[0]->EntryType, CONNECT_NAMESPACE_PREFIX . '\NamedIDOptList');
        $this->assertIdentical($mockIncident->Threads[0]->EntryType->ID, ENTRY_CUSTOMER);
        $this->assertIsA($mockIncident->Threads[0]->Channel, CONNECT_NAMESPACE_PREFIX . '\NamedIDLabel');
        $this->assertIdentical($mockIncident->Threads[0]->Channel->ID, CHAN_CSS_WEB);
        $this->assertIdentical($mockIncident->Threads[0]->Text, 'Test thread content');
    }

    function testCreateAttachmentEntry(){
        $createAttachmentEntry = $this->getMethod('createAttachmentEntry');

        $mockIncident = $this->model->getBlank()->result;

        $createAttachmentEntry($mockIncident, null);
        $this->assertNull($mockIncident->FileAttachments);

        $createAttachmentEntry($mockIncident, array());
        $this->assertNull($mockIncident->FileAttachments);

        $createAttachmentEntry($mockIncident, array((object)array('localName' => 'tempNameDoesntMatter', 'contentType' => 'image/sheen', 'userName' => 'reinactedScenesFromPlatoon.jpg')));
        $this->assertIsA($mockIncident->FileAttachments, CONNECT_NAMESPACE_PREFIX . '\FileAttachmentIncidentArray');
        $this->assertIdentical(0, count($mockIncident->FileAttachments));

        file_put_contents(\RightNow\Api::fattach_full_path('winning'), 'test data');

        $createAttachmentEntry($mockIncident, array((object)array('localName' => 'winning', 'contentType' => 'image/sheen', 'userName' => 'tigersBlood.jpg')));
        $this->assertIsA($mockIncident->FileAttachments, CONNECT_NAMESPACE_PREFIX . '\FileAttachmentIncidentArray');
        $this->assertIsA($mockIncident->FileAttachments[0], CONNECT_NAMESPACE_PREFIX . '\FileAttachmentIncident');
        $this->assertIdentical('image/sheen', $mockIncident->FileAttachments[0]->ContentType);
        $this->assertIdentical('tigersBlood.jpg', $mockIncident->FileAttachments[0]->FileName);

        unlink(\RightNow\Api::fattach_full_path('winning'));
    }

    function testCreateSlaEntry(){
        $createSlaEntry = $this->getMethod('createSlaEntry');

        $mockIncident = $this->model->getBlank()->result;

        $createSlaEntry($mockIncident, null);
        $this->assertNull($mockIncident->SLAInstance);

        $createSlaEntry($mockIncident, '0');
        $this->assertNull($mockIncident->SLAInstance);

        $createSlaEntry($mockIncident, false);
        $this->assertNull($mockIncident->SLAInstance);

        $createSlaEntry($mockIncident, 0);
        $this->assertNull($mockIncident->SLAInstance);

        $createSlaEntry($mockIncident, 4);
        $this->assertIsA($mockIncident->SLAInstance, CONNECT_NAMESPACE_PREFIX . '\AssignedSLAInstance');
        $this->assertIdentical(4, $mockIncident->SLAInstance->NameOfSLA->ID);
        $this->assertIdentical('Gold', $mockIncident->SLAInstance->NameOfSLA->LookupName);
    }

    function testEmptySubjectFilledFromThread() {
        $this->logout();
        $expectedSubject = 'bananas';

        // Verify that we fill in a subject from thread.
        $response = $this->model->create(array(
            'Incident.Threads' => (object) array('value' => $expectedSubject),
            'Incident.PrimaryContact' => 2,
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $result = $response->result;
        $this->assertIsA($result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $this->assertIdentical($expectedSubject, $result->Subject);

        // Verify that we fill in a subject from thread if the subject is not required.
        $response = $this->model->create(array(
            'Incident.Subject' => null,
            'Incident.Threads' => (object) array('value' => $expectedSubject),
            'Incident.PrimaryContact' => 2,
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $result = $response->result;
        $this->assertIsA($result, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $this->assertIdentical($expectedSubject, $result->Subject);

        //@@@ QA 130226-000079 Verify that we don't magically fill in a subject if it's empty and required.
        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => '', 'required' => true),
            'Incident.Threads' => (object) array('value' => $expectedSubject),
            'Incident.PrimaryContact' => 2,
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
    }

    function testGetContact(){
        $getContact = $this->getMethod('getContact');
        $this->assertIsA($getContact(1), CONNECT_NAMESPACE_PREFIX . '\Contact');
        if (Framework::isLoggedIn()) {
            $this->assertIsA($getContact(), CONNECT_NAMESPACE_PREFIX . '\Contact');
        }
        else {
            $this->assertNull($getContact());
        }
    }

    function testLookupEmailPriorityWithBadEmail() {
        $lookupEmailPriority = $this->getMethod('lookupEmailPriority');
        $this->assertSame(0, $lookupEmailPriority('yabbayabba@dabbadabba.' . microtime(true)));
    }

    function testLookupEmailPriorityWithPrimaryEmail() {
        $lookupEmailPriority = $this->getMethod('lookupEmailPriority');
        $this->assertSame(0, $lookupEmailPriority('ejergan@latisan.example.invalid'));
    }

    function testLookupEmailPriorityWithAltEmail() {
        $login = 'bananaSplit' . microtime(true);
        $email = 'banana@split.com.' . microtime(true);
        $contact = $this->CI->model('Contact')->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email . '.primary'),
            'Contact.Emails.1.Address' => (object) array('value' => $email),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last' => (object) array('value' => 'bananaLast'),
        ))->result;
        Connect\ConnectAPI::commit();

        $lookupEmailPriority = $this->getMethod('lookupEmailPriority');
        $this->assertSame(1, $lookupEmailPriority($email));

        if ($contact) {
            $this->destroyObject($contact);
            Connect\ConnectAPI::commit();
        }
    }

    function testIncidentCreateHooks() {
        $create = function($subject) {
            return get_instance()->model('Incident')->create(array(
                'Incident.Subject' => (object) array('value' => $subject),
                'Incident.PrimaryContact' => 2,
            ));
        };

        $expectedObject = CONNECT_NAMESPACE_PREFIX . '\Incident';

        $hookName = 'pre_incident_create';
        $this->setHook($hookName, array($hookName));
        $create($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual(self::$hookData['data']->Subject, $hookName);

        $hookName = 'post_incident_create';
        $this->setHook($hookName, array($hookName));
        $create($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual(self::$hookData['data']->Subject, $hookName);
        $this->assertNotNull(self::$hookData['data']->ID);
        $this->assertNotNull(self::$hookData['data']->ReferenceNumber);

        // If pre_incident_create returns a string, the incident should not be created.
        $lastIncidentID = $this->getLastIncidentID();
        $hookName = 'pre_incident_create';
        $this->setHook($hookName, array($hookName), 'hookError');
        $response = $create($hookName);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(self::$hookErrorMsg, $response->errors[0]->externalMessage);
        $this->assertIdentical(0, count($response->warnings));
        $this->assertIdentical($lastIncidentID, $this->getLastIncidentID());

        // If pre_incident_create_save returns a string, the incident should not be created.
        $lastIncidentID = $this->getLastIncidentID();
        $hookName = 'pre_incident_create_save';
        $this->setHook($hookName, array($hookName), 'hookError');
        $response = $create($hookName);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(self::$hookErrorMsg, $response->errors[0]->externalMessage);
        $this->assertIdentical(0, count($response->warnings));
        $this->assertIdentical($lastIncidentID, $this->getLastIncidentID());

        // If pre_incident_create_save sets shouldSave to false, don't save, but no error should result
        $lastIncidentID = $this->getLastIncidentID();
        $hookName = 'pre_incident_create_save';
        $this->setHook($hookName, array($hookName), 'shouldSaveHook', false);
        // don't try and register if we haven't saved, it'll just throw an exception (that's silently caught in the model)
        $this->setHooks(array(
            array('name' => 'pre_register_smart_assistant_resolution', 'function' => 'shouldRegisterHook'),
        ));
        $response = $create($hookName);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, $expectedObject);
        $this->assertIdentical(0, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));
        $this->assertIdentical($lastIncidentID, $this->getLastIncidentID());
        $this->assertNull($response->result->ID);
        $this->assertNull($response->result->ReferenceNumber);

        // If pre_register_smart_assistant_resolution sets shouldRegister to false, don't register, but no error should result
        $lastIncidentID = $this->getLastIncidentID();
        $hookName = 'pre_register_smart_assistant_resolution';
        $this->setHook($hookName, array($hookName), 'shouldRegisterHook');
        $response = $create($hookName, $model);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, $expectedObject);
        $this->assertIdentical(0, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));
        $this->assertNotIdentical($lastIncidentID, $this->getLastIncidentID());
        $this->assertNotNull($response->result->ID);
        $this->assertNotNull($response->result->ReferenceNumber);

        // set the sessionToken to an array - MAPI/KFAPI doens't like that
        // with normal execution, we get an error
        // if pre_register_smart_assistant_resolution sets shouldRegister to false, no error
        $baseClass = new \ReflectionClass('\RightNow\Models\Base');
        $sessionToken = $baseClass->getProperty('sessionToken');
        $sessionToken->setAccessible(true);
        $previousSessionToken = $sessionToken->getValue();
        $sessionToken->setValue(array('garbage'));

        $lastIncidentID = $this->getLastIncidentID();
        $hookName = 'pre_register_smart_assistant_resolution';
        $this->setHook($hookName, array($hookName));
        $this->expectError(new \PatternExpectation("/RightNow\\\\Connect\\\\Knowledge\\\\v1\\\\Knowledge::RegisterSmartAssistantResolution\(\) expects parameter 1 to be string, array given/"));
        $response = $create($hookName);

        $lastIncidentID = $this->getLastIncidentID();
        $hookName = 'pre_register_smart_assistant_resolution';
        $this->setHook($hookName, array($hookName), 'shouldRegisterHook');
        $response = $create($hookName);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, $expectedObject);
        $this->assertIdentical(0, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));
        $this->assertNotIdentical($lastIncidentID, $this->getLastIncidentID());
        $this->assertNotNull($response->result->ID);
        $this->assertNotNull($response->result->ReferenceNumber);

        $sessionToken->setValue($previousSessionToken);
    }

    function getLastIncidentID() {
        return \RightNow\Api::sql_get_int('SELECT MAX(i_id) FROM incidents');
    }

    function shouldSaveHook(&$hookData) {
        $hookData['shouldSave'] = false;
    }

    function shouldRegisterHook(&$hookData) {
        $hookData['shouldRegister'] = false;
    }

    function testIncidentUpdateHooks() {
        $update = function($subject) {
            return get_instance()->model('Incident')->update(1, array('Incident.Subject' => (object) array('value' => $subject)));
        };

        $getSubject = function() {
            return \RightNow\Api::sql_get_str('SELECT subject FROM incidents WHERE i_id = 1', 20);
        };

        $originalSubject = $getSubject();
        $this->logIn($this->user1->login);
        $expectedObject = CONNECT_NAMESPACE_PREFIX . '\Incident';

        $hookName = 'pre_incident_update';
        $this->setHook($hookName, array($hookName));
        $update($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual(self::$hookData['data']->Subject, $hookName);

        // If pre_incident_update returns a string, the incident should not be updated.
        $currentSubject = $getSubject();
        $this->setHook($hookName, array($hookName), 'hookError');
        $response = $update('Incident Should Not Get Updated');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(self::$hookErrorMsg, $response->errors[0]->externalMessage);
        $this->assertIdentical(0, count($response->warnings));
        $this->assertIdentical($currentSubject, $getSubject());

        $hookName = 'post_incident_update';
        $this->setHook($hookName, array($hookName));
        $update($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual(self::$hookData['data']->Subject, $hookName);

        $update($originalSubject);
        $this->logOut();
    }

    function testFeedbackAndContactHooks() {
        $submitFeedback = function($login) {
            $email = $login . time() . '@nowhere.com';
            $response = get_instance()->model('Incident')->submitFeedback(1, 1, 3, 'threshold', 'message', $email, 3);
            return array($email, $response);
        };

        $this->setMockSession();
        $expectedObject = CONNECT_NAMESPACE_PREFIX . '\Incident';

        $hookName = 'pre_feedback_submit';
        $this->setHook($hookName, array($hookName));
        list($email) = $submitFeedback($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual(self::$hookData['data']->PrimaryContact->Emails[0]->Address, $email);

        // If pre_feedback_submit returns a string, the incident should not get created
        $lastIncidentID = $this->getLastIncidentID();
        $this->setHook($hookName, array($hookName), 'hookError');
        list($email, $response) = $submitFeedback('IncidentShouldNotGetCreated');
        $this->assertResponseObject($response, 'is_null', 1);
        $this->assertIdentical(self::$hookErrorMsg, $response->error->externalMessage);
        $this->assertIdentical($lastIncidentID, $this->getLastIncidentID());

        $hookName = 'post_feedback_submit';
        $this->setHook($hookName, array($hookName));
        list($email) = $submitFeedback($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual(self::$hookData['data']->PrimaryContact->Emails[0]->Address, $email);

        // As submitFeedback() fires it's own pre/post contact_create hooks, we'll test them as well
        $expectedObject = CONNECT_NAMESPACE_PREFIX . '\Contact';

        $hookName = 'pre_contact_create';
        $this->setHook($hookName, array($hookName));
        list($email) = $submitFeedback($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual(self::$hookData['data']->Emails[0]->Address, $email);

        // If pre_contact_create returns a string, the contact should not get created
        $this->setHook($hookName, array($hookName), 'hookError');
        list($email, $response) = $submitFeedback('contactShouldNotGetCreated');
        $this->assertResponseObject($response, 'is_null', 1);
        $this->assertIdentical(self::$hookErrorMsg, $response->error->externalMessage);
        $this->assertFalse(\RightNow\Api::sql_get_int("SELECT c_id FROM contacts WHERE email='$email'"));

        $hookName = 'post_contact_create';
        $this->setHook($hookName, array($hookName));
        list($email) = $submitFeedback($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual(self::$hookData['data']->Emails[0]->Address, $email);

        $this->unsetMockSession();
    }

    //@@@ QA 130320-000068
    function testDoNotCreateInConnect() {
        $this->logIn();
        $response = $this->model->create(array(
            'Incident.Subject' => (object) array('value' => 'ESCALATETEST'),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(\RightNow\Utils\Config::getMessage(SORRY_ERROR_SUBMISSION_LBL), $response->errors[0]->externalMessage);
        $this->logOut();
    }

    function testGetAnswerFeedbackSubject(){
        $getAnswerFeedbackSubject = $this->getMethod('getAnswerFeedbackSubject');

        $this->assertIdentical("Feedback for Answer ID 10 (Rated: Helpful)", $getAnswerFeedbackSubject(10, 2, 2));
        $this->assertIdentical("Feedback for Answer ID 10 (Rated: Not Helpful)", $getAnswerFeedbackSubject(10, 2, 1));
        $this->assertIdentical("Feedback for Answer ID 0 (Rated: Not Helpful)", $getAnswerFeedbackSubject('ten', 2, 1));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated: Not Helpful)", $getAnswerFeedbackSubject('25', 2, 0));

        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 0%)", $getAnswerFeedbackSubject(25, 3, 1));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 50%)", $getAnswerFeedbackSubject(25, 3, 2));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 100%)", $getAnswerFeedbackSubject(25, 3, 3));

        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 0%)", $getAnswerFeedbackSubject(25, 4, 1));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 25%)", $getAnswerFeedbackSubject(25, 4, 2));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 75%)", $getAnswerFeedbackSubject(25, 4, 3));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 100%)", $getAnswerFeedbackSubject(25, 4, 4));

        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 0%)", $getAnswerFeedbackSubject(25, 5, 1));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 25%)", $getAnswerFeedbackSubject(25, 5, 2));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 50%)", $getAnswerFeedbackSubject(25, 5, 3));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 75%)", $getAnswerFeedbackSubject(25, 5, 4));
        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 100%)", $getAnswerFeedbackSubject(25, 5, 5));

        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 5)", $getAnswerFeedbackSubject(25, 6, 5));

        $this->assertIdentical("Feedback for Answer ID 25 (Rated Helpfulness: 35)", $getAnswerFeedbackSubject(25, 100, 35));
    }

    function testRegenerateProfile() {
        $regenerateProfile = $this->getMethod('regenerateProfile');
        $user = $this->user2->login;

        $makeRequest = function(array $propertyNamesToPreserve = array()) {
            return json_decode(TestHelper::makeRequest(
                '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/testRegenerateProfile',
                array('post' => 'propertyNamesToPreserve=' . implode(',', $propertyNamesToPreserve))
            ));
        };

        if (Text::stringContains($_SERVER['SCRIPT_URL'], 'wgetRecipient')) {
            // Request came from `makeRequest` below
            $this->logIn($user);
            $propertyNamesToPreserve = explode(',', $_POST['propertyNamesToPreserve']);
            if ($propertyNamesToPreserve === array('')) {
                $propertyNamesToPreserve = array();
            }
            echo json_encode($regenerateProfile($this->CI->session->getProfile(true), $propertyNamesToPreserve));
        }
        else {
            // Sending in a null profile should return null
            $this->assertNull($regenerateProfile(null));

            // Log in and make a remote request as Api::contact_login_verify seems to return
            // null if called too many times for the same user in the same request.
            $this->logIn($user);
            $currentProfile = $this->CI->session->getProfile(true);

            // Preserve properties
            $profile = $makeRequest(array('openLoginUsed', 'socialUserID'));
            $this->assertIsA($profile, 'stdClass');
            $this->assertEqual($currentProfile->contactID, $profile->contactID);
            $this->assertEqual($currentProfile->openLoginUsed, $profile->openLoginUsed);
            $this->assertEqual($currentProfile->socialUserID, $profile->socialUserID);

            // Don't preserve properties
            $profile = $makeRequest();
            $this->assertIsA($profile, 'stdClass');
            $this->assertEqual($currentProfile->contactID, $profile->contactID);
            $this->assertNull($profile->openLoginUsed);
            $this->assertNull($profile->socialUserID);
        }

        $this->logOut();
    }

    function testOkcsHook(){
        $previousOkcsConfigs = TestHelper::getConfigValues(array('OKCS_ENABLED'));
        TestHelper::setConfigValues(array('OKCS_ENABLED' => true));
        $okcsHook = function($subject) {
            return array(
                'formData' => array(
                    'Contact.Emails.PRIMARY.Address' => (object) array(
                        'value' => 'test@test.com',
                        'required' => true
                    ),
                    'Incident.Subject' => (object) array(
                        'value' => 'windows',
                        'required' => true
                    ),
                    'Incident.Threads' => (object) array('value' => 'windows')
                ),
                'token' => '',
                'canEscalate' => true,
                'suggestions' => array(),
                'priorTransactionID' => 1,
                'okcsSearchSession' => ''
            );
        };
        $hookName = 'pre_retrieve_smart_assistant_answers';
        $this->setHook($hookName, array($hookName), 'hookError');
        $this->setHook($hookName, array($hookName));
        $response = $okcsHook($hookName);
        $this->assertTrue(is_array($response));
        $this->assertIdentical(0, count($response->errors));

        TestHelper::setConfigValues($previousOkcsConfigs);
    }

    /**
     * Answer feedback incident subject should either match FEEDBK_ANS_ID_PCT_D_RATED_PCT_S_LBL
     * or FEEDBK_ANS_ID_PCT_D_RATED_HELPFUL_LBL message base values
     *
     */
    function isAnsFeedbackMsgBaseAllowed($incident) {
        if (isset($incident->result->Subject)) {
            $actualSubject = $incident->result->Subject;
            $allowedMsgBaseEntries = array(FEEDBK_ANS_ID_PCT_D_RATED_PCT_S_LBL, FEEDBK_ANS_ID_PCT_D_RATED_HELPFUL_LBL);
            foreach ($allowedMsgBaseEntries as $messageBaseEntry) {
                $allowedMsgBaseValue = \RightNow\Utils\Config::getMessage($messageBaseEntry);
                if (Text::beginsWith($actualSubject, Text::getSubstringBefore($allowedMsgBaseValue, "%d"))) {
                    return true;
                }
            }
        }
        return false;
    }
}
