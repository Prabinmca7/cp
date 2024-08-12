<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api,
    RightNow\UnitTest\Helper as TestHelper;

class ChatModelTest extends CPTestCase
{
    public $testingClass = 'RightNow\Models\Chat';

    function __construct()
    {
        $this->CI = get_instance();
        $this->model = new \RightNow\Models\Chat();
    }

    public function testGetChatHours()
    {
        //Anticipated result with default interface settings
        $expected = array('hours_data' => array(
                                'time_zone' => 'MDT',
                                'workday_definitions' => array(
                                    array(
                                        'days_of_week' => array(
                                            1,2,3,4,5,6,7
                                        ),
                                        'has_hours' => true,
                                        'work_intervals' => array(
                                            array(
                                                'start' => '00:00',
                                                'end' => '24:00'
                                            )
                                        )
                                    )
                                )
                            ),
                            'hours' => array(
                                array(
                                    'Monday - Sunday',
                                    '24 Hours'
                                )
                            )
                    );

        //Strip out holiday, inWorkHours and current_time as these can vary with the day.
        $result = $this->model->getChatHours()->result;
        $this->assertTrue(isset($result['holiday']));
        $this->assertTrue(isset($result['inWorkHours']));
        $this->assertTrue(isset($result['current_time']));
        unset($result['holiday']);
        unset($result['inWorkHours']);
        unset($result['current_time']);
        //Ignore differences between daylight savings time
        if($result['hours_data']['time_zone'] === 'MST'){
            $result['hours_data']['time_zone'] = 'MDT';
        }
        $this->assertIdentical($expected, $result);
    }

    public function testGetChatUrl()
    {
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
        
        $previousGlobalSSL = TestHelper::getConfigValues(array('GLOBAL_SSL_COMMUNICATION_ENABLED'));
        TestHelper::setConfigValues(array('GLOBAL_SSL_COMMUNICATION_ENABLED' => 1));
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
        
        TestHelper::setConfigValues($previousGlobalSSL);
        $method = $this->getMethod('getChatUrl');

        
    }

    public function testGetBlankCustomFieldArray() {
        $response = $this->model->getBlankCustomFields();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->errors, 'array');
        $this->assertIsA($response->warnings, 'array');
        $this->assertIdentical(count($response->errors), 0);
        $this->assertIdentical(count($response->warnings), 0);
        $customFields = $response->result;
        $this->assertIsA($customFields, 'array');
    }

    public function testGetMenuItems() {
        $method = $this->getMethod('getMenuItems');
        $this->assertNull($method(null));
        foreach(array(23, 28, 29, 31, 33, 43, 49) as $customFieldID) {
            $menuItems = $method($customFieldID);
            $this->assertIsA($menuItems, 'array');
            $this->assertNotEqual(0, count($menuItems));
        }
    }

    public function testChatValidate() {
        $response = $this->model->chatValidate();
        $this->assertTrue(isset($response['status']));
        $this->assertFalse($response['status']);
        $this->assertTrue(isset($response['error']));
        $this->logIn();
        $response = $this->model->chatValidate();
        $this->assertTrue(isset($response['status']));
        $this->assertTrue($response['status']);
    }

    public function testGetChatAuthenticationData() {
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
    }

    public function testGetChatAuthenticationMessage()
    {
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
}
