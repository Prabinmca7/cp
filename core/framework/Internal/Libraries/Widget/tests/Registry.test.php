<?php

use RightNow\Utils\Text,
    RightNow\Utils\Widgets,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\Registry;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
class WidgetRegistryTest extends CPTestCase {

    public $testingClass = 'RightNow\Internal\Libraries\Widget\Registry';

    function __construct() {
        $this->shortPath = 'chat/ChatAgentStatus';
        $this->widgetKey = $this->relativePath = 'standard/' . $this->shortPath;
        $this->absolutePath = CORE_WIDGET_FILES . $this->widgetKey;
        $this->widgetName = basename($this->shortPath);
        $this->widgetCategory = dirname($this->shortPath);

        $this->shortPathDup = 'chat/ChatCancelButton';
        $this->widgetKeyDup = $this->relativePathDup = 'standard/' . $this->shortPathDup;
        $this->absolutePathDup = CORE_WIDGET_FILES . $this->widgetKeyDup;
        $this->widgetNameDup = basename($this->shortPathDup);
        $this->widgetCategoryDup = dirname($this->shortPathDup);

        $this->customBase = CUSTOMER_FILES . 'widgets/custom/';
        $this->shortPathCustom = 'sample/SampleWidget';
        $this->widgetKeyCustom = $this->relativePathCustom = 'custom/' . $this->shortPathCustom;
        $this->absolutePathCustom = CUSTOMER_FILES . "widgets/$this->widgetKeyCustom";

        $this->shortPathCustomDup = $this->shortPathDup;
        $this->widgetKeyCustomDup = $this->relativePathCustomDup = 'custom/' . $this->shortPathCustomDup;
        $this->absolutePathCustomDup = CUSTOMER_FILES . "widgets/$this->widgetKeyCustomDup";


        $this->allWidgets = array(
          $this->shortPath, $this->relativePath,
          $this->shortPathDup, $this->relativePathDup,
          $this->shortPathCustom, $this->relativePathCustom,
          $this->shortPathCustomDup, $this->relativePathCustomDup,
        );

        $widgetNameCustom = 'Custom' . $this->widgetName;
        $this->customWidgetPaths = array(
          array($this->absolutePathDup, $this->customBase . $this->widgetNameDup),
          array($this->absolutePathDup, $this->customBase . $this->widgetCategoryDup . '/' . $this->widgetNameDup),
          array($this->absolutePathDup, $this->customBase . $this->widgetCategoryDup . '/foo/' . $this->widgetNameDup),
        );
    }

    function setUp() {
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        foreach ($this->customWidgetPaths as $paths) {
            list($source, $_target) = $paths;
            foreach (array($_target, $_target . 'Custom') as $target) {
                $widgetVersions[Text::getSubstringAfter($target, CUSTOMER_FILES . 'widgets/')] = '1.0';
                FileSystem::copyDirectory($source, $target . '/1.0', false, true);
            }
        }
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Registry::setSourceBasePath(CUSTOMER_FILES, true);
        parent::setUp();
    }

    function tearDown() {
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        foreach ($this->customWidgetPaths as $paths) {
            unset($widgetVersions[Text::getSubstringAfter($paths[1], CUSTOMER_FILES . 'widgets/', 'blah')]);
            unset($widgetVersions[Text::getSubstringAfter($paths[1] . 'Custom', CUSTOMER_FILES . 'widgets/', 'blah')]);
            foreach (array(dirname($paths[1]), $paths[1], $paths[1] . 'Custom') as $target) {
                if (is_dir($target) && $target !== $this->customBase && (($target . '/') !== $this->customBase)) {
                    FileSystem::removeDirectory($target, true);
                }
            }
        }
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Registry::initialize(true);
        parent::tearDown();
    }

    function testGetAllWidgets() {
        $widgets = Registry::getAllWidgets(true); // Initializing
        $this->assertTrue(array_key_exists($this->widgetKey, $widgets));
        $this->assertTrue(array_key_exists($this->widgetKeyCustom, $widgets));
        $this->assertTrue(array_key_exists($this->widgetKeyDup, $widgets));
        $this->assertTrue(array_key_exists($this->widgetKeyCustomDup, $widgets));
    }

    function testInitialize() {
        return; //placeholder
    }

    function testSetTargetPages() {
        $widgetTypes = Registry::getWidgetTypes();
        $this->assertEqual(Registry::getBaseCustomWidgetPath(), $widgetTypes['custom']);

        // If site has not been deployed, the path below will not exist.
        try {
            FileSystem::mkdirOrThrowExceptionOnFailure(OPTIMIZED_FILES . 'production/source/widgets/custom', true);
        }
        catch (\Exception $e) {
            print("WARNING: $e<br>");
        }

        Registry::setTargetPages('production');
        $widgetTypes = Registry::getWidgetTypes();
        $this->assertEqual(OPTIMIZED_FILES . 'production/source/widgets/', $widgetTypes['custom']);

        Registry::setTargetPages('development');
        $widgetTypes = Registry::getWidgetTypes();
        $this->assertEqual(Registry::getBaseCustomWidgetPath(), $widgetTypes['custom']);
    }

    function testGetStandardWidgets() {
        $addFullPath = false;
        $allStandardWidgets = Registry::getStandardWidgets($addFullPath);
        $this->assertTrue(array_key_exists($this->widgetKey, $allStandardWidgets));
        $this->assertFalse(array_key_exists($this->widgetKeyCustom, $allStandardWidgets));
        $this->assertFalse(array_key_exists($this->widgetKeyCustomDup, $allStandardWidgets));

        $addFullPath = true;
        $standardWidgets = Registry::getStandardWidgets($addFullPath);
        $this->assertTrue(array_key_exists($this->absolutePath, $standardWidgets));
        $this->assertFalse(array_key_exists($this->absolutePathCustom, $standardWidgets));
        $this->assertFalse(array_key_exists($this->absolutePathCustomDup, $standardWidgets));
    }

    function testGetCustomWidgets() {
        $addFullPath = false;
        $widgets = Registry::getCustomWidgets($addFullPath);
        $this->assertTrue(array_key_exists($this->widgetKeyCustom, $widgets));
        $this->assertTrue(array_key_exists($this->widgetKeyCustomDup, $widgets));
        $this->assertFalse(array_key_exists($this->widgetKey, $widgets));

        $addFullPath = true;
        $widgets = Registry::getCustomWidgets($addFullPath);
        $this->assertTrue(array_key_exists($this->absolutePathCustom, $widgets));
        $this->assertTrue(array_key_exists($this->absolutePathCustomDup, $widgets));
    }

    function testIsWidget() {
        foreach ($this->allWidgets as $path) {
            $this->assertTrue(Registry::isWidget($path));
            $this->assertFalse(Registry::isWidget($path . '/foo'));
        }
    }

    function testIsWidgetOnDisk() {
        foreach($this->allWidgets as $path) {
            $this->assertTrue(Registry::isWidgetOnDisk($path));
            $this->assertFalse(Registry::isWidgetOnDisk($path . '/foo'));
        }

        //Create a deactivated widget, re-init the system and verify that it is 'on disk'
        $customWidgetDirectory = 'custom/input';
        $widgetPath = "$customWidgetDirectory/test" . time();
        $absoluteWidgetPath = CUSTOMER_FILES . "widgets/$widgetPath/1.0";
        FileSystem::mkdirOrThrowExceptionOnFailure($absoluteWidgetPath, true);
        file_put_contents($absoluteWidgetPath . '/info.yml', 'version: 1.0');
        Registry::initialize(true);

        //The widget shouldn't be activated
        $this->assertFalse(Registry::isWidget($widgetPath));

        //However, it should be on disk
        $this->assertTrue(Registry::isWidgetOnDisk($widgetPath));

        FileSystem::removeDirectory(CUSTOMER_FILES . "widgets/$customWidgetDirectory", true);
        Registry::initialize(true);
    }

    function testGetDeprecationBaseline() {
        //Now that the deprecation baseline is no longer used, it will remain locked at 13.5
        $this->assertEqual('13.5', Registry::getDeprecationBaseline());
        $this->assertEqual('10.5', Registry::getDeprecationBaseline('10.5'));
        $this->assertEqual('10.5', Registry::getDeprecationBaseline('May 10'));
    }

    function testGetWidgetsByType() {
        // Tested via testGetStandardWidgets and testGetCustomWidgets.
    }

    function testGetWidgetTypes() {
        $expected = array('custom' => APPPATH . 'widgets/',
                          'standard' => CORE_WIDGET_FILES,
                         );
        $this->assertEqual($expected, Registry::getWidgetTypes());
    }

    function testGetBasePath() {
        $standardPath = CORE_WIDGET_FILES;
        $customPath = APPPATH . 'widgets/';

        $this->assertEqual($customPath, Registry::getBasePath('custom'));
        $this->assertTrue(is_dir(Registry::getBasePath('custom')));

        $this->assertEqual($standardPath, Registry::getBasePath('standard'));
        $this->assertTrue(is_dir(Registry::getBasePath('standard')));
    }

    /*
     * Test scenario where a custom widget (having the same name as a standard widget)
     * exists in the 'development' pages and not 'production' and ensure the widget
     * reference (without the leading 'standard' or 'custom') returns the standard copy.
     * This can happen in a service pack deploy where only the 'production' pages
     * should be referenced.
     */
    function testProductionModeWhenCustomWidgetInDevelopmentOnly() {
        // INPUT: 'chat/ChatCancelButton'  OUTPUT: custom/chat/ChatCancelButton
        $this->assertEqual($this->relativePathCustomDup, Registry::getWidgetPathInfo($this->shortPathDup)->relativePath);
        // INPUT: 'chat/ChatCancelButtonCustom'  OUTPUT: custom/chat/ChatCancelButtonCustom
        $this->assertEqual($this->relativePathCustomDup . 'Custom', Registry::getWidgetPathInfo($this->shortPathDup . 'Custom')->relativePath);

        Registry::setTargetPages('production');
        // INPUT: 'chat/ChatCancelButton'  OUTPUT: 'standard/chat/ChatCancelButton'
        $this->assertEqual($this->relativePathDup, Registry::getWidgetPathInfo($this->shortPathDup)->relativePath);
        // INPUT: 'chat/ChatCancelButtonCustom'  OUTPUT: null
        $this->assertNull(Registry::getWidgetPathInfo($this->shortPathDup . 'Custom'));
        Registry::setTargetPages('development');
    }

    /*
     * Test scenario where a custom widget (having the same name as a standard widget)
     * exists in the 'production' pages and not 'development' and ensure the widget
     * reference (without the leading 'standard' or 'custom') returns the custom copy.
     * This can happen in a service pack deploy where only the 'production' pages
     * should be referenced.
     */
    function testProductionModeWhenCustomWidgetInProductionOnly() {
        $shortPath = 'search/FilterDropdown';
        $shortPathCustom = $shortPath . 'Custom';

        // INPUT: search/FilterDropdown  OUTPUT: standard/search/FilterDropdown
        $this->assertEqual('standard/' . $shortPath, Registry::getWidgetPathInfo($shortPath)->relativePath);

        // INPUT: custom/search/FilterDropdown  OUTPUT: null
        $this->assertNull(Registry::getWidgetPathInfo('custom/' . $shortPath));

        // INPUT: search/FilterDropdown2Custom  OUTPUT: null
        $this->assertNull(Registry::getWidgetPathInfo($shortPathCustom));

        Registry::setTargetPages('production');
        // INPUT: search/FilterDropdown  OUTPUT: standard/search/FilterDropdown
        $this->assertEqual('standard/' . $shortPath, Registry::getWidgetPathInfo($shortPath)->relativePath);

        // INPUT: custom/search/FilterDropdown  OUTPUT: null
        $this->assertNull(Registry::getWidgetPathInfo('custom/' . $shortPath));

        // INPUT: search/FilterDropdown2Custom  OUTPUT: null
        $this->assertNull(Registry::getWidgetPathInfo($shortPathCustom));

        // Copy standard widget to development custom widget
        $src = CORE_WIDGET_FILES . "standard/$shortPath"; // euf/core/widgets/standard/search/FilterDropdown'
        $widgetTypes = Registry::getWidgetTypes();
        $customProdPath = $widgetTypes['custom'];
        $tgt = $customProdPath . "custom/$shortPath"; // euf/customer/widgets/custom/search/FilterDropdown'
        $targets = array(
          array($src, $tgt . '/1.0'),
          array($src, $tgt . 'Custom/1.0'),
        );

        foreach($targets as $pairs) {
            list($source, $target) = $pairs;
            FileSystem::copyDirectory($source, $target);
        }
        $widgetVersions = Widgets::getDeclaredWidgetVersions();
        $widgetVersions['custom/' . $shortPath] = '1.0';
        $widgetVersions['custom/' . $shortPathCustom] = '1.0';

        Widgets::updateDeclaredWidgetVersions($widgetVersions);

        Registry::setSourceBasePath(CUSTOMER_FILES);

        // INPUT: search/FilterDropdown  OUTPUT: custom/search/FilterDropdown
        $this->assertEqual('custom/' . $shortPath, Registry::getWidgetPathInfo($shortPath)->relativePath);

        // INPUT: custom/search/FilterDropdown  OUTPUT: custom/search/FilterDropdown
        $this->assertEqual('custom/' . $shortPath, Registry::getWidgetPathInfo('custom/' . $shortPath)->relativePath);

        // INPUT: search/FilterDropdown2Custom  OUTPUT: custom/search/FilterDropdown2Custom
        $this->assertEqual('custom/' . $shortPathCustom, Registry::getWidgetPathInfo($shortPathCustom)->relativePath);

        // INPUT: custom/search/FilterDropdown2Custom  OUTPUT: custom/search/FilterDropdown2Custom
        $this->assertEqual('custom/' . $shortPathCustom, Registry::getWidgetPathInfo('custom/' . $shortPathCustom)->relativePath);

        foreach($targets as $pairs) {
            list($source, $target) = $pairs;
            $target = dirname($target);
            if (is_dir($target)) {
                FileSystem::removeDirectory($target, true);
            }
        }
        $widgetVersions = Widgets::getDeclaredWidgetVersions();
        unset($widgetVersions['custom/' . $shortPath]);
        unset($widgetVersions['custom/' . $shortPathCustom]);
        Widgets::updateDeclaredWidgetVersions($widgetVersions);

        Registry::setTargetPages('development');
    }

    function testGetVersionInPath() {
        $getVersionInPath = \RightNow\UnitTest\Helper::getStaticMethodInvoker('\RightNow\Internal\Libraries\Widget\Registry', 'getVersionInPath');
        $this->assertSame('1.9.3', $getVersionInPath('standard/foo/bar/1.9.3'));
        $this->assertSame('1.9.3', $getVersionInPath('standard/foo/bar/1.9.3/'));
        $this->assertSame('1.9', $getVersionInPath('standard/foo/bar/1.9'));
        $this->assertSame('1.9', $getVersionInPath('standard/foo/bar/1.9/'));
        $this->assertSame('0.0.0', $getVersionInPath('standard/foo/bar/0.0.0'));
        $this->assertSame('100.100.100', $getVersionInPath('standard/foo/bar/100.100.100'));
        $this->assertSame('122.3', $getVersionInPath('standard/foo/bar/122.3'));
        $this->assertSame('122.223', $getVersionInPath('standard/foo/bar/122.223'));
        $this->assertSame('122.23', $getVersionInPath('standard/foo/bar/122.23'));
        $this->assertFalse($getVersionInPath('standard/foo/bar'));
        $this->assertFalse($getVersionInPath(''));
        $this->assertFalse($getVersionInPath('bar'));
        $this->assertFalse($getVersionInPath('9.2.3/foo'));
        $this->assertFalse($getVersionInPath('standard/foo/bar/122334'));
        $this->assertFalse($getVersionInPath('standard/foo/bar/122.'));
        $this->assertFalse($getVersionInPath('standard/foo/bar/122'));
    }

    function testGetWidgetPathInfoWithOptionalVersion() {
        Registry::setTargetPages('development');
        $pathInfo = 'RightNow\Internal\Libraries\Widget\PathInfo';

        $result = Registry::getWidgetPathInfo('standard/utils/Blank');
        $this->assertIsA($result, $pathInfo);
        $this->assertSame('', $result->version);

        $result = Registry::getWidgetPathInfo('standard/utils/Blank', '100.0');
        $this->assertIsA($result, $pathInfo);
        $this->assertSame('100.0.0', $result->version);
        $this->assertTrue(Text::endsWith($result->absolutePath, 'Blank/100.0.0'));
        $this->assertTrue(Text::endsWith($result->controller, 'Blank/100.0.0/controller.php'));
        $this->assertTrue(Text::endsWith($result->logic, 'Blank/100.0.0/logic.js'));
    }

    function testGetWidgetPathInfoWithNumericSuffix() {
        $pathInfo = 'RightNow\Internal\Libraries\Widget\PathInfo';

        //Accept these standard widget versions
        $originalPath = 'standard/search/ProductCategorySearchFilter';
        $result = Registry::getWidgetPathInfo('standard/search/ProductCategorySearchFilter');
        $this->assertIsA($result, $pathInfo);
        $this->assertSame($result->relativePath, $originalPath);

        $result = Registry::getWidgetPathInfo('standard/search/ProductCategorySearchFilter2');
        $this->assertIsA($result, $pathInfo);
        $this->assertSame($result->relativePath, $originalPath);

        $result = Registry::getWidgetPathInfo('Standard/search/ProductCategorySearchFilter2');
        $this->assertIsA($result, $pathInfo);
        $this->assertSame($result->relativePath, $originalPath);

        $result = Registry::getWidgetPathInfo('search/ProductCategorySearchFilter2');
        $this->assertIsA($result, $pathInfo);
        $this->assertSame($result->relativePath, $originalPath);

        //Add a custom widget and make sure it is still accessed properly
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $originalPath = 'custom/search/ProductCategorySearchFilter2';
        $widgetVersions[$originalPath] = '1.0';
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Registry::initialize(true);

        $result = Registry::getWidgetPathInfo($originalPath);
        $this->assertIsA($result, $pathInfo);
        $this->assertSame($result->relativePath, $originalPath);

        $result = Registry::getWidgetPathInfo('search/ProductCategorySearchFilter2');
        $this->assertIsA($result, $pathInfo);
        $this->assertSame($result->relativePath, $originalPath);

        unset($widgetVersions[$originalPath]);
        Widgets::updateDeclaredWidgetVersions($widgetVersions);

        //Reject these forms with more than a single number on the end
        $result = Registry::getWidgetPathInfo('standard/search/ProductCategorySearchFilter272');
        $this->assertNull($result);
        $result = Registry::getWidgetPathInfo('standard/search/ProductCategorySearchFilter27');
        $this->assertNull($result);
        $result = Registry::getWidgetPathInfo('standard/search/ProductCategorySearchFilter2a');
        $this->assertNull($result);
        $result = Registry::getWidgetPathInfo('standard/search/ProductCategorySearchFilter27a');
        $this->assertNull($result);
    }

    function testGetWidgetPaths() {
        $method = $this->getMethod('getWidgetPaths', true);
        $widgetPaths = array_keys($method('standard'));

        // look for a widget that is no longer supported/deprecated
        $result = array_filter($widgetPaths, $this->getCallback('SearchTruncation'));
        $this->assertTrue(count($result) === 0);

        // look for a widget that is still supported/deprecated
        $result = array_filter($widgetPaths, $this->getCallback('Multiline'));
        $this->assertTrue(count($result) > 0);
    }

    function testContainsPrefix() {
        $method = $this->getMethod('containsPrefix');

        $this->assertTrue($method('standard/input/FormInput'));
        $this->assertTrue($method('custom/input/FormInput'));
        $this->assertTrue($method('Standard/input/FormInput'));
        $this->assertTrue($method('Custom/input/FormInput'));

        $this->assertFalse($method('stnadard/input/FormInput'));
        $this->assertFalse($method('csutom/input/FormInput'));
        $this->assertFalse($method(''));
    }

    private function getCallback($widgetName) {
        return function($item) use ($widgetName) {
            if (\RightNow\Utils\Text::endsWith($item, $widgetName))
                return $item;
        };
    }
}
