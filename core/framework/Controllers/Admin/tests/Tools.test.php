<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Controllers\UnitTest\PhpFunctional;

class ToolsTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Admin\Tools';

    function testGetWidgetInfo() {
        $url = '/ci/admin/tools/widgetBuilder/getWidgetInfo/attributes/';
        $this->assertTrue(Text::stringContains($this->makeRequest($url, array('admin' => true, 'justHeaders' => true)), '200 OK'));
        $result = $this->makeRequest($url . 'banana/bar/baz', array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($result, '200 OK'));
        $this->assertTrue(Text::stringContains($result, 'text/html'));

        $result = $this->makeRequest($url . urlencode('standard/utils/EmailAnswerLink'), array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($result, 'application/json'));
        $result = $this->makeRequest($url . urlencode('standard/utils/EmailAnswerLink'), array('admin' => true));
        $this->assertTrue(Text::stringContains($result, 'label_cancel_button'));

        $url = '/ci/admin/tools/widgetBuilder/getWidgetInfo/urlParameters/';
        $this->assertTrue(Text::stringContains($this->makeRequest($url, array('admin' => true, 'justHeaders' => true)), '200 OK'));
        $result = $this->makeRequest($url . 'banana/bar/baz', array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($result, '200 OK'));
        $this->assertTrue(Text::stringContains($result, 'text/html'));

        $result = $this->makeRequest($url . urlencode('standard/utils/EmailAnswerLink'), array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($result, 'application/json'));
        $result = $this->makeRequest($url . urlencode('standard/utils/EmailAnswerLink'), array('admin' => true));
        $this->assertTrue(Text::stringContains($result, 'a_id'));

        $url = '/ci/admin/tools/widgetBuilder/getWidgetInfo/jsModule/';
        $this->assertTrue(Text::stringContains($this->makeRequest($url, array('admin' => true, 'justHeaders' => true)), '200 OK'));
        $result = $this->makeRequest($url . 'banana/bar/baz', array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($result, '200 OK'));
        $this->assertTrue(Text::stringContains($result, 'text/html'));

        $result = $this->makeRequest($url . urlencode('standard/utils/EmailAnswerLink'), array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($result, 'application/json'));
        $result = $this->makeRequest($url . urlencode('standard/utils/EmailAnswerLink'), array('admin' => true));
        $this->assertTrue(Text::stringContains($result, 'standard'));
    }

    function testBuildWidget() {
        $url = '/ci/admin/tools/widgetBuilder/buildWidget';

        $this->assertTrue(Text::stringContains($this->makeRequest($url, array('admin' => true, 'justHeaders' => true)), '404: Not Found.'));
        $this->assertTrue(Text::stringContains($this->makeRequest($url, array(
            'admin'       => true,
            'flags'       => " --post-data='foo=bar'",
            'justHeaders' => true,
        )), '404: Not Found.'));

        $this->assertTrue(Text::stringContains($this->makeRequest($url, array(
            'admin'       => true,
            'flags'       => " --header='X-Requested-With: XMLHttpRequest'",
            'justHeaders' => true,
        )), '404: Not Found.'));

        $this->assertTrue(Text::stringContains($this->makeRequest($url, array('admin' => true, 'justHeaders' => true)), '404: Not Found.'));
        $allowed = $this->makeRequest($url, array(
            'admin' => true,
            'flags' => " --post-data='foo=bar' --header='X-Requested-With: XMLHttpRequest'",
        ));
        $this->assertTrue(Text::stringContains($allowed, '{"error":'));
    }

    /**
     * Verifies the existence and validity of the data.json file in YUI_SOURCE_DIR.
     * The YUI Module functionality for the Widget Builder requires this file to be kosher.
     */
    function testYUIDataJSON() {
        $yuiInfo = json_decode($this->makeRequest(Url::getYUICodePath('data.json')), true);
        $this->assertIsA($yuiInfo['modules'], 'array');
    }
}

require_once CPCORE . 'Controllers/UnitTest/simpletest1.1beta/web_tester.php';

class FrameworkMigrationToolsTest extends WebTestCase {
    function __construct() {
        $this->versionFile = CUSTOMER_FILES . 'frameworkVersion';
    }

    function setUp() {
        $this->preTestVersion = file_get_contents($this->versionFile);
        parent::setUp();
    }

    function tearDown() {
        // Revert back to orig. framework version
        file_put_contents($this->versionFile, $this->preTestVersion);

        if (!IS_HOSTED) {
            // Revert htmlroot symlink that Admin#back2cp2 sets
            $symlinkLocation = HTMLROOT . '/euf';
            $actualLocation = realpath($symlinkLocation);
            $actualLocation = str_ireplace("rnw/scripts/euf", "rnw/scripts/cp", $actualLocation);
            @unlink($symlinkLocation);
            @symlink($actualLocation, $symlinkLocation);
        }
        parent::tearDown();
    }

    function testSwitchBack() {
        if (!($downgradeAllowed = \RightNow\Utils\Config::getConfig(CP_DOWNGRADE_TO_V2_ALLOWED))) {
            \Rnow::updateConfig('CP_DOWNGRADE_TO_V2_ALLOWED', 1);
        }
        // Go back to CP 2
        $this->addHeader('Authorization:Basic ' . base64_encode('admin:'));
        $this->get(Url::getShortEufBaseUrl(null, '/ci/admin/tools/migrateframework'));

        $this->assertTitle(\RightNow\Utils\Config::getMessage(FRAMEWORK_MIGRATION_LBL));

        $this->setMaximumRedirects(0);
        $this->assertText('Switch back to v2 →');
        $this->click('Switch back to v2 →');
        $this->assertResponse(302);

        // Now in CP 2 dev mode
        $this->assertTrue('2.0' === file_get_contents($this->versionFile));
        $this->assertCookie('location', new PatternExpectation('/development%7E/i'));
        $this->assertHeader('Location', '/');

        // Wanna revert back
        $this->get(Url::getShortEufBaseUrl(null, '/ci/admin/migrateFramework'));
        $this->assertResponse(200);
        $this->assertNoPattern('/\<div class="info"\>/');
        $this->assertText('Switch to the new framework →');
        
        $this->click('Switch to the new framework →');
        //Commented on 12/05/2023 #231130-000028
        //$this->assertResponse(302);

        // Back in CP 3
        //$this->assertTrue(CP_FRAMEWORK_VERSION === file_get_contents($this->versionFile));
        //$this->assertHeader('Location', '/ci/admin/docs/help/migrate');
        
        if (!$downgradeAllowed) {
            \Rnow::updateConfig('CP_DOWNGRADE_TO_V2_ALLOWED', 0);
        }
    }
}
