<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Url as Url;

class UtilsOitTest extends CPTestCase {

    public $testingClass = 'RightNow\Internal\Oit\Utils';

    /*public function testGetChatAuthenticationData() {
        $method = $this->getMethod('getChatAuthenticationData');

        $this->logIn();
        // this loop executes twice - first when logged in and then when logged out
        foreach (array('logged in', 'logged out') as $authType) {
            // repeated calls for either chat type should not fail
            foreach (array(1, 1, 2, 2) as $index => $type) {
                $chatData = $method($type)->result;

                $this->assertNotNull($chatData, "Chat Data was null for type $type in iteration $index when $authType.");
                $this->assertNotNull($chatData['jwt'], "Chat Data had a null value for jwt with chat type $type in iteration $index when $authType.");
                $this->assertNotNull($chatData['tierOneSessionId'], "Chat Data had a null value for tierOneSessionId with chat type $type in iteration $index when $authType.");

                // contact metadata should be populated for logged-in users only
                if ($authType == 'logged in') {
                    $this->assertNotNull($chatData['email'], "Chat Data had a null value for email with type $type in iteration $index when $authType.");
                    $this->assertNotNull($chatData['first_name'], "Chat Data had a null value for first_name with type $type in iteration $index when $authType.");
                    $this->assertNotNull($chatData['last_name'], "Chat Data had a null value for last_name with type $type in iteration $index when $authType.");
                } else {
                    $this->assertNull($chatData['email'], "Chat Data should have a null value for email with type $type in iteration $index when $authType.");
                    $this->assertNull($chatData['first_name'], "Chat Data should have a null value for first_name with type $type in iteration $index when $authType.");
                    $this->assertNull($chatData['last_name'], "Chat Data should have a null value for last_name with type $type in iteration $index when $authType.");
                }
            }

            // log out so that the next iteration runs logged out
            $this->logOut();
        }
    }*/

    public function testGetChatAuthenticationMessage() {
        $method = $this->getMethod('getChatAuthenticationMessage');
        $this->logIn();
        $loggedInUser = $this->CI->model('Contact')->get()->result;
        $profile = $this->CI->session->getProfile(true);

        // the chat data normally comes from getChatAuthenticationData()
        $chatData = array(
            'first_name' => $loggedInUser->Name->First,
            'last_name' => $loggedInUser->Name->Last,
            'email' => $profile->email,
            'jwt' => "jwt-" . rand(),
            'tierOneSessionId' => "tierOneSession-" . rand(),
        );

        $customFields = array(
            'customField1' => 'customValue1',
            'customField2' => 'customValue2',
        );

        // note that the jwt is not passed in the body (it'll go in the Authorization header)
        $expected = array(
            "firstName"=> $chatData['first_name'],
            "lastName"=> $chatData['last_name'],
            "emailAddress"=> $chatData['email'],
            'interfaceId' => Api::intf_id(),
            'contactId' => $loggedInUser->ID,
            'organizationId' => $profile->orgID,
            'productId' => rand(),
            'categoryId' => rand(),
            'queueId' => rand(),
            'incidentId' => rand(),
            'resumeType' => 'RESUME',
            'question' => "How can I " . rand(),
            'tierOneSessionId' => $chatData['tierOneSessionId'],
            'organizationId' => $profile->orgID,
            'mediaList' => array((object)array("type" => "COBROWSE")),
            'customFields' => array(
                array("name" => "customField1", "value" => $customFields['customField1']),
                array("name" => "customField2", "value" => $customFields['customField2']),
            ),
            'routingData' => 'chat_data_' . rand(),
        );

        // populate the POST data
        $_POST['prod'] = $expected['productId'];
        $_POST['cat'] = $expected['categoryId'];
        $_POST['queueID'] = $expected['queueId'];
        $_POST['incidentID'] = $expected['incidentId'];
        $_POST['resume'] = 'true';
        $_POST['subject'] = $expected['question'];
        $_POST['mediaList'] = json_encode($expected['mediaList']);
        $_POST['customFields'] = json_encode($customFields);
        $_POST['routingData'] = $expected['routingData'];
        // note that these last few differ from the expected values - they should only be used for anonymous users
        $_POST['email'] = "user" . rand() . "@noreply.oracle.com";
        $_POST['firstName'] = "first" . rand();
        $_POST['lastName'] = "last" . rand();

        // first try the logged-in case
        $message = $method($chatData)->result;

        foreach ($expected as $key => $value) {
            $this->assertEqual($message[$key], $value, "Mismatched values for $key: expected: " . print_r($value, true) . " actual: " . print_r($message[$key], true));
        }

        // set up for logged-out test
        $this->logOut();
        $expected['contactId'] = null;
        $expected['emailAddress'] = $_POST['email'];
        $expected['firstName'] = $_POST['firstName'];
        $expected['lastName'] = $_POST['lastName'];

        $message = $method($chatData)->result;

        // now try the logged-out case
        foreach ($expected as $key => $value) {
            $this->assertEqual($message[$key], $value, "Mismatched values for $key: expected: " . print_r($value, true) . " actual: " . print_r($message[$key], true));
        }
    }

    public function testGetChatUrl() {
        $chatUrl = 'http://' . \RightNow\Utils\Config::getConfig(SRV_CHAT_INT_HOST, 'RNL') . '/Chat/chat/' . \RightNow\Utils\Config::getConfig(DB_NAME, 'COMMON');
        $method = $this->getMethod('getChatUrl');
        $this->assertSame($chatUrl, $method());

        $chatUrl .= '?i_id=5&other_stuff=yeah';
        $this->assertSame($chatUrl, $method(array('i_id' => 5, 'other_stuff' => 'yeah')));

        // also test the REST API URL
        $chatAuthUrl = join(array(
            'http://',
            \RightNow\Utils\Config::getConfig(SRV_CHAT_INT_HOST, 'RNL'),
            '/engagement/api/consumer/',
            \RightNow\Utils\Config::getConfig(DB_NAME, 'COMMON'),
            '/v1/authenticate'));
        $this->assertSame($chatAuthUrl, $method(array(), true));

        $chatAuthUrl .= '?i_id=5&other_stuff=yeah';
        $this->assertSame($chatAuthUrl, $method(array('i_id' => 5, 'other_stuff' => 'yeah'), true));
        
        $previousGlobalSSL = Helper::getConfigValues(array('GLOBAL_SSL_COMMUNICATION_ENABLED'));
        Helper::setConfigValues(array('GLOBAL_SSL_COMMUNICATION_ENABLED' => 1));
        $method = $this->getMethod('getChatUrl');
        $chatAuthHttpsUrl = join(array(
            'https://',
            \RightNow\Utils\Config::getConfig(SRV_CHAT_INT_HOST, 'RNL'),
            '/engagement/api/consumer/',
            \RightNow\Utils\Config::getConfig(DB_NAME, 'COMMON'),
            '/v1/authenticate'));        
        $this->assertSame($chatAuthHttpsUrl, $method(array(), true));
        
        $chatAuthHttpsUrl .= '?i_id=5&other_stuff=yeah';
        $this->assertSame($chatAuthHttpsUrl, $method(array('i_id' => 5, 'other_stuff' => 'yeah'), true));
        
        Helper::setConfigValues($previousGlobalSSL);
        $method = $this->getMethod('getChatUrl');
    }

    function testgetSHA2Hash() {
        $method = $this->getMethod('getSHA2Hash');
        $this->assertIdentical($method('abc.jpg12345table'), 'aea01dec7da53fe359c07a891b872fd4bc54d81b1672fa53489135e3a4332803');
    }

    function testIsRequestHttps()  {
        $method = $this->getMethod('isRequestHttps');
        $expected = Text::getSubstringBefore($_SERVER['SCRIPT_URI'], ':') == 'https';
        $this->assertIdentical($expected, $method());
    }

    function testGetOriginalUrl() {
        $method = $this->getMethod('getOriginalUrl');
        $expectedUrl = 'http://' . $_SERVER['SERVER_NAME'];
        $url = $method(false);
        $this->assertEqual($expectedUrl, $url);

        $urlWithUri = $method();
        $this->assertTrue(strlen($urlWithUri) > strlen($expectedUrl));
        $this->assertTrue(Text::stringContains($urlWithUri, $expectedUrl));
    }

    function testIsValidRequestDomain() {
        $method = $this->getMethod('isValidRequestDomain');

        // Deny All
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => ''), false);
        $this->assertFalse($method());

        // Allow All
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*'), false);
        $_SERVER['HTTP_ORIGIN'] = 'test.oracle.com';
        $this->assertTrue($method());
        
        // Host Check for same Domain
        $_SERVER['HTTP_HOST'] =  Url::getShortEufBaseUrl('sameAsRequest');
        $this->assertTrue($method());


        // Origin Check
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => 'test.oracle.com'), false);
        $_SERVER['HTTP_ORIGIN'] = 'test.oracle.com';
        $this->assertTrue($method());
        $_SERVER['HTTP_ORIGIN'] = 'hello.oracle.com';
        $this->assertFalse($method());

        // Referer Check
        $_SERVER['HTTP_ORIGIN'] = '';
        $_SERVER['HTTP_REFERER'] = 'test.oracle.com';
        $this->assertTrue($method());
        $_SERVER['HTTP_REFERER'] = 'hello.oracle.com';
        $this->assertFalse($method());
    }

    function testGetAllowedOrigin() {
        $method = $this->getMethod('getAllowedOrigin');
        // Origin present
        $_SERVER['HTTP_ORIGIN'] = 'test.oracle.com';
        $this->assertEqual($_SERVER['HTTP_ORIGIN'], $method());

        // Origin empty - Referer present
        $_SERVER['HTTP_ORIGIN'] = '';
        $_SERVER['HTTP_REFERER'] = "http://vipdq01.dq.lan/s/oit-qa/latest/inlays/oracle/chat-embedded/example1.html";
        $this->assertEqual('http://vipdq01.dq.lan', $method());
    }

    function testSanitizeFilename() {
        $method = $this->getMethod('sanitizeFilename');
        $this->assertSame('cp-script-', $method('cp<script>'));
        $this->assertSame('cp<script', $method('cp<script'));
        $this->assertSame('cpscript>', $method('cpscript>'));
        $this->assertSame('cp-script-', $method('cp&lt;script&gt;'));
        $this->assertSame('cp&lt;script', $method('cp&lt;script'));
        $this->assertSame('cpscript&gt;', $method('cpscript&gt;'));
        $this->assertSame('cp-script-', $method('cp<script&gt;'));
        $this->assertSame('cp-script-', $method('cp&lt;script>'));
        $this->assertSame('cp>script&lt;', $method('cp>script&lt;'));
        $this->assertSame('cp&gt;script<', $method('cp&gt;script<'));
        $this->assertSame('cp--script', $method('cp"\'script'));
    }
}
