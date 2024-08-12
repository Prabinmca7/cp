<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\UnitTest\Helper as TestHelper;

class FormSubmitTest extends WidgetTestCase
{
    public $testingWidget = "standard/input/FormSubmit";

    function testGetData()
    {
        $reflectionClass = new \ReflectionClass('RightNow\Libraries\Session');
        $sessionIdleLength = $reflectionClass->getProperty('sessionIdleLength');
        $sessionIdleLength->setAccessible(true);
        $previousSessionIdleLength = $sessionIdleLength->getValue($this->CI->session);
        $sessionIdleLength->setValue($this->CI->session, 1800);
        $profileCookieLength = $reflectionClass->getProperty('profileCookieLength');
        $profileCookieLength->setAccessible(true);
        $previousProfileCookieLength = $profileCookieLength->getValue($this->CI->session);
        $profileCookieLength->setValue($this->CI->session, 3600);

        $this->createWidgetInstance();

        $this->logIn();

        $data = $this->getWidgetData();
        // by default, logged in users get the default submit token expiration (30 - 5 minutes)
        $this->assertIdentical(1500000, $data['js']['formExpiration']);

        $this->logOut();

        $data = $this->getWidgetData();
        // by default, anonymous users get the default submit token expiration (30 - 5 minutes)
        $this->assertIdentical(1500000, $data['js']['formExpiration']);

        $config = TestHelper::getConfigValues(array('SUBMIT_TOKEN_EXP'));
        TestHelper::setConfigValues(array('SUBMIT_TOKEN_EXP' => '100'));

        $this->logIn();

        $data = $this->getWidgetData();
        // with a large submit token expiration, logged in users get the login expiration (60 - 5 minutes)
        $this->assertIdentical(3300000, $data['js']['formExpiration']);

        $this->logOut();

        $data = $this->getWidgetData();
        // with a large submit token expiration, anonymous users get the session timeout (30 - 5 minutes)
        $this->assertIdentical(1500000, $data['js']['formExpiration']);

        TestHelper::setConfigValues($config);

        $sessionIdleLength->setValue($this->CI->session, $previousSessionIdleLength);
        $profileCookieLength->setValue($this->CI->session, $previousProfileCookieLength);
    }
}
