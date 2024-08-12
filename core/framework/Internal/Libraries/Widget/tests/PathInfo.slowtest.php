<?php

use RightNow\Internal\Utils\Version,
    RightNow\Utils\Widgets,
    RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\Registry;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class WidgetPathInfoTest extends CPTestCase {
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

        $this->customBase = APPPATH . 'widgets/custom/';
        $this->shortPathCustom = 'sample/SampleWidget';
        $this->widgetKeyCustom = $this->relativePathCustom = 'custom/' . $this->shortPathCustom;
        $this->absolutePathCustom = CUSTOMER_FILES . "widgets/$this->widgetKeyCustom/1.0";

        $this->shortPathCustomDup = $this->shortPathDup;
        $this->widgetKeyCustomDup = $this->relativePathCustomDup = 'custom/' . $this->shortPathCustomDup;
        $this->absolutePathCustomDup = CUSTOMER_FILES . "widgets/$this->widgetKeyCustomDup/1.0";

        $this->shortPathCustomRoot = "SampleWidget";
        $this->widgetKeyCustomRoot = $this->relativePathCustomRoot = 'custom/' . $this->shortPathCustomRoot;
        $this->absolutePathCustomRoot = CUSTOMER_FILES . "widgets/$this->widgetKeyCustomRoot/1.0";

        $widgetNameCustom = 'Custom' . $this->widgetName;
        $this->customWidgetPaths = array(
          array($this->absolutePathDup, $this->customBase . $this->widgetNameDup),
          array($this->absolutePathDup, $this->customBase . $this->widgetCategoryDup . '/' . $this->widgetNameDup),
          array($this->absolutePathDup, $this->customBase . $this->widgetCategoryDup . '/foo/' . $this->widgetNameDup),
        );
    }

    function setUp() {
        $widgetVersions = Widgets::getDeclaredWidgetVersions();
        foreach ($this->customWidgetPaths as $paths) {
            list($source, $_target) = $paths;
            foreach (array($_target, $_target . 'Custom') as $target) {
                $widgetVersions[Text::getSubstringAfter($target, APPPATH . 'widgets/')] = '1.0';
                FileSystem::copyDirectory($source, $target . '/1.0', false, true);
            }
        }

        $source = CUSTOMER_FILES . "widgets/custom/sample/SampleWidget/1.0";
        $target = CUSTOMER_FILES . "widgets/custom/SampleWidget/1.0";
        $widgetVersions['custom/SampleWidget'] = '1.0';
        FileSystem::copyDirectory($source, $target, false, true);

        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Registry::setSourceBasePath(CUSTOMER_FILES);
        Registry::initialize(true);
        parent::setUp();
    }

    function tearDown() {
        $widgetVersions = Widgets::getDeclaredWidgetVersions();
        foreach ($this->customWidgetPaths as $paths) {
            unset($widgetVersions[Text::getSubstringAfter($paths[1], APPPATH . 'widgets/', 'blah')]);
            unset($widgetVersions[Text::getSubstringAfter($paths[1] . 'Custom', APPPATH . 'widgets/', 'blah')]);
            foreach (array(dirname($paths[1]), $paths[1], $paths[1] . 'Custom') as $target) {
                if (is_dir($target) && $target !== $this->customBase && (($target . '/') !== $this->customBase)) {
                    FileSystem::removeDirectory($target, true);
                }
            }
        }

        unset($widgetVersions['custom/SampleWidget']);
        FileSystem::removeDirectory(CUSTOMER_FILES . "widgets/custom/SampleWidget", true);

        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Registry::setSourceBasePath(CUSTOMER_FILES);
        Registry::initialize(true);
        parent::tearDown();
    }

    function testAbsolutePath() {
        $this->assertEqual($this->absolutePath, Registry::getWidgetPathInfo($this->shortPath)->absolutePath);
        $this->assertEqual($this->absolutePath, Registry::getWidgetPathInfo($this->widgetKey)->absolutePath);

        $this->assertEqual($this->absolutePathCustomDup, Registry::getWidgetPathInfo($this->shortPathDup)->absolutePath);
        $this->assertEqual($this->absolutePathDup, Registry::getWidgetPathInfo($this->widgetKeyDup)->absolutePath);

        $this->assertEqual($this->absolutePathCustom, Registry::getWidgetPathInfo($this->shortPathCustom)->absolutePath);
        $this->assertEqual($this->absolutePathCustom, Registry::getWidgetPathInfo($this->widgetKeyCustom)->absolutePath);
    }

    function testAbsolutePathCaseInsensitive() {
        $this->assertEqual($this->absolutePath, Registry::getWidgetPathInfo(strtoupper($this->shortPath))->absolutePath);
        $this->assertEqual($this->absolutePath, Registry::getWidgetPathInfo(strtoupper($this->widgetKey))->absolutePath);

        $this->assertEqual($this->absolutePathCustomDup, Registry::getWidgetPathInfo(strtoupper($this->shortPathDup))->absolutePath);
        $this->assertEqual($this->absolutePathDup, Registry::getWidgetPathInfo(strtoupper($this->widgetKeyDup))->absolutePath);

        $this->assertEqual($this->absolutePathCustom, Registry::getWidgetPathInfo(strtoupper($this->shortPathCustom))->absolutePath);
        $this->assertEqual($this->absolutePathCustom, Registry::getWidgetPathInfo(strtoupper($this->widgetKeyCustom))->absolutePath);
    }

    function testRelativePath() {
        $this->assertEqual($this->relativePath, Registry::getWidgetPathInfo($this->shortPath)->relativePath);
        $this->assertEqual($this->relativePath, Registry::getWidgetPathInfo($this->relativePath)->relativePath);

        $this->assertEqual($this->relativePathCustomDup, Registry::getWidgetPathInfo($this->shortPathDup)->relativePath);
        $this->assertEqual($this->relativePathDup, Registry::getWidgetPathInfo($this->relativePathDup)->relativePath);

        $this->assertEqual($this->relativePathCustom, Registry::getWidgetPathInfo($this->shortPathCustom)->relativePath);
        $this->assertEqual($this->relativePathCustom, Registry::getWidgetPathInfo($this->relativePathCustom)->relativePath);

        $this->assertEqual($this->relativePathCustomDup, Registry::getWidgetPathInfo($this->shortPathCustomDup)->relativePath);
        $this->assertEqual($this->relativePathCustomDup, Registry::getWidgetPathInfo($this->relativePathCustomDup)->relativePath);
    }

    function testWidgetType() {
        $this->assertEqual('standard', Registry::getWidgetPathInfo($this->shortPath)->type);
        $this->assertEqual('standard', Registry::getWidgetPathInfo($this->relativePath)->type);

        $this->assertEqual('custom', Registry::getWidgetPathInfo($this->shortPathDup)->type);
        $this->assertEqual('standard', Registry::getWidgetPathInfo($this->relativePathDup)->type);

        $this->assertEqual('custom', Registry::getWidgetPathInfo($this->shortPathCustom)->type);
        $this->assertEqual('custom', Registry::getWidgetPathInfo($this->relativePathCustom)->type);

        $this->assertEqual('custom', Registry::getWidgetPathInfo($this->shortPathCustomDup)->type);
        $this->assertEqual('custom', Registry::getWidgetPathInfo($this->relativePathCustomDup)->type);
    }

    function testController() {
        $appendValue = '/controller.php';
        $property = 'controller';

        $this->assertEqual($this->absolutePath . $appendValue, Registry::getWidgetPathInfo($this->shortPath)->$property);
        $this->assertEqual($this->absolutePath . $appendValue, Registry::getWidgetPathInfo($this->widgetKey)->$property);

        $this->assertEqual($this->absolutePathCustomDup . $appendValue, Registry::getWidgetPathInfo($this->shortPathDup)->$property);
        $this->assertEqual($this->absolutePathDup . $appendValue, Registry::getWidgetPathInfo($this->widgetKeyDup)->$property);

        $this->assertEqual($this->absolutePathCustom . $appendValue, Registry::getWidgetPathInfo($this->shortPathCustom)->$property);
        $this->assertEqual($this->absolutePathCustom . $appendValue, Registry::getWidgetPathInfo($this->widgetKeyCustom)->$property);
    }

    function testLogic() {
        $appendValue = '/logic.js';
        $property = 'logic';

        $this->assertEqual($this->absolutePath . $appendValue, Registry::getWidgetPathInfo($this->shortPath)->$property);
        $this->assertEqual($this->absolutePath . $appendValue, Registry::getWidgetPathInfo($this->widgetKey)->$property);

        $this->assertEqual($this->absolutePathCustomDup . $appendValue, Registry::getWidgetPathInfo($this->shortPathDup)->$property);
        $this->assertEqual($this->absolutePathDup . $appendValue, Registry::getWidgetPathInfo($this->widgetKeyDup)->$property);

        $this->assertEqual($this->absolutePathCustom . $appendValue, Registry::getWidgetPathInfo($this->shortPathCustom)->$property);
        $this->assertEqual($this->absolutePathCustom . $appendValue, Registry::getWidgetPathInfo($this->widgetKeyCustom)->$property);
    }

    function testView() {
        $appendValue = '/view.php';
        $property = 'view';

        $this->assertEqual($this->absolutePath . $appendValue, Registry::getWidgetPathInfo($this->shortPath)->$property);
        $this->assertEqual($this->absolutePath . $appendValue, Registry::getWidgetPathInfo($this->widgetKey)->$property);

        $this->assertEqual($this->absolutePathCustomDup . $appendValue, Registry::getWidgetPathInfo($this->shortPathDup)->$property);
        $this->assertEqual($this->absolutePathDup . $appendValue, Registry::getWidgetPathInfo($this->widgetKeyDup)->$property);

        $this->assertEqual($this->absolutePathCustom . $appendValue, Registry::getWidgetPathInfo($this->shortPathCustom)->$property);
        $this->assertEqual($this->absolutePathCustom . $appendValue, Registry::getWidgetPathInfo($this->widgetKeyCustom)->$property);
    }

    function testClassName() {
        $property = 'className';

        $this->assertEqual('ChatAgentStatus', Registry::getWidgetPathInfo($this->shortPath)->$property);
        $this->assertEqual('ChatAgentStatus', Registry::getWidgetPathInfo($this->widgetKey)->$property);

        $this->assertEqual('ChatCancelButton', Registry::getWidgetPathInfo($this->shortPathDup)->$property);
        $this->assertEqual('ChatCancelButton', Registry::getWidgetPathInfo($this->widgetKeyDup)->$property);

        $this->assertEqual('SampleWidget', Registry::getWidgetPathInfo($this->shortPathCustom)->$property);
        $this->assertEqual('SampleWidget', Registry::getWidgetPathInfo($this->widgetKeyCustom)->$property);
    }

    function testNamespacedClassName() {
        $property = 'namespacedClassName';

        $this->assertEqual('\RightNow\Widgets\ChatAgentStatus', Registry::getWidgetPathInfo($this->shortPath)->$property);
        $this->assertEqual('\RightNow\Widgets\ChatAgentStatus', Registry::getWidgetPathInfo($this->widgetKey)->$property);

        $this->assertEqual('\Custom\Widgets\chat\ChatCancelButton', Registry::getWidgetPathInfo($this->shortPathDup)->$property);
        $this->assertEqual('\RightNow\Widgets\ChatCancelButton', Registry::getWidgetPathInfo($this->widgetKeyDup)->$property);

        $this->assertEqual('\Custom\Widgets\sample\SampleWidget', Registry::getWidgetPathInfo($this->shortPathCustom)->$property);
        $this->assertEqual('\Custom\Widgets\sample\SampleWidget', Registry::getWidgetPathInfo($this->widgetKeyCustom)->$property);
    }

    function testNamespace() {
        $property = 'namespace';

        $this->assertEqual('RightNow\Widgets', Registry::getWidgetPathInfo($this->shortPath)->$property);
        $this->assertEqual('RightNow\Widgets', Registry::getWidgetPathInfo($this->widgetKey)->$property);

        $this->assertEqual('Custom\Widgets\chat', Registry::getWidgetPathInfo($this->shortPathDup)->$property);
        $this->assertEqual('RightNow\Widgets', Registry::getWidgetPathInfo($this->widgetKeyDup)->$property);

        $this->assertEqual('Custom\Widgets\sample', Registry::getWidgetPathInfo($this->shortPathCustom)->$property);
        $this->assertEqual('Custom\Widgets\sample', Registry::getWidgetPathInfo($this->widgetKeyCustom)->$property);
    }

    function testJSClassName() {
        $property = 'jsClassName';

        $this->assertEqual('RightNow.Widgets.ChatAgentStatus', Registry::getWidgetPathInfo($this->shortPath)->$property);
        $this->assertEqual('RightNow.Widgets.ChatAgentStatus', Registry::getWidgetPathInfo($this->widgetKey)->$property);

        $this->assertEqual('Custom.Widgets.chat.ChatCancelButton', Registry::getWidgetPathInfo($this->shortPathDup)->$property);
        $this->assertEqual('RightNow.Widgets.ChatCancelButton', Registry::getWidgetPathInfo($this->widgetKeyDup)->$property);

        $this->assertEqual('Custom.Widgets.sample.SampleWidget', Registry::getWidgetPathInfo($this->shortPathCustom)->$property);
        $this->assertEqual('Custom.Widgets.sample.SampleWidget', Registry::getWidgetPathInfo($this->widgetKeyCustom)->$property);
    }

    function testVersionInStandardWidgetPath() {
        umask(0);
        $widgetPath = 'standard/search/SomeTestWidget';
        $widgetAbsolutePath = CORE_WIDGET_FILES . $widgetPath;
        $cleanUp = function() use ($widgetAbsolutePath) {
            if (FileSystem::isReadableDirectory($widgetAbsolutePath)) {
                FileSystem::removeDirectory($widgetAbsolutePath, true);
            }
        };
        $cleanUp();
        $version = '1.0.1';
        $pathWithVersion = "$widgetAbsolutePath/$version";
        FileSystem::mkdirOrThrowExceptionOnFailure($pathWithVersion, true);
        $files = array('view.php', 'logic.js', 'controller.php');
        foreach ($files as $file) {
            FileSystem::filePutContentsOrThrowExceptionOnFailure("$pathWithVersion/$file", '');
        }
        FileSystem::filePutContentsOrThrowExceptionOnFailure("$pathWithVersion/info.yml",
            yaml_emit(array('version' => '1.0.1', 'requires' => array('framework' => '["3.0"]', 'jsModule' => array()))));

        $realVersions = $allWidgets = Widgets::getDeclaredWidgetVersions();
        $realVersionHistory = $allVersionHistory = Version::getVersionHistory(false, false);
        $fakeVersion = '1.0';
        $allWidgets[$widgetPath] = $fakeVersion;
        Widgets::updateDeclaredWidgetVersions($allWidgets);
        Widgets::killCacheVariables();
        $allVersionHistory['widgetVersions'][$widgetPath] = array($version => array('requires' => array('framework' => array(strval(CP_FRAMEWORK_VERSION)))));
        Version::writeVersionHistory($allVersionHistory);
        Version::clearCacheVariables();
        Registry::setSourceBasePath(CUSTOMER_FILES);
        Registry::initialize(true);
        $widget = Registry::getWidgetPathInfo($widgetPath);
        $this->assertSame('1.0.1', $widget->version);
        $this->assertSame('1.0', $widget->shortVersion);
        $this->assertSame($widgetPath, $widget->relativePath);
        $this->assertSame($pathWithVersion, $widget->absolutePath);
        $this->assertSame($pathWithVersion . '/controller.php', $widget->controller);
        $this->assertSame($pathWithVersion . '/logic.js', $widget->logic);
        $this->assertSame($pathWithVersion . '/view.php', $widget->view);
        $this->assertSame('SomeTestWidget', $widget->className);
        $this->assertSame('\RightNow\Widgets\SomeTestWidget', $widget->namespacedClassName);
        $this->assertSame('RightNow\Widgets', $widget->namespace);
        $this->assertSame('RightNow.Widgets.SomeTestWidget', $widget->jsClassName);

        Widgets::updateDeclaredWidgetVersions($realVersions);
        Version::writeVersionHistory($realVersionHistory);

        $cleanUp();
        Registry::initialize(true);
    }

    function testVersionInCustomWidgetPath() {
        umask(0);
        $widgetPath = 'custom/search/SomeTestWidget';
        $widgetAbsolutePath = CUSTOMER_FILES . 'widgets/' . $widgetPath;
        $cleanUp = function() use ($widgetAbsolutePath) {
            if (FileSystem::isReadableDirectory($widgetAbsolutePath)) {
                FileSystem::removeDirectory($widgetAbsolutePath, true);
            }
        };
        $cleanUp();
        $version = '1.0';
        $pathWithVersion = "$widgetAbsolutePath/$version";
        FileSystem::mkdirOrThrowExceptionOnFailure($pathWithVersion, true);
        $files = array('view.php', 'logic.js', 'controller.php');
        foreach ($files as $file) {
            FileSystem::filePutContentsOrThrowExceptionOnFailure("$pathWithVersion/$file", '');
        }
        FileSystem::filePutContentsOrThrowExceptionOnFailure("$pathWithVersion/info.yml", "version: \"1.0\"\nrequires:\n  framework: [\"3.0\"]");

        $realVersions = $allWidgets = Widgets::getDeclaredWidgetVersions();
        $fakeVersion = '1.0';
        $allWidgets[$widgetPath] = $fakeVersion;
        Widgets::updateDeclaredWidgetVersions($allWidgets);
        Widgets::killCacheVariables();
        Registry::initialize(true);
        $widget = Registry::getWidgetPathInfo($widgetPath);
        $this->assertSame('1.0', $widget->version);
        $this->assertSame('1.0', $widget->shortVersion);
        $this->assertSame($widgetPath, $widget->relativePath);
        $this->assertSame($pathWithVersion, $widget->absolutePath);
        $this->assertSame($pathWithVersion . '/controller.php', $widget->controller);
        $this->assertSame($pathWithVersion . '/logic.js', $widget->logic);
        $this->assertSame($pathWithVersion . '/view.php', $widget->view);
        $this->assertSame('SomeTestWidget', $widget->className);
        $this->assertSame('\Custom\Widgets\search\SomeTestWidget', $widget->namespacedClassName);
        $this->assertSame('Custom\Widgets\search', $widget->namespace);
        $this->assertSame('Custom.Widgets.search.SomeTestWidget', $widget->jsClassName);

        Widgets::updateDeclaredWidgetVersions($realVersions);

        $cleanUp();
        Registry::initialize(true);
    }

    function testMetaPopulation(){
        $meta = Registry::getWidgetPathInfo($this->shortPath)->meta;
        $this->assertTrue(is_array($meta));
        $this->assertIdentical('1.1.1', $meta['version']);
        $this->assertIdentical($this->absolutePath, $meta['absolutePath']);
        $this->assertIdentical($this->relativePath, $meta['relativePath']);
        $this->assertTrue(is_array($meta['requires']));
        $this->assertIdentical('3.11', $meta['requires']['framework'][0]);
        $this->assertIdentical('standard', $meta['requires']['jsModule'][0]);
        $this->assertIdentical('mobile', $meta['requires']['jsModule'][1]);
        $this->assertTrue(is_array($meta['attributes']));
        $this->assertTrue(is_array($meta['info']));

        $meta = Registry::getWidgetPathInfo($this->shortPathCustom)->meta;
        $this->assertTrue(is_array($meta));
        $this->assertIdentical('1.0', $meta['version']);
        $this->assertIdentical($this->absolutePathCustom, $meta['absolutePath']);
        $this->assertIdentical($this->relativePathCustom, $meta['relativePath']);
        $this->assertTrue(is_array($meta['requires']));
        $this->assertIdentical('3.11', $meta['requires']['framework'][0]);
        $this->assertIdentical('standard', $meta['requires']['jsModule'][0]);
        $this->assertIdentical('mobile', $meta['requires']['jsModule'][1]);
        $this->assertTrue(is_array($meta['attributes']));
        $this->assertTrue(is_array($meta['info']));
        $this->assertTrue(is_array($meta['extends']));
        $this->assertIdentical('standard/input/SelectionInput', $meta['extends']['widget']);
        $this->assertTrue(is_array($meta['extends']['components']));
    }

    function testCustomRoot() {
        $pathInfo1 = Registry::getWidgetPathInfo('SampleWidget');
        $pathInfo2 = Registry::getWidgetPathInfo('custom/SampleWidget');
        foreach(array($pathInfo1, $pathInfo2) as $pathInfo) {
            $this->assertNotNull($pathInfo);
            $this->assertEqual($this->absolutePathCustomRoot, $pathInfo->absolutePath);
            $this->assertEqual($this->relativePathCustomRoot, $pathInfo->relativePath);
            $this->assertEqual('1.0', $pathInfo->version);
            $this->assertEqual('SampleWidget', $pathInfo->className);
            $this->assertEqual('\\Custom\\Widgets\\SampleWidget', $pathInfo->namespacedClassName);
            $this->assertEqual('Custom\\Widgets', $pathInfo->namespace);
            $this->assertEqual('Custom.Widgets.SampleWidget', $pathInfo->jsClassName);
        }

        $pathInfo1 = Registry::getWidgetPathInfo('sample/SampleWidget');
        $pathInfo2 = Registry::getWidgetPathInfo('custom/sample/SampleWidget');
        foreach(array($pathInfo1, $pathInfo2) as $pathInfo) {
            $this->assertNotNull($pathInfo);
            $this->assertEqual($this->absolutePathCustom, $pathInfo->absolutePath);
            $this->assertEqual($this->relativePathCustom, $pathInfo->relativePath);
            $this->assertEqual('1.0', $pathInfo->version);
            $this->assertEqual('SampleWidget', $pathInfo->className);
            $this->assertEqual('\\Custom\\Widgets\\sample\\SampleWidget', $pathInfo->namespacedClassName);
            $this->assertEqual('Custom\\Widgets\\sample', $pathInfo->namespace);
            $this->assertEqual('Custom.Widgets.sample.SampleWidget', $pathInfo->jsClassName);
        }
    }
}
