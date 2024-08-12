<?php

use RightNow\UnitTest\Helper,
    RightNow\Utils\Text,
    RightNow\Api;

Helper::loadTestedFile(__FILE__);

class RnowTest extends CPTestCase {
    public $testingClass = 'Rnow';

    function __construct() {
        parent::__construct();
        $this->urlRequest = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/';
    }

    function testcpDisabledAndShouldExit() {
        $configs = Helper::getConfigValues(array(
            'MOD_CP_ENABLED',
            'MOD_CP_DEVELOPMENT_ENABLED',
            'MOD_FEEDBACK_ENABLED',
            'MOD_MA_ENABLED',
        ));

        $className = 'phpFunctional';

        // No exit. MOD_CP_*_ENABLED = 1.
        $method = $this->getMethod('cpDisabledAndShouldExit', true);
        $this->assertFalse($method($className));

        // No exit. MOD_CP_ENABLED = 0 but MOD_CP_DEVELOPMENT_ENABLED = 1 and IS_ADMIN
        \Rnow::updateConfig('MOD_CP_ENABLED', 0, true);
        $this->assertFalse($method($className));

        // Exit. MOD_CP_*_ENABLED = 0.
        \Rnow::updateConfig('MOD_CP_DEVELOPMENT_ENABLED', 0, true);
        $this->assertTrue($method($className));

        // No exit. Despite MOD_CP_*_ENABLED = 0 due to allowed class and/or method names
        $this->assertFalse($method('inlineimage'));
        $this->assertFalse($method('inlineimg'));
        $this->assertFalse($method('answerpreview'));
        $this->assertFalse($method('deploy', 'servicepackdeploy'));

        // Exit if custom controller
        $this->assertTrue($method('inlineimage', null, true));
        $this->assertTrue($method('inlineimg', null, true));
        $this->assertTrue($method('answerpreview', null, true));
        $this->assertTrue($method('deploy', 'servicepackdeploy', true));

        // Marketing exceptions //
        // Exit
        \Rnow::updateConfig('MOD_MA_ENABLED', 0, true);
        \Rnow::updateConfig('MOD_FEEDBACK_ENABLED', 0, true);
        $this->assertTrue($method('friend'));
        $this->assertTrue($method('documents'));

        // No exit
        \Rnow::updateConfig('MOD_MA_ENABLED', 1, true);
        \Rnow::updateConfig('MOD_FEEDBACK_ENABLED', 0, true);
        $this->assertFalse($method('friend'));
        $this->assertFalse($method('documents'));

        // No exit
        \Rnow::updateConfig('MOD_MA_ENABLED', 0, true);
        \Rnow::updateConfig('MOD_FEEDBACK_ENABLED', 1, true);
        $this->assertFalse($method('friend'));
        $this->assertFalse($method('documents'));

        Helper::setConfigValues($configs);
    }

    function testXForwardedForValidateEnduserHosts() {
        \Rnow::updateConfig('SEC_VALID_ENDUSER_HOSTS', '10.0.0.1');
        $remoteaddr = $_SERVER['REMOTE_ADDR'];
        $_SERVER['REMOTE_ADDR'] = '10.0.0.2';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';

        $method = $this->getMethod('validateRemoteAddress', true);
        $output = $method(true);
        $this->assertNULL($output);

        $_SERVER['REMOTE_ADDR'] = $remoteaddr;

        \Rnow::updateConfig('SEC_VALID_ENDUSER_HOSTS', '');
    }


    function testValidateRedirectHost() {
        $url = $this->urlRequest . 'validateRedirectHost/redirect/';

        // no external url's should be allowed
        \Rnow::updateConfig('CP_REDIRECT_HOSTS', '');
        $output = $this->makeRequest($url . urlencode('http://somevalue.com'), array('justHeaders' => true));
        $this->assertStatusCode($output, 403);

        // non-external url's should always be allowed
        $output = $this->makeRequest($url . urlencode('somevalue.com'), array('justHeaders' => true));
        $this->assertStatusCode($output, 200);

        // all external url's should be allowed
        \Rnow::updateConfig('CP_REDIRECT_HOSTS', '*');
        $output = $this->makeRequest($url . urlencode('http://somevalue.com'), array('justHeaders' => true));
        $this->assertStatusCode($output, 200);

        $output = $this->makeRequest($url . urlencode('http://www.oracle.com'), array('justHeaders' => true));
        $this->assertStatusCode($output, 200);

        // only external url's for the specified hosts are allowed
        \Rnow::updateConfig('CP_REDIRECT_HOSTS', '*.foo.com, *.oracle.com');
        $output = $this->makeRequest($url . urlencode('http://somevalue.com'), array('justHeaders' => true));
        $this->assertStatusCode($output, 403);

        $output = $this->makeRequest($url . urldecode('/http%3A%2f%2fsomevalue.com'), array('justHeaders' => true));
        $this->assertStatusCode($output, 403); 

        $output = $this->makeRequest($url . urlencode('http://www.oracle.com'), array('justHeaders' => true));
        $this->assertStatusCode($output, 200);

        // test when IS_PRODUCTION is true
        \Rnow::updateConfig('CP_REDIRECT_HOSTS', '');
        \Rnow::updateConfig('CP_FORCE_PASSWORDS_OVER_HTTPS', false);
        $output = $this->makeRequest('/app/utils/login_form/redirect/' . urlencode("http://www.oracle.com"), array('noDevCookie' => true, 'justHeaders' => true));
        $this->assertSame(1, preg_match("@^\s*Location:.*/app/utils/login_form\s*$@m", $output));

        \Rnow::updateConfig('CP_FORCE_PASSWORDS_OVER_HTTPS', true);
    }

    function testStrictTransportSecurityHeader() {
        \Rnow::updateConfig('SEC_END_USER_HTTPS', 1, false);
        $output = $this->makeRequest('/app', array('justHeaders' => true, 'useHttps' => true));
        $header = 'Strict-Transport-Security: max-age=15724800';
        $this->assertStringContains($output, $header, "Header not found: '$header");
        \Rnow::updateConfig('SEC_END_USER_HTTPS', 0, false);
    }

    function testRedirectToHttpsIfNeeded() {
        \Rnow::updateConfig('SEC_END_USER_HTTPS', 1, false);
        $output = $this->makeRequest('/app', array('justHeaders' => true));
        $header = '301 Moved Permanently';
        $this->assertStringContains($output, $header, "Header not found: '$header");
        $location = 'Location: https://' . \RightNow\Utils\Config::getConfig(OE_WEB_SERVER) . '/app [following]';
        $this->assertStringContains($output, $location, "Location not found: '$location");
        \Rnow::updateConfig('SEC_END_USER_HTTPS', 0, false);
    }

    function testRestrictedAccess () {
        \Rnow::updateConfig('SEC_INVALID_USER_AGENT', 'Chrome');
        $output = $this->makeRequest('/app', array(
            'includeHeaders' => true,
            'noDevCookie'    => true,
            'userAgent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.19 Safari/537.36',
        ));
        $this->assertStringContains($output, '403 Forbidden');
        \Rnow::updateConfig('SEC_INVALID_USER_AGENT', '');
    }

    function validateRedirectHost() {
        list($class, $validateRedirectHost) = $this->reflect('method:validateRedirectHost');
        $validateRedirectHost->invoke(get_instance()->rnow);
    }
}

class CIUserAgentTest extends CPTestCase {
    function testMobileOSAndBrowser() {
        $url = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/' . 'getOSAndBrowser';
        $output = $this->makeRequest($url, array(
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 5_1_1 like Mac OS X; en) AppleWebKit/534.46.0 (KHTML, like Gecko) CriOS/19.0.1084.60 Mobile/9B206 Safari/7534.48.3',
            )
        ));
        $this->assertSame('iOS - Chrome for iOS - 19.0.1084.60', $output);
    }

    function testComplexBrowserDetection(){
        $url = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/' . 'getOSAndBrowser';
        $output = $this->makeRequest($url, array(
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0) like Gecko',
            )
        ));
        $this->assertSame('Windows 8.1 - Internet Explorer - 11.0', $output);

        $output = $this->makeRequest($url, array(
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0',
            )
        ));
        $this->assertSame('Windows 10 - Edge - 12.0', $output);
    }

    function testComplexBrowserDetectionWithNbsp(){
        $url = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/' . 'getOSAndBrowser';

        $output = $this->makeRequest($url, array(
            'headers' => array(
                'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C)',
            )
        ));
        $this->assertSame('Windows 8.1 - Internet Explorer - 7.0', $output);

        $output = $this->makeRequest($url, array(
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko',
            )
        ));
        $this->assertSame('Windows 8.1 - Internet Explorer - 11.0', $output);

        // "\xc2\xa0" - non-breaking space
        $nbsp = "\xc2\xa0";
        $output = $this->makeRequest($url, array(
            'headers' => array(
                'User-Agent' => "Mozilla/5.0{$nbsp}(Windows{$nbsp}NT{$nbsp}6.3;{$nbsp}Trident/7.0;{$nbsp}rv:11.0){$nbsp}like{$nbsp}Gecko",
            )
        ));
        $this->assertSame('Windows 8.1 - Internet Explorer - 11.0', $output);

        // double spaces should still fail the regex
        $output = $this->makeRequest($url, array(
            'headers' => array(
                'User-Agent' => "Mozilla/5.0  (Windows  NT  6.3;  Trident/7.0;  rv:11.0)  like  Gecko",
            )
        ));
        $this->assertSame('Unknown Windows OS - Internet Explorer - 11.0', $output);
    }

    function testBrowserAfterComplex(){
        $url = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/' . 'getOSAndBrowser';
        $output = $this->makeRequest($url, array(
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
            )
        ));
        $this->assertSame('Windows 7 - Firefox - 25.0', $output);
    }

    function getOSAndBrowser() {
        $CI = get_instance();
        $CI->load->library('user_agent');
        echo $CI->agent->platform() . " - " . $CI->agent->browser() . " - " . $CI->agent->version();
    }
}

class CIRouterTest extends CPTestCase {
    function testSessionFor404() {
        $latestClickstreamDate = function() {
            $sql = "SELECT MAX(cs.created) FROM clickstreams cs";

            $si = Api::sql_prepare($sql);
            $i = 0;
            Api::sql_bind_col($si, ++$i, BIND_NTS, 50);

            $row = Api::sql_fetch($si);
            Api::sql_free($si);

            return $row[0];
        };

        $lastDate = $latestClickstreamDate();

        $output = $this->makeRequest('/app/home', array(
            'noDevCookie' => true,
            'includeHeaders' => true,
            'useHttps' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
        ));
        preg_match("/RightNow\.Url\.setSession\('(.*)'\);/", $output, $matches);
        $session = $matches[1];
        $this->assertIsA($session, 'string');
        $this->assertTrue(strlen($session) > 0);

        $output = $this->makeRequest("/app/home/session/$session", array(
            'noDevCookie' => true,
            'justHeaders' => true,
            'useHttps' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
        ));
        $this->assertStatusCode($output, '200 OK');
        $output = $this->makeRequest("/app/blah/home/session/$session", array(
            'noDevCookie' => true,
            'justHeaders' => true,
            'useHttps' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
        ));
        $this->assertStatusCode($output, '404 Not Found');
        $output = $this->makeRequest("/cc/home/session/$session", array(
            'noDevCookie' => true,
            'justHeaders' => true,
            'useHttps' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
        ));
        $this->assertStatusCode($output, '404 Not Found');
        $output = $this->makeRequest("/cc/blah/home/session/$session", array(
            'noDevCookie' => true,
            'justHeaders' => true,
            'useHttps' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
        ));
        $this->assertStatusCode($output, '404 Not Found');
        $output = $this->makeRequest("/cc/blah/home/session/whatever", array(
            'noDevCookie' => true,
            'justHeaders' => true,
            'useHttps' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
        ));
        $this->assertStatusCode($output, '404 Not Found');

        $url = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/' . 'verifySessions/' . urlencode($lastDate);
        $this->assertIdentical('', $this->makeRequest($url));
    }

    function verifySessions(){
        $lastDate = urldecode(Text::getSubstringAfter(get_instance()->uri->uri_string(), 'verifySessions/'));
        $getClickstreams = function($lastDate) {
            $sql = "SELECT cs.cs_session_id, cs.created, act.action FROM clickstreams cs "
                . " JOIN cs_actions act ON act.action_id = cs.action_id "
                . " WHERE cs.created > '$lastDate' "
                . " ORDER BY cs.created";

            $si = Api::sql_prepare($sql);
            $i = 0;
            Api::sql_bind_col($si, ++$i, BIND_NTS, 12);  // cs.cs_session_id
            Api::sql_bind_col($si, ++$i, BIND_NTS, 50);  // cs.created
            Api::sql_bind_col($si, ++$i, BIND_NTS, 256); // act.action

            $rows = array();
            while ($row = Api::sql_fetch($si))
                $rows []= $row;

            Api::sql_free($si);

            return $rows;
        };

        $rows = $getClickstreams($lastDate);

        // four rows for the initial home page hit to get the session (SOURCE, SOURCE_CLIENT, /Home, /ResultList)
        // two rows for the next home page hit with the same session value (/Home, /ResultList)
        // one row for the bad page hit with the same session value (/error404)
        // one row for each of the bad custom controller hits with the same session value (/error404)
        // three rows for the bad custom controller hit with a different session value (SOURCE, SOURCE_CLIENT, /error404)
        $this->assertIdentical(12, count($rows));
        $goodSession = $rows[0][0];
        for ($i = 0; $i < 9; $i++) {
            $this->assertIdentical($goodSession, $rows[$i][0]);
        }
        for ($i = 9; $i < 12; $i++) {
            $this->assertNotIdentical($goodSession, $rows[$i][0]);
        }
    }
}
