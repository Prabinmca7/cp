<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use \RightNow\Libraries\OpenLoginErrors,
    \RightNow\Controllers\UnitTest,
    \RightNow\Utils\Text;

class OpenLoginErrorsTest extends CPTestCase 
{
    // missing COOKIES_REQUIRED_ERROR
    private $errors = array(
        "USER_REJECTION_ERROR",
        "INVALID_EMAIL_ERROR",
        "COOKIES_REQUIRED_ERROR",
        "AUTHENTICATION_ERROR",
        "TWITTER_API_ERROR",
        "CONTACT_DISABLED_ERROR",
        "FACEBOOK_PROXY_EMAIL_ERROR",
        "CONTACT_LOGIN_ERROR",
        "OPENID_INVALID_PROVIDER_ERROR",
        "OPENID_RESPONSE_INVALID_PROVIDER_ERROR",
        "OPENID_RESPONSE_INSUFFICIENT_DATA_ERROR",
        "OPENID_CONNECT_ERROR",
    );

    function testMapOpenLoginErrorsToPageErrors()
    {
        foreach ($this->errors as $error)
        {
            $value = OpenLoginErrors::mapOpenLoginErrorsToPageErrors(constant('\RightNow\Libraries\OpenLoginErrors::' . $error));
            $this->assertSame($value, ($error === "COOKIES_REQUIRED_ERROR") ? "saml19" : "saml18");
        }
    }

    function testGetErrorMessage() 
    {
        foreach ($this->errors as $error) 
        {
            $value = OpenLoginErrors::getErrorMessage(constant('\RightNow\Libraries\OpenLoginErrors::' . $error));
            $this->assertTrue(is_string($value));
            $this->assertNotEqual($value, '');
        }

        $value = OpenLoginErrors::getErrorMessage(1234);
        $this->assertEqual($value, "");
    }
}
