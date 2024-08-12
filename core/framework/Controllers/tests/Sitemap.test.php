<?php
use RightNow\Utils\Text,
    RightNow\Utils\Url;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SitemapTest extends CPTestCase
{
    public $testingClass = 'RightNow\Controllers\Sitemap';

    function __construct() {
        $this->originalConfigValues = RightNow\UnitTest\Helper::getConfigValues(array(
            'KB_SITEMAP_ENABLE',
            'SSS_DISCUSSION_SITEMAP_ENABLE'
        ));
        // saving config changes to database
        $this->save = true;

        parent::__construct();
    }

    function testIndex() {
        $this->setConfigs(array('KB_SITEMAP_ENABLE' => true, 'SSS_DISCUSSION_SITEMAP_ENABLE' => false));
        $response = $this->makeRequest("/ci/Sitemap", array('noDevCookie' => true));

        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");

        // atleast KB answers will always be there in sitemap
        $this->assertStringContains($response, "sitemap/answers/page/1");

        // Since makeRequest sends a Dev Mode cookie, verify that php errors and warnings aren't output :)
        $this->assertStringDoesNotContain($response, 'Error');
        $this->assertStringDoesNotContain($response, 'Warning');

        // test no sitemap generated when KB_SITEMAP_ENABLE is false
        $this->setConfigs(array('KB_SITEMAP_ENABLE' => false, 'SSS_DISCUSSION_SITEMAP_ENABLE' => true));
        $response = $this->makeRequest("/ci/Sitemap", array('noDevCookie' => true));
        $this->assertIsA($response, 'string');
        $this->assertStringContains($response, "No sitemap available");
        $this->resetConfigs();
    }

    function testXmlSitemapPage() {
        $response = $this->makeRequest("/ci/Sitemap/answers/page/1", array('noDevCookie' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertStringContains($response, Url::defaultAnswerUrl(null));
        preg_match('/<priority>\d\.\d<\/priority>/i', $response, $matches);
        $this->assertNotNull($matches, "Priority value is not found");

        // negative test case - requesting page which can't exist. You cannot have more than 50,000 urls on sitemap index file
        $response = $this->makeRequest("/ci/Sitemap/answers/page/50001", array('noDevCookie' => true));
        $this->assertIsA($response, 'string');
        $this->assertStringContains($response, "No sitemap available");
    }

    function testHtml() {
        $response = $this->makeRequest("/ci/Sitemap/html/", array('noDevCookie' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertStringContains($response, "HTML Sitemap");
        $this->assertStringContains($response, "/ci/sitemap/html/answers/page/1");
    }

    function testHtmlSitemapPage() {
        $response = $this->makeRequest("/ci/Sitemap/html/answers/page/1", array('noDevCookie' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertStringContains($response, Url::defaultAnswerUrl(null));

        // negative test case - requesting page which can't exist. You cannot have more than 50,000 urls on sitemap index file
        $response = $this->makeRequest("/ci/Sitemap/html/answers/page/50001", array('noDevCookie' => true));
        $this->assertIsA($response, 'string');
        $this->assertStringContains($response, "No sitemap available");
    }

    function testXmlIndexGeneration() {
        $this->downgradeErrorReporting();
        $method = $this->getMethod('_outputXmlResults');
        $pages = 10;
        ob_start();
        $method(array('totalPages' =>  array('answers' => $pages)));
        $result = ob_get_clean();

        // Output buffering doesn't capture headers.
        // So change back from text/xml that the writer outputs.
        header('Content-type: text/html');

        $url = \RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage', '/ci/sitemap/answers/page/');
        while ($pages) {
            $this->assertStringContains($result, "<loc>{$url}{$pages}</loc>");
            $pages--;
        }
        $this->restoreErrorReporting();
    }

    function testHtmlIndexGeneration() {
        $this->downgradeErrorReporting();
        $method = $this->getMethod('_outputHtmlResults');
        $pages = 10;
        ob_start();
        $method(array('totalPages' =>  array('answers' => $pages)));
        $result = ob_get_clean();

        $url = \RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage', '/ci/sitemap/html/answers/page/');
        while ($pages) {
            $this->assertStringContains($result, "{$url}{$pages}");
            $pages--;
        }
        $this->restoreErrorReporting();
    }

    function testXmlPageGeneration() {
        $this->downgradeErrorReporting();
        $method = $this->getMethod('_outputXmlResults');
        $data['data'][0] = array("<a href='/app/answers/detail/a_id/52' >52</a>", 22, 1269411000, "Enabling MMS on iPhone 3G and iPhone 3GS");
        ob_start();
        $method($data, 'answers');
        $result = ob_get_clean();

        // Output buffering doesn't capture headers.
        // So change back from text/xml that the writer outputs.
        header('Content-type: text/html');

        $url = \RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage', Url::defaultAnswerUrl(52));
        $this->assertStringContains($result, $url);
        preg_match('/<priority>\d\.\d<\/priority>/i', $result, $matches);
        $this->assertNotNull($matches, "Priority value is not found");
        $this->restoreErrorReporting();
    }

    function testHtmlPageGeneration() {
        $this->downgradeErrorReporting();
        $method = $this->getMethod('_outputHtmlResults');
        $data['data'][0] = array("<a href='/app/answers/detail/a_id/52' >52</a>", 22, 1269411000, "Enabling MMS on iPhone 3G and iPhone 3GS");
        ob_start();
        $method($data, 'answers');
        $result = ob_get_clean();

        $url = \RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage', Url::defaultAnswerUrl(52));
        $this->assertStringContains($result, $url);
        $this->restoreErrorReporting();
    }

    function testErrorOutput() {
        $this->downgradeErrorReporting();
        $method = $this->getMethod('_errorOutput');
        ob_start();
        $method();
        $result = ob_get_clean();

        $this->assertStringContains($result, "No sitemap available");
        $this->restoreErrorReporting();
    }


    function resetConfigs() {
        $this->setConfigs($this->originalConfigValues, $this->save);
    }

    function setConfigs(array $configs){
        // Set locally
        RightNow\UnitTest\Helper::setConfigValues($configs, $this->save);
    }
}
