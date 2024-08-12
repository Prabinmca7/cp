<?php
use RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class CacheControllerTest extends CPTestCase {
    function testRss() {
        $response = $this->makeRequest('/ci/cache/rss');
        $this->assertTrue(Text::stringContains($response, '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'));
        $match = array();
        $this->assertSame(19, preg_match_all('/\<item\>/i', $response, $match));
    }

    function testDisabledRss(){
        $contactLoginConfig = \RightNow\Utils\Config::getConfig(CP_CONTACT_LOGIN_REQUIRED);
        \Rnow::updateConfig('CP_CONTACT_LOGIN_REQUIRED', true);

        $output = $this->makeRequest("/ci/cache/rss", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($output, '403 Forbidden'), "Output did not respond with a 403 - $output");

        \Rnow::updateConfig('CP_CONTACT_LOGIN_REQUIRED', $contactLoginConfig);
    }

    function testSocialRss () {
        $response = $this->makeRequest('/ci/cache/socialrss');
        $this->assertTrue(Text::stringContains($response, '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'));
        $this->assertNotEqual(0, preg_match('/\<item\>/i', $response));
    }

    function testSocialRssWithIncorrectProductHierarchy () {
        $response = $this->makeRequest('/ci/cache/socialrss/p/8');
        $this->assertTrue(Text::stringContains($response, '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'));
        $this->assertEqual(0, preg_match('/\<item\>/i', $response));
    }

    function testSocialRssWithCorrectProductHierarchy () {
        $response = $this->makeRequest('/ci/cache/socialrss/p/1,2');
        $this->assertTrue(Text::stringContains($response, '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'));
        $this->assertNotEqual(0, preg_match('/\<item\>/i', $response));
    }
}
