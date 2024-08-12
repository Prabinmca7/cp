<?php

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\UnitTest\Helper;

Helper::loadTestedFile(__FILE__);

class SocialTest extends CPTestCase
{
    public $testingClass = 'RightNow\Controllers\Social';

    function __construct() {
        $this->communityUrl = 'http://den01tpo.us.oracle.com';
        parent::__construct();
    }

    function setUp($save = false) {
        Helper::setConfigValues(array(
            'COMMUNITY_ENABLED' => 1,
            'COMMUNITY_PRIVATE_KEY' => 'OXaX8ctYifSiwonq',
            'COMMUNITY_PUBLIC_KEY' => '5JbzLXNAJ0Y3sxAn',
            'COMMUNITY_BASE_URL' => $this->communityUrl,
        ), $save);
        parent::setUp();
    }

    function tearDown($save = false) {
        Helper::setConfigValues(array(
            'COMMUNITY_ENABLED' => 0,
            'COMMUNITY_PRIVATE_KEY' => '',
            'COMMUNITY_PUBLIC_KEY' => '',
            'COMMUNITY_BASE_URL' => '',
        ), $save);
        parent::tearDown();
    }

    function testLogin() {
        // No redirect url supplied
        $response = $this->makeRequest("/ci/Social/login", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, 'Location: /app/error/error_id/4'));

        $response = $this->makeRequest("/ci/Social/login/app%2Fhome", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "app/utils/login_form/redirect/%2Fci%2Fsocial%2FssoRedirect%2F"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
    }

    function testLogout() {
        $response = $this->makeRequest("/ci/Social/logout/%2Fapp%2Fhome", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
    }

    function testSSORedirect() {
        // Save COMMUNITY configs so they are set for the `makeRequest` call below.
        $this->setUp(true);
        // No redirect url supplied
        $response = $this->makeRequest("/ci/Social/ssoRedirect", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, 'Location: /app/error/error_id/4'));

        $redirectUrl = base64_encode(urlencode($this->communityUrl));

        $response = $this->makeRequest("/ci/Social/ssoRedirect/$redirectUrl", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "/app/utils/login_form"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
        $this->assertTrue(Text::stringContains($response, $redirectUrl));
        $this->tearDown(true);
    }

    function testParseRedirect() {
        $method = $this->getMethod('_parseRedirect');
        $urlParameters = 'posts/33cbd2fc47?commentId=141';
        $url = "{$this->communityUrl}/{$urlParameters}";

        $parseRedirect = function($url = null) use ($method) {
            return $method(base64_encode(urlencode($url)));
        };

        // No hash fragment
        $expected = array($url, '');
        $actual = $parseRedirect($url);
        $this->assertIdentical($expected, $actual);

        // valid hash fragment
        $expected = array($url, '#141');
        $actual = $parseRedirect("{$url}#141");
        $this->assertIdentical($expected, $actual);

        // Ensure we only match a trailing hash fragment
        $redirectUrl = "{$this->communityUrl}/posts/33cbd2fc47?someParam=#141&commentId=141";
        $expected = array($redirectUrl, '#141');
        $actual = $parseRedirect("{$redirectUrl}#141");
        $this->assertIdentical($expected, $actual);
    }

    function testValidateRedirect() {
        $method = $this->getMethod('_validateRedirect');
        $urlParameters = 'posts/33cbd2fc47?commentId=141';
        $url = "{$this->communityUrl}/{$urlParameters}";

        $validateRedirect = function($url = null) use ($method) {
            return $method(base64_encode(urlencode($url)));
        };

        // No redirect URL
        $results = $validateRedirect();
        $this->assertTrue(array_key_exists('error', $results));
        $this->assertNull($results['error']);

        // Redirect URL not the same hostname as COMMUNITY_BASE_URL
        $results = $validateRedirect("http://somewhere.else/$urlParameters");
        $this->assertTrue(array_key_exists('error', $results));
        $this->assertIsA($results['error'], 'integer');

        // Invalid redirect URL that doesn't resolve to a 'host'
        $results = $validateRedirect('not-a-valid-url');
        $this->assertTrue(array_key_exists('error', $results));
        $this->assertIsA($results['error'], 'integer');

        // Valid Redirect URL but not logged in redirects to login page
        $results = $validateRedirect($url);
        $this->assertStringContains($results['location'], 'login_form/redirect/');

        // Redirect to login page contains valid hashed redirect back to community page and retains hash fragment
        $redirectUrl = "{$url}#141";
        $results = $validateRedirect($redirectUrl);
        $this->assertStringContains($results['location'], 'login_form/redirect/');
        $redirect = htmlspecialchars_decode(urldecode(Text::getSubstringAfter($results['location'], 'login_form/redirect/')));
        $this->assertStringContains($redirect, '/ci/social/ssoRedirect/');
        $hash = Text::getSubstringAfter($redirect, 'ci/social/ssoRedirect/');
        if (Text::stringContains($hash, '/'))
            $hash = Text::getSubstringBefore($hash, '/');
        $decoded = base64_decode($hash);
        $this->assertEqual($redirectUrl, $decoded, "Expected '$redirectUrl', but got '$redirect', '$hash', and '$decoded'");

        $this->logIn();

        // Valid Redirect URL with user logged in
        $results = $validateRedirect($url);
        $this->assertStringContains($results['location'], "{$url}&opentoken=");

        // Valid Redirect URL containing a hash fragment and user logged in
        $results = $validateRedirect("{$url}#141");
        $this->assertStringContains($results['location'], "{$url}&opentoken=");
        // hash fragment is tacked on to the end
        $this->assertEqual('#141', substr($results['location'], -4));

        $this->logOut();
    }

    function testGenerateLoginUrl() {
        $method = $this->getMethod('_generateLoginUrl');
        $urlParameters = 'posts/33cbd2fc47?commentId=141';
        $url = "{$this->communityUrl}/{$urlParameters}";

        $expected = function($url) {
            return 'app/utils/login_form/redirect/' . urlencode('/ci/social/ssoRedirect/' . base64_encode($url));
        };

        $results = $method($url);
        $this->assertStringContains($results, $expected($url));

        $redirectUrl = "{$url}#141";
        $results = $method($redirectUrl);
        $this->assertStringContains($results, $expected($redirectUrl));
    }

    function testSSOError() {
        $response = $this->makeRequest("/ci/Social/ssoError/11", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "/app/error/error_id/sso11"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Social/ssoError/12", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "/app/error/error_id/4"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Social/ssoError/15", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "/app/error/error_id/sso15"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Social/ssoError/16", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "/app/error/error_id/sso16"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Social/ssoError/17", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "/app/error/error_id/sso17"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
    }
}
