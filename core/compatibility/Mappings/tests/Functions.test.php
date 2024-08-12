<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FunctionsTest extends CPTestCase
{
    function __construct()
    {
        parent::__construct();
        $this->urlRequest = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/';
    }

    function testInsertBeforeTag() {
        $expected = "<html><body><p>this is a doc</p>\n<script type='text/javascript' src='something'></script>\n</body>\n</html>";
        $result = insertBeforeTag("<html><body><p>this is a doc</p></body></html>", "<script type='text/javascript' src='something'></script>", \RightNow\Utils\Tags::CLOSE_BODY_TAG_REGEX);
        $this->assertIdentical($result, $expected);
    }

    function testSetCPCookie() {
        $output = $this->makeRequest($this->urlRequest . 'setCPCookie1', array('justHeaders' => true));

        //A Max-Age attribute is now included in the Set-Cookie header sent to the client from PHP 5.5.0
        $this->assertIdentical(1, preg_match("#Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/; httponly#", $output));

        $output = $this->makeRequest($this->urlRequest . 'setCPCookie2', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/path; domain=thisdomain; SameSite=None; Secure#", $output));

        $output = $this->makeRequest($this->urlRequest . 'setCPCookie3', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/path; domain=thisdomain#", $output));

        $output = $this->makeRequest($this->urlRequest . 'setCPCookie4', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/path; domain=thisdomain; SameSite=None; Secure#", $output));

        $output = $this->makeRequest($this->urlRequest . 'setCPCookie5', array('justHeaders' => true));
        $this->assertIdentical(1, preg_match("#Set-Cookie: blah=bleet; expires=[a-zA-Z0-9:, -]+; Max-Age=[0-9]+; path=/path; domain=thisdomain#", $output));
    }

    static function setCPCookie1() {
        SetCPCookie('blah', 'bleet', time());
    }

    static function setCPCookie2() {
        SetCPCookie('blah', 'bleet', time(), '/path', 'thisdomain', false, true);
    }

    static function setCPCookie3() {
        SetCPCookie('blah', 'bleet', time(), '/path', 'thisdomain', false, false);
    }

    static function setCPCookie4() {
        \Rnow::updateConfig('SEC_END_USER_HTTPS', true, true);
        SetCPCookie('blah', 'bleet', time(), '/path', 'thisdomain', false);
    }

    static function setCPCookie5() {
        \Rnow::updateConfig('SEC_END_USER_HTTPS', false, true);
        SetCPCookie('blah', 'bleet', time(), '/path', 'thisdomain', false);
    }

    function testRedirectToHttpsIfNecessary() {
        $output = $this->makeRequest($this->urlRequest . 'redirectToHttpsIfNecessary1', array('cookie' => 'cp_profile_flag=1;', 'justHeaders' => true));
        $this->assertTrue(strpos($output, 'Location: https://') !== false); //ensure that the location redirect is in the headers
        $output = $this->makeRequest($this->urlRequest . 'redirectToHttpsIfNecessary1', array('cookie' => 'cp_profile_flag=bull;', 'justHeaders' => true));
        $this->assertFalse(strpos($output, 'Location: https://')); //ensure that the location redirect is NOT in the headers
    }

    static function redirectToHttpsIfNecessary1() {
        redirectToHttpsIfNecessary();
    }
}
