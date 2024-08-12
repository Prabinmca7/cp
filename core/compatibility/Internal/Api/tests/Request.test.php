<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

Use \RightNow\UnitTest\Helper,
    \RightNow\Utils\Text,
    \RightNow\Utils\Url as Url;

class RequestApiTest extends CPTestCase {
    
    public $testingClass = 'RightNow\Internal\Api\Request';

    /**
     * Returns a mock CI object having mock 'config' and 'uri' modules.
     * @param array $returnUriValues An optional array of key/value pairs to send to uri->setReturnValue()
     * @param array $returnInputValues An optional array of key/value pairs to send to input->setReturnValue()
     * @return object
     */
    function getMockCI(array $returnUriValues = array(), array $returnInputValues = array()) {
        if (!class_exists('\RightNow\Controllers\MockBase')) {
            Mock::generate('\RightNow\Controllers\Base', '\RightNow\Controllers\MockBase');
        }
        Mock::generate('CI_Config');
        Mock::generate('CI_Input');
        Mock::generate('CI_URI');

        $CI = new \RightNow\Controllers\MockBase();
        $CI->config = new MockCI_Config();
        $CI->input = new MockCI_Input();
        $CI->uri = new MockCI_URI();

        foreach($returnUriValues as $key => $value) {
            $CI->uri->setReturnValue($key, $value);
        }
        foreach($returnInputValues as $key => $scenarios) {
            foreach($scenarios as $scenario) {
                list($value, $args) = $scenario;
                $CI->input->setReturnValue($key, $value, $args);
            }
        }
        return $CI;
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

    function testIsRequestHttps()  {
        $method = $this->getMethod('isRequestHttps');
        $expected = Text::getSubstringBefore($_SERVER['SCRIPT_URI'], ':') == 'https';
        $this->assertIdentical($expected, $method());
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

    function testGetParameter() {
        $CI = $this->getMockCI(array('uri_to_assoc' => array('foo' => 'bar', 'baz' => 'bam', 'slash' => 'a%2fb')));
        $request = new \RightNow\Internal\Api\Request();
        $this->assertEqual('bar', $request->getUriParams('foo', $CI));
        $this->assertEqual('bam', $request->getUriParams('baz', $CI));
        $this->assertNull($request->getUriParams('notThere', $CI));
    }

    // It would be nice to add tests for getParameterString, getParameter, etc.
    // Those method rely on $CI from get_instance.  I could make them have an
    // optional parameter to specify a $CI which would allow me to specify a mock CI.  That would
    // give me a chance to exercise simpletest's mock framework.

    function testGetParameterString() {
        $CI = $this->getMockCI(array('uri_to_assoc' => array('foo' => 'bar', 'baz' => 'bam')));
        $request = new \RightNow\Internal\Api\Request();
        $this->assertEqual('/foo/bar/baz/bam', $request->getUriParamString($CI));

        $CI->uri = new MockCI_URI();
        $CI->uri->setReturnValue('uri_to_assoc', array());
        $this->assertEqual('', $request->getUriParamString($CI));
    }

}
