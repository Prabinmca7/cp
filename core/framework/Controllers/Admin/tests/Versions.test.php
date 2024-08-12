<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text,
    RightNow\Utils\Widgets,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\Widget\Documenter;

class VersionsTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Admin\Versions';

    private $restoreWritableFiles = array();

    function __construct($label = null) {
        parent::__construct($label);

        // Some of these test cases end up modifying files.
        // Be a dear and clean up after yourself.
        $this->restoreWritableFiles = array(
            APPPATH . 'widgetVersions'  => '',
            APPPATH . 'versionAuditLog' => '',
        );
    }

    function setUp() {
        foreach ($this->restoreWritableFiles as $name => $_) {
            $this->restoreWritableFiles[$name] = file_get_contents($name);
        }
    }

    function tearDown() {
        foreach ($this->restoreWritableFiles as $name => $content) {
            FileSystem::filePutContentsOrThrowExceptionOnFailure($name, $content);
        }
    }

    function testRestrictedMethods() {
        $restricted = array(
            '/ci/admin/versions/modifyFrameworkVersion',
            '/ci/admin/versions/modifyWidgetVersions',
            '/ci/admin/versions/updatePhpVersion',
        );

        foreach ($restricted as $url) {
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
                'admin'       => true,
                'flags'       => " --post-data='foo=bar' --header='X-Requested-With: XMLHttpRequest'",
                'justHeaders' => true,
            ));
            $this->assertTrue(Text::stringContains($allowed, '200 OK'));
            $allowed = $this->makeRequest($url, array(
                'admin' => true,
                'flags' => " --post-data='foo=bar' --header='X-Requested-With: XMLHttpRequest'",
            ));
            $this->assertTrue(Text::stringContains($allowed, '{"success":false}'));

            // 191107-000037 kick out bogus version numbers
            $allowed = $this->makeRequest($url, array(
                'admin' => true,
                'flags' => " --post-data='version=11111' --header='X-Requested-With: XMLHttpRequest'",
            ));
            $this->assertTrue(Text::stringContains($allowed, '{"success":false}'));
        }
    }

    function testGetChangelog() {
        $result = $this->makeRequest('/ci/admin/versions/getChangelog/standard%2Fchat%2FChatAttachFileButton', array('admin' => true));
        $result = json_decode($result, true);
        $this->assertIsA($result, 'array');
        $this->assertTrue(count($result) > 0);

        $result = $this->makeRequest('/ci/admin/versions/getChangelog/..%2Fstandard%2Fchat%2FChatAttachFileButton', array('admin' => true));
        $result = json_decode($result, true);
        $this->assertIsA($result, 'array');
        $this->assertIdentical(0, count($result));
    }

    function testGetChangeHistory() {
        $result = $this->makeRequest('/ci/admin/versions/getChangeHistory', array('admin' => true));
        $this->assertIsA(json_decode($result), 'array');
    }

    function testGetViewsUsedOn() {
        $headers = $this->makeRequest('/ci/admin/versions/getViewsUsedOn/standard%2Finput%2FFormInput', array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($headers, 'Cache-Control: no-cache, no-store'));
        $this->assertTrue(Text::stringContains($headers, 'Content-Type: application/json'));

        // hit it again, since now this should be cached and we want to verify we still output the same headers
        $headers = $this->makeRequest('/ci/admin/versions/getViewsUsedOn/standard%2Finput%2FFormInput', array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($headers, 'Cache-Control: no-cache, no-store'));
        $this->assertTrue(Text::stringContains($headers, 'Content-Type: application/json'));

        // hit it again and examine the results
        $result = $this->makeRequest('/ci/admin/versions/getViewsUsedOn/standard%2Finput%2FFormInput', array('admin' => true));
        $result = json_decode($result, true);

        // Depending on whether the rendering tests have been executed on the site, there
        // may be unitTest pages in this result set. Don't check those, since rendering
        // tests shouldn't be a dependency on this functional test.
        $widgets = array(
           'standard/input/CustomAllInput'   => 'standard',
           'standard/login/LoginDialog'      => 'standard',
           'standard/login/ResetPassword'    => 'standard',
        );
        $referenceImplementationPages = array(
           'pages/account/profile.php'                 => 'view',
           'pages/account/questions/detail.php'        => 'view',
           'pages/ask.php'                             => 'view',
           'pages/chat/chat_launch.php'                => 'view',
           'pages/mobile/account/profile.php'          => 'view',
           'pages/mobile/account/questions/detail.php' => 'view',
           'pages/mobile/ask.php'                      => 'view',
           'pages/mobile/chat/chat_launch.php'         => 'view',
           'pages/mobile/social/ask.php'               => 'view',
           'pages/mobile/utils/create_account.php'     => 'view',
           'pages/okcs/account/profile.php'            => 'view',
           'pages/okcs/ask.php'                        => 'view',
           'pages/okcs/mobile/account/profile.php'     => 'view',
           'pages/okcs/mobile/ask.php'                 => 'view',
           'pages/okcs/mobile/utils/create_account.php'=> 'view'
        );
        // ContactNameInput may also appear, so remove that, too
        if (array_slice($result['references'], 0, 1) === array('standard/input/ContactNameInput' => 'standard')) {
            $result['references'] = array_slice($result['references'], 1);
        }

        $this->assertIdentical($widgets, array_intersect_assoc($widgets, $result['references']));
        $this->assertIdentical($referenceImplementationPages, array_intersect_assoc($referenceImplementationPages, $result['references']));
        $this->assertIsA(strtotime($result['lastCheckTime']), 'int');

        $result = $this->makeRequest('/ci/admin/versions/getViewsUsedOn/non%2Fexistent%2FWidget', array('admin' => true));
        $result = json_decode($result, true);
        $this->assertIdentical(false, $result['references']);
        $this->assertIsA(strtotime($result['lastCheckTime']), 'int');
    }

    function testGetWidgetFileUsage() {
        $headers = $this->makeRequest('/ci/admin/versions/getWidgetFileUsage/standard%2Finput%2FFormInput/standard/standard%2Finput%2FCustomAllInput', array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($headers, 'Cache-Control: no-cache'));
        $this->assertTrue(Text::stringContains($headers, 'Content-Type: application/json'));

        // hit it again, since now this should be cached and we want to verify we still output the same headers
        $headers = $this->makeRequest('/ci/admin/versions/getWidgetFileUsage/standard%2Finput%2FFormInput/standard/standard%2Finput%2FCustomAllInput', array('admin' => true, 'justHeaders' => true));
        $this->assertTrue(Text::stringContains($headers, 'Cache-Control: no-cache'));
        $this->assertTrue(Text::stringContains($headers, 'Content-Type: application/json'));
    }

    function testCheckForUseOfWidgetParent() {
        $checkForUseOfWidgetParent = $this->getMethod('checkForUseOfWidgetParent');

        $widgetUsage = array(
            'noLongerUsed' => array("custom/sample/Walrus"),
            'possiblyUsed' => array(
                'custom/sample/Shoes' => array(
                    'custom/sample/Walrus' => array(
                        "custom",
                    ),
                ),
                'custom/sample/Books' => array(
                    'custom/sample/Shoes' => array(
                        "custom",
                    ),
                ),
                'custom/sample/Bananas' => array(
                    'pages/ask.php' => array(
                        "view",
                    ),
                ),
                'custom/sample/Raspberry' => array(
                    'custom/sample/Walrus' => array(
                        "custom",
                    ),
                    'pages/ask.php' => array(
                        "view",
                    ),
                ),
                'custom/sample/Kiwi' => array(
                    'custom/sample/Walrus' => array(
                        "custom",
                    ),
                    'custom/sample/Shoes' => array(
                        "custom",
                    ),
                ),
            ),
        );

        $results = $checkForUseOfWidgetParent($widgetUsage);

        $this->assertNotNull($results);
        $this->assertTrue(count($results['noLongerUsed']) > 0);
        $this->assertTrue(count($results['possiblyUsed']) > 0);
        $this->assertTrue(in_array("custom/sample/Walrus", $results['noLongerUsed']));
        $this->assertTrue(in_array("custom/sample/Shoes", $results['noLongerUsed']));
        $this->assertTrue(in_array("custom/sample/Books", $results['noLongerUsed']));
        $this->assertTrue(in_array("custom/sample/Kiwi", $results['noLongerUsed']));
        $this->assertNotNull($results['possiblyUsed']['custom/sample/Bananas']);
        $this->assertNotNull($results['possiblyUsed']['custom/sample/Raspberry']);

    }

    function testGetWidgetDocs() {
        require_once(CPCORE . 'Internal/Libraries/Widget/Documenter.php');
        require_once(CPCORE . 'Internal/Utils/VersionTracking.php');
        \RightNow\Internal\Utils\VersionTracking::initializeCache();
        $baseUrl = '/ci/admin/versions/getWidgetDocs';

        // Grab first standard widget and gather some data
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $widgets = Registry::getStandardWidgets();
        $widgetInfo = array_shift($widgets);
        $widgetPath = $widgetInfo['relativePath'];
        $widget = Registry::getWidgetPathInfo($widgetPath, null);
        $digits = explode('.', $widget->meta['version']);
        $version = "{$digits[0]}.{$digits[1]}";
        $details = Documenter::getWidgetDetails($widget, array(
            'events' => true,
            'previewFiles' => false,
        ));
        $labels = array_keys($details['attributes']['labels']['values']);
        $options = array('admin' => true);

        // Invalid widget path
        $response = $this->makeRequest("$baseUrl/standard%2Fchat%2FChatAttachFileMutton/1.0", $options);
        $this->assertTrue(Text::stringContains($response, 'A PHP Error was encountered'));

        // Activated widget returns documentation
        $encodedWidgetPath = urlencode($widgetPath);
        $response = $this->makeRequest("$baseUrl/$encodedWidgetPath/$version", $options);
        $this->assertIsA($response, 'string');

        // Ensure all labels are in response so we know we're getting a documentation view
        foreach($labels as $label) {
            $this->assertTrue(Text::stringContains($response, $label), "Label '$label' not found in response");
        }

        // Deactivated widget. Should still return documentation
        $this->assertIsA(Widgets::updateWidgetVersion($widgetPath, 'remove', true), 'array');
        Registry::initialize(true);
        $response = $this->makeRequest("$baseUrl/$encodedWidgetPath/$version", $options);
        foreach($labels as $label) {
            $this->assertTrue(Text::stringContains($response, $label), "Label '$label' not found in response");
        }
        $this->assertIsA(Widgets::updateWidgetVersion($widgetPath, $version, true), 'array');

        $widgetVersions[$widgetPath] = 'current';
        Widgets::updateDeclaredWidgetVersions($widgetVersions);

        Registry::initialize(true);
    }

    function testGetImageNames() {
        $getImageNames = $this->getMethod('getImageNames');
        $this->assertIdentical(array(), $getImageNames(null));
        $this->assertIdentical(array(false), $getImageNames(array('asdf')));
        $this->assertIdentical(array('asdf'), $getImageNames(array('path/asdf')));
        $this->assertIdentical(array('asdf'), $getImageNames(array('path/path2/asdf')));
        $this->assertIdentical(array('asdf', 'qwert'), $getImageNames(array('path/qwert', 'path/asdf')));
        $this->assertIdentical(array('preview.png', 'asdf', 'qwert'), $getImageNames(array('path/qwert', 'path/preview.png', 'path/asdf')));
    }
}
