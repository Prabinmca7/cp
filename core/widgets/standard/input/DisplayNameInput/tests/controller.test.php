<?php

use RightNow\Utils\Config,
    RightNow\UnitTest\Helper;

if (!class_exists('RightNow\Widgets\TextInput')) {
    \RightNow\Internal\Utils\Widgets::requireWidgetControllerWithPathInfo(\RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo('standard/input/DisplayNameInput'));
}

Helper::loadTestedFile(__FILE__);

class DisplayNameInput extends WidgetTestCase {
    public $testingWidget = 'standard/input/DisplayNameInput';

    function testLoggedInUsersDisplayNameIsRetrieved() {
        $this->logIn();

        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData($instance);
        $this->assertIsA($data['value'], 'string');
        $this->assertTrue(strlen($data['value']) > 0);
        $this->assertTrue($data['attrs']['required']);

        $this->logOut();
    }

    function testUserInParameterIsRetrieved() {
        $this->addUrlParameters(array('user' => '10'));

        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData($instance);
        $this->assertEqual($data['value'], '');

        $this->logIn();
        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData($instance);
        $this->assertIsA($data['value'], 'string');
        $this->assertTrue(strlen($data['value']) > 0);
        $this->assertTrue($data['attrs']['required']);
        $this->logOut();

        $this->restoreUrlParameters();
    }

    function getSocialUserID() {
        if ($user = \RightNow\Utils\Text::getSubstringAfter($this->CI->uri->uri_string(), __FUNCTION__ . '/user/')) {
            $this->addUrlParameters(array('user' => $user));
        }
        $this->logIn();
        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData($instance);
        $this->logOut();
        if ($user) {
            $this->restoreUrlParameters();
        }
        echo $data['socialUserID'];
    }

    function testSocialUserID() {
        $makeRequest = function($user = '') {
            return json_decode(Helper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . "/getSocialUserID/user/$user"));
        };

        // data['socialUserID'] should match logged-in user in absence of `user` url parameter
        $this->logIn();
        $this->assertNotEqual($this->CI->session->getProfileData('socialUserID'), $userActive1->ID);
        $this->assertEqual($this->CI->session->getProfileData('socialUserID'), $makeRequest());

        // data['socialUserID'] should match `user` url parameter when present
        list($fixtureInstance, $userActive1) = $this->getFixtures(array('UserActive1'));
        $this->assertEqual($userActive1->ID, $makeRequest($userActive1->ID));

        // data['socialUserID'] should match logged-in user when invalid `user` url parameter present
        $this->logIn();
        $this->assertEqual($this->CI->session->getProfileData('socialUserID'), $makeRequest('invalidUser'));

        $this->logOut();
        $fixtureInstance->destroy();
    }
}
