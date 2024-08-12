<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Libraries\Widget,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\Deployer,
    RightNow\Internal\Utils\Version,
    RightNow\Internal\Utils\Widgets,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Text;

class DeployerTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Deployer';

    function getMethodDeployer($methodName) {
        return $this->getMethodDeployOptions($methodName);
    }

    function getMethodDeployOptions($methodName, $deployOptionsClass = null) {
        $agentAccount = get_instance()->_getAgentAccount();
        $stageObject = new \RightNow\Internal\Libraries\Stage('staging_01', array('pushVersionChanges' => false, 'lockCreatedTime' => 0));
        if ($deployOptionsClass) {
            $fullName = "\\RightNow\\Internal\\Libraries\\$deployOptionsClass";
            switch ($deployOptionsClass) {
                case 'UpgradeProductionDeployOptions':
                case 'UnitTestDeployOptions':
                case 'TarballDeployOptions':
                case 'VersionCronDeployOptions':
                case 'ServicePackDeployOptions':
                    return parent::getMethod($methodName,
                        array(new $fullName()));
                case 'PrepareDeployOptions':
                case 'CommitDeployOptions':
                    return parent::getMethod($methodName,
                        array(new $fullName($agentAccount)));
                case 'StagingDeployOptions':
                case 'UpgradeStagingDeployOptions':
                case 'ServicePackStagingDeployOptions':
                    return parent::getMethod($methodName,
                        array(new $fullName(
                            $stageObject)));
                case 'TarballStagingDeployOptions':
                    return parent::getMethod($methodName,
                        array(new $fullName(
                            $stageObject,
                            'comment', true)));
            }
        }
        return parent::getMethod($methodName,
            array(new \RightNow\Internal\Libraries\BasicDeployOptions($agentAccount)));
    }

    function testRemoveExtraSlashes() {
        $paths = array(
            '//foo/bar/file.css' => '/foo/bar/file.css',
            '/foo/bar/file.css' => '/foo/bar/file.css',
            'foo///bar/file.css' => 'foo/bar/file.css',
            'foo///bar/file.css/' => 'foo/bar/file.css/',
        );
        foreach($paths as $input => $expected) {
            $this->assertEqual($expected, \RightNow\Internal\Libraries\Deployer::removeExtraSlashes($input));
        }
    }

    function testGetPathRelativeToFilePath() {
        $paths = array(
            'foo/bar/file.css' => '../../themes/',
            'foo/null/0//file.css' => '../../../themes/',
        );
        foreach($paths as $input => $expected) {
            $this->assertEqual($expected, \RightNow\Internal\Libraries\Deployer::getPathRelativeToFilePath($input));
        }
    }

    function testGetPhpFiles() {
        $invoke = $this->getMethodDeployer('getPhpFiles');

        // Ensure only *.php *files* are returned, and not ._*.php
        $files = array(
            'apple.php' => true,
            'banana.php' => true,
            '.banana.php' => true,
            'lemon.php' => true,
            '._lemon.php' => false,
            'lime.php' => true,
            'lime.txt' => false,
        );
        foreach(array_keys($files) as $file) {
            $this->writeTempFile($file, 'junk');
        }
        FileSystem::mkdirOrThrowExceptionOnFailure("{$this->testDir}/imadirectory.php");

        $results = $invoke($this->testDir);
        $this->assertFalse(in_array('imadirectory.php', $results));
        foreach($files as $file => $expected) {
            if ($expected) {
                $this->assertTrue(in_array($file, $results));
            }
            else {
                $this->assertFalse(in_array($file, $results));
            }
        }

        foreach($results as $file) {
            $this->assertTrue(array_key_exists($file, $files) && $files[$file]);
        }

        // Test with second argument null
        $first = 'home.php';
        $path = APPPATH . 'views/pages/';
        $files = $invoke($path);
        $offset = array_search($first, $files);
        $this->assertNotEqual($offset, false);
        $this->assertNotEqual($files[0], $first);

        // Test with second argument specified
        $files = $invoke($path, $first);
        $this->assertIdentical($files[0], $first);
        $this->assertNotEqual($offset, array_search($first, $files));
        $elements = array_filter(array_values($files), function($x) use ($first) {return ($x === $first);});
        $this->assertIdentical(count($elements), 1);

        $this->eraseTempDir();
    }

    function testShortenPathName() {
        $invoke = $this->getMethodDeployer('shortenPathName');
        $this->assertIdentical('foo/bar', $invoke('foo/bar'));
        $this->assertIdentical('foo/bar', $invoke(CORE_FILES . 'foo/bar'));
        $this->assertIdentical('foo/bar', $invoke(CUSTOMER_FILES . 'foo/bar'));
        $this->assertIdentical('foo/bar', $invoke(HTMLROOT . 'foo/bar'));
        $this->assertIdentical(OPTIMIZED_FILES . 'foo/bar', $invoke(OPTIMIZED_FILES . 'foo/bar'));
    }

    function testCreateShellSafeFilename() {
        $invoke = $this->getMethodDeployer('createShellSafeFilename');
        $nameIn = 'A really poorly named file.foo';
        $nameOut = $invoke($nameIn);
        $this->assertFalse(\RightNow\Utils\Text::stringContains($nameOut, ' '));
        $this->assertTrue(\RightNow\Utils\Text::endsWith($nameOut, 'Areallypoorlynamedfile.foo'));
    }

    function testGetJavascriptNameFromMeta() {
        $invoke = $this->getMethodDeployer('getJavascriptNameFromMeta');
        $this->assertIdentical('Custom.Widgets.foo.bar', $invoke(array('js_path' => 'custom/foo/bar')));
        $this->assertIdentical('RightNow.Widgets.SearchButton', $invoke(array('js_path' => 'standard/search/SearchButton')));
        $this->assertIdentical('', $invoke(array('css_path' => '/a/js/path')));
    }

    function testGetThemePathRelativeToAssets() {
        $invoke = $this->getMethodDeployer('getThemePathRelativeToAssets');
        $this->assertIdentical('foo', $invoke('/euf/assets/foo'));
        $this->assertIdentical('foo', $invoke('/euf/core/foo'));
        try {
            $shouldNotGetSet = $this->assertIdentical('foo', $invoke('/euf/foo'));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testGetPageUrlFromConfig() {
        $inputs = array(
            array('/home.php', true, true, '/home.php'),
            array('home', false, true, '/home.php'),
            array('answers/detail/a_id/3698', false, true, '/answers/detail.php'),
            array('answers/detail/a_id/3698', false, false, '/answers/detail/a_id/3698.php'),
            array('answers/detail/kw/foo', false, true, '/answers/detail.php'),
            array('answers/detail/p/123', false, true, '/answers/detail.php'),
            array('answers/detail/c/456', false, true, '/answers/detail.php'),
            array('answers/detail/foo/you', false, true, '/answers/detail/foo/you.php'),
        );

        foreach ($inputs as $items) {
            list($page, $obtainValueFromConfig, $removeCommonUrlParameters, $expected) = $items;
            $this->assertEqual($expected, \RightNow\Internal\Libraries\Deployer::getPageUrlFromConfig($page, $obtainValueFromConfig, $removeCommonUrlParameters));
        }
    }

    function testGetMinifiedJavaScript(){
        $getMinifiedJavaScript = $this->getMethodDeployer('getMinifiedJavaScript');

        $sampleJS = HTMLROOT . '/euf/core/debug-js/RightNow.js';
        $result = $getMinifiedJavaScript($sampleJS);
        $this->assertFalse(Text::stringContains($result, '/**'), "Resulting Javascript contained comments when it should have been minified: $result");

        $widgetPathInfo = Registry::getWidgetPathInfo('custom/sample/SampleWidget');
        $result = $getMinifiedJavaScript($sampleJS);
        $this->assertFalse(Text::stringContains($result, '/**'), "Resulting Javascript contained comments when it should have been minified: $result");

        $widgetPathInfo = Registry::getWidgetPathInfo('standard/search/AdvancedSearchDialog');
        $result = $getMinifiedJavaScript($sampleJS);
        $this->assertFalse(Text::stringContains($result, '/**'), "Resulting Javascript contained comments when it should have been minified: $result");
    }

    function testAddPHPRequire() {
        $invoke = $this->getMethodDeployer('addPHPRequire');

        $result = $invoke("Controller code...", "insert me at the top");
        $this->assertTrue(Text::beginsWith($result, "insert me at the top"));

        $result = $invoke("namespace foo;\nclass Foo {}", "require_once('foo.php');");
        $this->assertTrue(Text::beginsWith($result, "namespace foo;"));
        $pos = strpos($result, "require_once('foo.php');");
        $this->assertTrue($pos > 0);
        $this->assertTrue($pos < strpos($result, "class Foo {}"));

        $result = $invoke("namespace foo{\nclass Foo {}\n}", "require_once('foo.php');");
        $this->assertTrue(Text::beginsWith($result, "namespace foo{"));
        $pos = strpos($result, "require_once('foo.php');");
        $this->assertTrue($pos > 0);
        $this->assertTrue($pos < strpos($result, "class Foo {}"));

        $result = $invoke("namespace foo\Bar\Baz\Banana ;\nclass Foo {}", "require_once('foo.php');");
        $this->assertTrue(Text::beginsWith($result, "namespace foo\Bar\Baz\Banana ;"));
        $pos = strpos($result, "require_once('foo.php');");
        $this->assertTrue($pos > 0);
        $this->assertTrue($pos < strpos($result, "class Foo {}"));

        $result = $invoke("namespace foo\Bar\Baz\Banana {\nclass Foo {}\n}", "require_once('foo.php');");
        $this->assertTrue(Text::beginsWith($result, "namespace foo\Bar\Baz\Banana {"));
        $pos = strpos($result, "require_once('foo.php');");
        $this->assertTrue($pos > 0);
        $this->assertTrue($pos < strpos($result, "class Foo {}"));
    }

    function testReplaceRelativeWidgetRenderCallsWithResolved() {
        $replaceWithResolved = $this->getMethodDeployer('replaceRelativeWidgetRenderCallsWithResolved');
        $code = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('search/KeywordText', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',)); ?>";
        $result = $replaceWithResolved($code);
        $this->assertTrue(\RightNow\Utils\Text::stringContains($result, "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText'"));
        $code = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('sample/SampleWidget', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',)); ?>";
        $result = $replaceWithResolved($code);
        $this->assertTrue(\RightNow\Utils\Text::stringContains($result, "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('custom/sample/SampleWidget'"));
        $code = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',)); ?>";
        $result = $replaceWithResolved($code);
        $this->assertTrue(\RightNow\Utils\Text::stringContains($result, "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText'"));
    }

    function testAddNewLibraryNameToResolvedWidgetRenderCalls() {
        $addNewLibraryNameToResolvedWidgetRenderCalls = $this->getMethodDeployer('addNewLibraryNameToResolvedWidgetRenderCalls');
        $code = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',)); ?>";
        $expected = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',),'customViewLibrary'); ?>";
        $result = $addNewLibraryNameToResolvedWidgetRenderCalls($code, 'standard/search/KeywordText', 'customViewLibrary');
        $this->assertSame($expected, $result);

        $code = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',)); ?>" .
            "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText', array()); ?>";
        $expected = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',),'customViewLibrary'); ?>" .
            "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText', array(),'customViewLibrary'); ?>";
        $result = $addNewLibraryNameToResolvedWidgetRenderCalls($code, 'standard/search/KeywordText', 'customViewLibrary');
        $this->assertSame($expected, $result);

        $code = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText2', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',)); ?>" .
            "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText', array()); ?>";
        $expected = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText2', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',)); ?>" .
            "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText', array(),'customViewLibrary'); ?>";
        $result = $addNewLibraryNameToResolvedWidgetRenderCalls($code, 'standard/search/KeywordText', 'customViewLibrary');
        $this->assertSame($expected, $result);

        $code = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText2', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',)); ?>" .
            "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText2', array()); ?>";
        $expected = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText2', array('label_text' => '' . \RightNow\Utils\Config::msgGetFrom('', SEARCH_TERMS_UC_CMD) . '',)); ?>" .
            "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/search/KeywordText2', array()); ?>";
        $result = $addNewLibraryNameToResolvedWidgetRenderCalls($code, 'standard/search/KeywordText', 'customViewLibrary');
        $this->assertSame($expected, $result);
    }

    function testCheckWidgetControllerConstructor() {
        $testedMethod = $this->getMethodDeployer('checkWidgetControllerConstructor');

        // Without a constructor.
        $this->assertTrue($testedMethod('', ''));

        // Constructor without argument
        $this->assertFalse($testedMethod('class X {\nfunction __construct();\n}', 'X'));

        // Constructor with 1 argument, but no call to parent
        $this->assertFalse($testedMethod('class X {\nfunction __construct( $a );\n}', 'X'));

        // Constructor with 2 arguments, but no call to parent
        $this->assertFalse($testedMethod('class X {\nfunction __construct( $a, $b);\n}', 'X'));

        // Constructor with 1 argument, call to parent, but fails to pass arg
        $this->assertFalse($testedMethod('class X {\nfunction __construct( $a ){ \n parent::__construct();\n} \n }\n', 'X'));

        // Constructor with 2 arguments, call to parent, but fails to pass arg
        $this->assertFalse($testedMethod('class X {\nfunction __construct( $a, $b ){ \n parent::__construct();\n} \n }\n', 'X'));

        // Constructor with 1 argument, call to parent, passes arg
        $this->assertTrue($testedMethod('class X {\nfunction __construct( $a ){ \n parent::__construct($a);\n} \n }\n', 'X'));

        // Constructor with 2 arguments, call to parent, passes arg
        $this->assertTrue($testedMethod('class X {\nfunction __construct( $a, $b ){ \n parent::__construct(   $a  );\n} \n }\n', 'X'));

        // Allow valid PHP4 style constructors
        $this->assertTrue($testedMethod('class X {\nfunction X( $a ){ \n parent::__construct($a);\n} \n }\n', 'X'));
        $this->assertTrue($testedMethod('class X {\nfunction X( $a ){}', 'Y'));
        $this->assertFalse($testedMethod('class X {\nfunction X( $a ){ \n parent::__construct();\n} \n }\n', 'X'));
        $this->assertFalse($testedMethod('class X {\nfunction X( $a, $b ){ \n parent::__construct();\n} \n }\n', 'X'));
    }

    function testUninstallDeletedCustomWidgets() {
        Widgets::killCacheVariables();
        $method = $this->getMethodDeployer('uninstallDeletedCustomWidgets');
        ob_start();
        $return = $method();
        $output = ob_get_clean();
        $this->assertTrue($return);
        $template = "Because widget %s does not exist in the staging environment, it is not activated there.";
        $this->assertTrue(Text::stringContains($output, sprintf($template, 'custom/sample/SampleWidget (1.0)')));

        ob_start();
        $return = $method(array('custom/foo/bar'));
        $output = ob_get_clean();
        $this->assertTrue($return);
        $this->assertTrue(Text::stringContains($output, sprintf($template, 'custom/foo/bar')));

        $methodStaging = $this->getMethodDeployOptions('uninstallDeletedCustomWidgets', 'StagingDeployOptions');
        ob_start();
        $return = $methodStaging();
        $output = ob_get_clean();
        $this->assertTrue($return);
        $this->assertTrue(Text::stringContains($output, sprintf($template, 'custom/sample/SampleWidget (1.0)')));

        ob_start();
        $return = $methodStaging(array('custom/foo/bar'));
        $output = ob_get_clean();
        $this->assertTrue($return);
        $this->assertTrue(Text::stringContains($output, sprintf($template, 'custom/foo/bar')));

        $developmentWidgetVersions = CUSTOMER_FILES . 'widgetVersions';
        $stagingWidgetVersions = OPTIMIZED_FILES . 'staging/staging_01/temp_source/widgetVersions';

        $originalDevelopment = $development = Version::getVersionFile($developmentWidgetVersions);
        $originalStaging = $staging = Version::getVersionFile($stagingWidgetVersions);

        $staging['custom/foo/bar'] = '1.0';
        Version::writeVersionFile($stagingWidgetVersions, $staging, 'php');

        ob_start();
        $return = $methodStaging(array('custom/foo/bar'));
        $output = ob_get_clean();
        $this->assertTrue($return);
        $this->assertTrue(Text::stringContains($output, sprintf($template, 'custom/foo/bar')));

        $development['custom/foo/bar'] = '1.0';
        Version::writeVersionFile($developmentWidgetVersions, $development, 'yaml');
        Version::writeVersionFile($stagingWidgetVersions, $staging, 'php');

        ob_start();
        $return = $methodStaging(array('custom/foo/bar'));
        $output = ob_get_clean();
        $this->assertTrue($return);
        $this->assertTrue(Text::stringContains($output, 'Because the custom/foo/bar widget does not exist in the development or staging environments, it is not activated in either area.'));

        Version::writeVersionFile($developmentWidgetVersions, $development, 'yaml');
        Version::writeVersionFile($stagingWidgetVersions, $staging, 'php');

        Version::writeVersionFile($developmentWidgetVersions, $originalDevelopment, 'yaml');
        Version::writeVersionFile($stagingWidgetVersions, $originalStaging, 'php');
    }

    function testModifyVersions() {
        $getFilePath = function($type, $mode, $isSource = true) {
            $file = $type === 'framework' ? 'frameworkVersion' : 'widgetVersions';
            if ($mode === 'development')
                return CUSTOMER_FILES . $file;
            if ($mode === 'staging')
                return DOCROOT . '/cp/generated/staging/staging_01/' . ($isSource ? 'source' : 'temp_source') . '/' . $file;
            return DOCROOT . '/cp/generated/production/' . ($isSource ? 'source' : 'temp_source') . '/' . $file;
        };
        $getExpectedFileContents = function($type, $mode, $isSource = true) {
            $widgetPath = 'custom/sample/SampleWidget';
            if ($mode === 'development')
                return $type === 'framework' ? '3.0' : 'custom/sample/SampleWidget: "1.0"';
            if ($mode === 'staging') {
                if ($type === 'framework')
                    return $isSource ? '3.1' : '3.2';
                return serialize(array($widgetPath => $isSource ? '1.1' : '1.2'));
            }
            if ($type === 'framework')
                return $isSource ? '3.3' : '3.4';
            return serialize(array($widgetPath => $isSource ? '1.3' : '1.4'));
        };
        $existingFiles = array();
        $existingFiles['developmentFrameworkVersion'] = @file_get_contents($getFilePath('framework', 'development'));
        $existingFiles['developmentWidgetVersions'] = @file_get_contents($getFilePath('widget', 'development'));
        $existingFiles['stagingSourceFrameworkVersion'] = @file_get_contents($getFilePath('framework', 'staging', true));
        $existingFiles['stagingSourcetWidgetVersions'] = @file_get_contents($getFilePath('widget', 'staging', true));
        $existingFiles['stagingTempSourceFrameworkVersion'] = @file_get_contents($getFilePath('framework', 'staging', false));
        $existingFiles['stagingTempSourcetWidgetVersions'] = @file_get_contents($getFilePath('widget', 'staging', false));
        $existingFiles['productionSourceFrameworkVersion'] = @file_get_contents($getFilePath('framework', 'production', true));
        $existingFiles['productionSourcetWidgetVersions'] = @file_get_contents($getFilePath('widget', 'production', true));
        $existingFiles['productionTempSourceFrameworkVersion'] = @file_get_contents($getFilePath('framework', 'production', false));
        $existingFiles['productionTempSourcetWidgetVersions'] = @file_get_contents($getFilePath('widget', 'production', false));

        $restoreFiles = function() use ($getFilePath, $existingFiles) {
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'development'), $existingFiles['developmentFrameworkVersion']);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'development'), $existingFiles['developmentWidgetVersions']);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'staging', true), $existingFiles['stagingSourceFrameworkVersion']);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'staging', true), $existingFiles['stagingSourcetWidgetVersions']);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'staging', false), $existingFiles['stagingTempSourceFrameworkVersion']);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'staging', false), $existingFiles['stagingTempSourcetWidgetVersions']);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'production', true), $existingFiles['productionSourceFrameworkVersion']);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'production', true), $existingFiles['productionSourcetWidgetVersions']);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'production', false), $existingFiles['productionTempSourceFrameworkVersion']);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'production', false), $existingFiles['productionTempSourcetWidgetVersions']);
        };

        $setupFiles = function() use ($getFilePath, $getExpectedFileContents) {
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'development'), $getExpectedFileContents('framework', 'development'));
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'development'), $getExpectedFileContents('widget', 'development'));
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'staging', true), $getExpectedFileContents('framework', 'staging', true));
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'staging', true), $getExpectedFileContents('widget', 'staging', true));
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'staging', false), $getExpectedFileContents('framework', 'staging', false));
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'staging', false), $getExpectedFileContents('widget', 'staging', false));
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'production', true), $getExpectedFileContents('framework', 'production', true));
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'production', true), $getExpectedFileContents('widget', 'production', true));
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('framework', 'production', false), $getExpectedFileContents('framework', 'production', false));
            FileSystem::filePutContentsOrThrowExceptionOnFailure($getFilePath('widget', 'production', false), $getExpectedFileContents('widget', 'production', false));
        };

        $that = $this;

        $testAssertions = function($deployOptionsClass, $overrides = array()) use ($that, $getFilePath, $getExpectedFileContents) {
            $assertMessage = "$deployOptionsClass: %s";
            $that->assertIdentical($overrides[$getFilePath('framework', 'development')] ?: $getExpectedFileContents('framework', 'development'), @file_get_contents($getFilePath('framework', 'development')), $assertMessage);
            $that->assertIdentical($overrides[$getFilePath('widget', 'development')] ?: $getExpectedFileContents('widget', 'development'), @file_get_contents($getFilePath('widget', 'development')), $assertMessage);
            $that->assertIdentical($overrides[$getFilePath('framework', 'staging', true)] ?: $getExpectedFileContents('framework', 'staging', true), @file_get_contents($getFilePath('framework', 'staging', true)), $assertMessage);
            $that->assertIdentical($overrides[$getFilePath('widget', 'staging', true)] ?: $getExpectedFileContents('widget', 'staging', true), @file_get_contents($getFilePath('widget', 'staging', true)), $assertMessage);
            $that->assertIdentical($overrides[$getFilePath('framework', 'staging', false)] ?: $getExpectedFileContents('framework', 'staging', false), @file_get_contents($getFilePath('framework', 'staging', false)), $assertMessage);
            $that->assertIdentical($overrides[$getFilePath('widget', 'staging', false)] ?: $getExpectedFileContents('widget', 'staging', false), @file_get_contents($getFilePath('widget', 'staging', false)), $assertMessage);
            $that->assertIdentical($overrides[$getFilePath('framework', 'production', true)] ?: $getExpectedFileContents('framework', 'production', true), @file_get_contents($getFilePath('framework', 'production', true)), $assertMessage);
            $that->assertIdentical($overrides[$getFilePath('widget', 'production', true)] ?: $getExpectedFileContents('widget', 'production', true), @file_get_contents($getFilePath('widget', 'production', true)), $assertMessage);
            $that->assertIdentical($overrides[$getFilePath('framework', 'production', false)] ?: $getExpectedFileContents('framework', 'production', false), @file_get_contents($getFilePath('framework', 'production', false)), $assertMessage);
            $that->assertIdentical($overrides[$getFilePath('widget', 'production', false)] ?: $getExpectedFileContents('widget', 'production', false), @file_get_contents($getFilePath('widget', 'production', false)), $assertMessage);
        };

        $createOverrideArray = function($argsToOverride, $valuesToExpect) use ($getFilePath, $getExpectedFileContents) {
            $serializedDevelopmentWidgets = 'a:1:{s:26:"custom/sample/SampleWidget";s:3:"1.0";}';
            return array(
                $getFilePath('framework', $argsToOverride[0], $argsToOverride[1]) => $getExpectedFileContents('framework', $valuesToExpect[0], $valuesToExpect[1]),
                $getFilePath('widget', $argsToOverride[0], $argsToOverride[1]) => $valuesToExpect[0] === development ? $serializedDevelopmentWidgets : $getExpectedFileContents('widget', $valuesToExpect[0], $valuesToExpect[1])
            );
        };

        $runTests = function($deployOptionsClass, $overrides = array()) use ($that, $setupFiles, $testAssertions) {
            $modifyVersions = $that->getMethodDeployOptions('modifyVersions', $deployOptionsClass);
            $setupFiles();
            $modifyVersions();
            $testAssertions($deployOptionsClass, $overrides);
        };

        // files are unchanged
        $runTests('StagingDeployOptions');
        $runTests('TarballStagingDeployOptions');

        // production uses development
        $runTests('PrepareDeployOptions', $createOverrideArray(array('production', false), array('development')));
        $runTests('CommitDeployOptions', $createOverrideArray(array('production', false), array('development')));
        // skip UnitTestDeployOptions because CC has already created deploy0000000000.log in a way that we cannot open it for appending
        //$runTests('UnitTestDeployOptions', $createOverrideArray(array('production', false), array('development')));
        $runTests('TarballDeployOptions', $createOverrideArray(array('production', false), array('development')));
        $runTests('VersionCronDeployOptions', $createOverrideArray(array('production', false), array('development')));

        // staging uses staging source
        $runTests('UpgradeStagingDeployOptions', $createOverrideArray(array('staging', false), array('staging', true)));
        $runTests('ServicePackStagingDeployOptions', $createOverrideArray(array('staging', false), array('staging', true)));

        // production uses production source
        $runTests('UpgradeProductionDeployOptions', $createOverrideArray(array('production', false), array('production', true)));
        $runTests('ServicePackDeployOptions', $createOverrideArray(array('production', false), array('production', true)));

        $restoreFiles();
    }

    function testVersionChangeArgs() {
        require_once CPCORE . 'Internal/Utils/VersionTracking.php';
        $removeVersionEntries = function() {
            $filter = array();
            $reportEntries = get_instance()->model('Report')->getDataHTML(
                \RightNow\Internal\Utils\VersionTracking::$reports['versions'],
                null,
                $filter,
                array())->result['data'] ?: array();

            $destroyed = array();
            foreach ($reportEntries as $entry) {
                $id = $entry[0];
                if (!$destroyed[$id]) {
                    \RightNow\Internal\Api::cp_object_destroy(array('cp_object_id' => $id));
                    $destroyed[$id] = true;
                }
            }
            \RightNow\Internal\Utils\VersionTracking::initializeCache();
        };

        $getVersionArgs = function($initialize) {
            $stage = new \RightNow\Internal\Libraries\Stage('staging_01', array('initialize' => $initialize, 'pushVersionChanges' => true));
            $options = new \RightNow\Internal\Libraries\StagingDeployOptions($stage);
            return $options->versionChangeArgs();
        };

        // before we begin, make sure we have an entry in the database
        \RightNow\Internal\Utils\VersionTracking::logToDatabase(array(
            'name'  => 'standard/input/TextInput',
            'to'    => '3.1',
            'mode'  => 'staging',
        ));

        // if we are initializing staging area, look for backups in production/backup/staging_01
        $versionArgs = $getVersionArgs(true);
        $this->assertSame(1, count($versionArgs));
        $this->assertSame(3, count($versionArgs[0]));
        $this->assertSame('staging', $versionArgs[0][2]);
        $this->assertTrue(\RightNow\Utils\Text::endsWith($versionArgs[0][0], 'cp/generated/production/backup/staging_01/optimized/'));

        // otherwise, the backup is found in staging/staging_01/backup
        $versionArgs = $getVersionArgs(false);
        $this->assertSame(1, count($versionArgs));
        $this->assertSame(3, count($versionArgs[0]));
        $this->assertSame('staging', $versionArgs[0][2]);
        $this->assertTrue(\RightNow\Utils\Text::endsWith($versionArgs[0][0], 'cp/generated/staging/staging_01/backup/'));

        // @@@ 141015-000162 versionCahngeArgs should return empty array for service packs
        $stage = new \RightNow\Internal\Libraries\Stage('staging_01', array('initialize' => $initialize, 'pushVersionChanges' => true));
        $servicePackStagingDeploy = new \RightNow\Internal\Libraries\ServicePackStagingDeployOptions($stage);
        $this->assertIdentical(array(), $servicePackStagingDeploy->versionChangeArgs());
        $servicePackDeploy = new \RightNow\Internal\Libraries\ServicePackDeployOptions();
        $this->assertIdentical(array(), $servicePackDeploy->versionChangeArgs());

        // cleanup versions we added for tests
        $removeVersionEntries();
    }

    function testAddExtendedViewToWidgetCode() {
        list(
            $class,
            $method
        ) = $this->reflect('method:addExtendedViewToWidgetCode');
        $deployer = $class->newInstanceArgs(array(new \RightNow\Internal\Libraries\BasicDeployOptions(get_instance()->_getAgentAccount())));

        // Block replacement is performed in sub-widget
        $dir = get_cfg_var('upload_tmp_dir') . '/unitTest/' . get_class($this);
        $viewFile = "$dir/1.0.1/view.php";
        umask(0);
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure($viewFile, '<rn:block id="KeywordText-preInput">banana</rn:block>');
        $pathInfo = new Widget\PathInfo('standard', 'standard/foo/Bar', $dir, '1.0.1');

        $code = 'empty';
        $args = array($pathInfo, array('standard/search/KeywordText'), &$code);
        $this->assertTrue($method->invokeArgs($deployer, $args));
        $this->assertTrue(Text::stringContains($code, 'empty'));
        $this->assertTrue(Text::stringContains($code, 'banana'));
        $this->assertTrue(Text::stringContains($code, '<input'));

        // Widget calls can exist within rn:blocks
        $dir = get_cfg_var('upload_tmp_dir') . '/unitTest/' . get_class($this);
        $viewFile = "$dir/1.0.2/view.php";
        $widget = '<rn:widget path="utils/Blank"/>';
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure($viewFile, "<rn:block id='KeywordText-preInput'>{$widget}</rn:block>");
        $pathInfo = new Widget\PathInfo('standard', 'standard/foo/Rex', $dir, '1.0.2');

        $code = 'empty';
        $args = array($pathInfo, array('standard/search/KeywordText'), &$code);
        $this->assertTrue($method->invokeArgs($deployer, $args));
        $this->assertTrue(Text::stringContains($code, 'empty'));
        $this->assertTrue(Text::stringContains($code, $widget));
        $this->assertTrue(Text::stringContains($code, '<input'));

        \RightNow\Utils\FileSystem::removeDirectory($dir, true);
    }

    function testAssetPathRetrievalMethods(){
        $upgradeProductionDeploy = new \RightNow\Internal\Libraries\UpgradeProductionDeployOptions();
        $this->assertIdentical(HTMLROOT . '/euf_backup/', $upgradeProductionDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = '1';
        $this->assertIdentical(HTMLROOT . '/euf_backup/', $upgradeProductionDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = 0;
        $this->assertIdentical(HTMLROOT . '/euf_backup/', $upgradeProductionDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = 'yes';
        $this->assertIdentical(HTMLROOT . '/euf_backup/', $upgradeProductionDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = '0';
        $this->assertIdentical(HTMLROOT . '/euf/', $upgradeProductionDeploy->getEufBaseDir());
        $this->assertIdentical(HTMLROOT . '/euf/generated/optimized/', $upgradeProductionDeploy->getOptimizedProductionEufBaseDir());
        $this->assertIdentical(HTMLROOT . '/euf/generated/staging//optimized/', $upgradeProductionDeploy->getOptimizedStagingEufBaseDir());

        $stageObject = new \RightNow\Internal\Libraries\Stage('staging_01', array('pushVersionChanges' => false, 'lockCreatedTime' => 0));
        $upgradeStagingDeploy = new \RightNow\Internal\Libraries\UpgradeStagingDeployOptions($stageObject);
        $this->assertIdentical(HTMLROOT . '/euf/generated/staging/staging_01/optimized/', $upgradeStagingDeploy->getOptimizedStagingEufBaseDir());
        $_SERVER['CP_ACTIVE'] = '1';
        $this->assertIdentical(HTMLROOT . '/euf_backup/', $upgradeStagingDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = 0;
        $this->assertIdentical(HTMLROOT . '/euf_backup/', $upgradeStagingDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = 'yes';
        $this->assertIdentical(HTMLROOT . '/euf_backup/', $upgradeStagingDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = '0';
        $this->assertIdentical(HTMLROOT . '/euf/', $upgradeStagingDeploy->getEufBaseDir());

        // @@@ 140818-000098 Always use euf directory for SP deploys
        $servicePackStagingDeploy = new \RightNow\Internal\Libraries\ServicePackStagingDeployOptions($stageObject);
        $_SERVER['CP_ACTIVE'] = '1';
        $this->assertIdentical(HTMLROOT . '/euf/', $servicePackStagingDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = 0;
        $this->assertIdentical(HTMLROOT . '/euf/', $servicePackStagingDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = 'yes';
        $this->assertIdentical(HTMLROOT . '/euf/', $servicePackStagingDeploy->getEufBaseDir());
        $_SERVER['CP_ACTIVE'] = '0';
        $this->assertIdentical(HTMLROOT . '/euf/', $servicePackStagingDeploy->getEufBaseDir());

    }

    function testVerifyWidget() {
        $method = $this->getMethodDeployer('verifyWidget');

        // widget path does not exist
        $widgetPath = 'not/a/widget';
        $containerPath = 'not/a/containerPath';
        $template = "%s: '%s', is referencing a widget that does not exist (widget path: %s)";
        $this->assertEqual(sprintf($template, 'template', $containerPath, $widgetPath), $method($widgetPath, $containerPath, true));
        $this->assertEqual(sprintf($template, 'page', $containerPath, $widgetPath), $method($widgetPath, $containerPath, false));

        // Accepts valid widget paths as well as PathInfo objects
        $widgetPath = 'standard/input/TextInput';
        $PathInfoClass = '\\RightNow\\Internal\\Libraries\\Widget\\PathInfo';
        $this->assertIsA($method($widgetPath, $containerPath, true), $PathInfoClass);
        $this->assertIsA($method(Registry::getWidgetPathInfo($widgetPath), $containerPath, true), $PathInfoClass);

        // Custom widget
        $widgetPath = 'custom/sample/SampleWidget';
        $widget = Registry::getWidgetPathInfo($widgetPath);
        $this->assertIsA($method($widget, $containerPath, true), $PathInfoClass);

        // Custom widget, sans prefix
        $widget = Registry::getWidgetPathInfo('sample/SampleWidget');
        $this->assertIsA($method($widget, $containerPath, true), $PathInfoClass);

        // Bad absolute path.
        $badWidget = new \RightNow\Internal\Libraries\Widget\PathInfo('custom', $widgetPath, '/an/invalid/absolute/path', '1.0');
        $result = $method($badWidget, $containerPath, true);
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'needs to be activated'));

        // Ensure Widgets::verifyWidgetReferences() is called and an error is returned when widget does not claim support for framework version.
        // Custom
        $class = new ReflectionClass($PathInfoClass);
        $instance = $class->newInstanceArgs(array('custom', $widgetPath, $widget->absolutePath, ''));
        $meta = $instance->meta;
        $this->assertTrue(is_array($meta));
        $this->assertTrue(is_array($meta['requires']));
        $meta['requires']['framework'] = array('9.999');
        $property = $class->getProperty('meta');
        $property->setAccessible(true);
        $property->setValue($instance, $meta);
        $result = $method($instance, $containerPath, true);
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'not supported on the current framework'));

        // Standard
        $widgetPath = 'standard/input/TextInput';
        $versionHistory = $oldVersionHistory = Version::getVersionHistory(false, false);
        $version = max(array_keys($versionHistory['widgetVersions'][$widgetPath]));
        $versionHistory['widgetVersions'][$widgetPath][$version] = array('requires' => array('framework' => array('9.999')));
        Registry::setTargetPages('development');
        $this->assertTrue(Version::writeVersionHistory($versionHistory));
        Version::clearCacheVariables();
        $result = $method(Registry::getWidgetPathInfo($widgetPath), $containerPath, true);
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'not supported on the current framework'));

        Version::writeVersionHistory($oldVersionHistory);
        Version::clearCacheVariables();
        Registry::setTargetPages('development');

        // Ensure history restored and widget is now valid.
        $result = $method(Registry::getWidgetPathInfo($widgetPath), $containerPath, true);
        $this->assertIsA($result, $PathInfoClass);
    }

    function testAddIncludes() {
        $method = $this->getMethodDeployer('addIncludes');
        $templateFile = 'templateFile:UnitTest';
        $pageFile = 'pageFile:UnitTest';

        //Test with module type 'standard'
        $content = "<body></body>";
        $meta = array(
            'javascript_module' => 'standard'
        );
        $result = $method($content, $templateFile, array(), $pageFile, array(), array(), array(), $meta, '', false);

        //Ensure that all of the output JS occurs in the result and in the correct order
        $lastLocation = 0;
        $expectedFunctions = array(
            'getYuiConfiguration',
            'RightNow.js',
            $templateFile,
            $pageFile,
            'convertWidgetInterfaceCalls',
            'getClientInitializer',
            'getAdditionalJavaScriptReferences()'
        );

        foreach($expectedFunctions as $functionName) {
            $this->assertTrue(Text::stringContains($result, $functionName));
            $position = strpos($result, $functionName);
            $this->assertTrue($lastLocation < $position);
            $lastLocation = $position;
        }

        //Test with module type 'none'
        $content = "<body></body>";
        $meta = array(
            'javascript_module' => 'none'
        );
        $result = $method($content, $templateFile, array(), $pageFile, array(), array(), array(), $meta, '', false);

        //Module type none should contain significantly less output
        $lastLocation = 0;
        $expectedFunctions = array(
            $templateFile,
            $pageFile,
            'getAdditionalJavaScriptReferences()'
        );

        foreach($expectedFunctions as $functionName) {
            $this->assertTrue(Text::stringContains($result, $functionName));
            $position = strpos($result, $functionName);
            $this->assertTrue($lastLocation < $position);
            $lastLocation = $position;
        }
    }

    function testCreateWidgetHeaderFunction() {
        $method = $this->getMethodDeployer('createWidgetHeaderFunction');

        $widget = Registry::getWidgetPathInfo('standard/knowledgebase/RelatedAnswers');
        $meta = array(
          'controller_path' => 'standard/knowledgebase/RelatedAnswers',
          'view_path' => 'standard/knowledgebase/RelatedAnswers',
          'version' => '1.0.1',
          'requires' => array(),
          'attributes' => array(),
          'info' => array(),
          'absolutePath' => '/bulk/httpd/cgi-bin/scott.cfg/scripts/cp/core/widgets/standard/knowledgebase/RelatedAnswers',
          'relativePath' => 'standard/knowledgebase/RelatedAnswers',
          'widget_name' => 'RelatedAnswers',
        );

        $expected = <<<HEADER

function _standard_knowledgebase_RelatedAnswers_header() {
    \$result = array(
        'js_name' => '',
        'library_name' => 'RelatedAnswers',
        'view_func_name' => '_standard_knowledgebase_RelatedAnswers_view',
        'meta' => array(
            'controller_path' => 'standard/knowledgebase/RelatedAnswers',
            'view_path' => 'standard/knowledgebase/RelatedAnswers',
            'version' => '1.0.1',
            'requires' => array (),
            'info' => array (),
            'relativePath' => 'standard/knowledgebase/RelatedAnswers',
            'widget_name' => 'RelatedAnswers',
        ),
    );
    \$result['meta']['attributes'] = array();
    return \$result;
}
HEADER;

        $results = $method($widget, $meta);
        $this->assertIdentical(str_replace(array(' ', "\n"), '', $expected), str_replace(array(' ', "\n"), '', $results));
        $this->assertFalse(Text::stringContains($results, 'absolutePath')); // trigger false diffs for service packs
    }

    function testCreateWidgetViewCodeForSimpleView() {
        $method = $this->getMethodDeployer('createWidgetViewCode');
        $input = array(
            'relativePath' => 'custom/Bananas',
        );
        $expected = <<<FUNC
    function _custom_Bananas_view (\$data) {
        extract(\$data);
        ?>bananas<?
    }
FUNC;
        $actual = $method($input, 'bananas');
        $this->assertIdentical($expected, $actual);
    }

    function testCreateWidgetViewCodeWithViewPartials () {
        $method = $this->getMethodDeployer('createWidgetViewCode');
        $input = array(
            'relativePath' => 'standard/feedback/AnswerFeedback',
            'view_partials' => array(
                'buttonView.html.php',
            ),
        );
        $actual = $method($input, 'view');
        $this->assertStringContains($actual, 'function _standard_feedback_AnswerFeedback_view ($data)');
        $this->assertStringContains($actual, 'function _standard_feedback_AnswerFeedback_buttonView ($data)');
        $this->assertStringDoesNotContain($actual, 'rn:block');
    }

    function testCreateExtendedWidgetViewCodeWithViewPartials () {
        $method = $this->getMethodDeployer('createWidgetViewCode');
        list($actual) = $this->returnResultAndContent(function () use ($method) {
            $input = array(
                'relativePath' => 'standard/searchsource/SocialResultListing',
                'extends_view' => array(
                    'standard/searchsource/SocialResultListing',
                    'standard/searchsource/SourceResultListing',
                ),
            );
            return $method($input, 'view');
        });
        $this->assertStringContains($actual, 'function _standard_searchsource_SocialResultListing_view ($data)');
        $this->assertStringContains($actual, 'function _standard_searchsource_SocialResultListing_Results ($data)');
        $this->assertStringContains($actual, 'parent::_standard_searchsource_SourceResultListing_Results($data);');
        $this->assertStringDoesNotContain($actual, 'rn:block');

        // Try with custom widgets
        list($actual) = $this->returnResultAndContent(function () use ($method) {
            $input = array(
                'relativePath' => 'custom/feedback/CustomAnswerFeedback',
                'extends_view' => array(
                    'standard/feedback/AnswerFeedback',
                ),
            );
            return $method($input, 'view');
        });
        $this->assertStringContains($actual, 'function _custom_feedback_CustomAnswerFeedback_view ($data)');
        $this->assertStringContains($actual, 'function _custom_feedback_CustomAnswerFeedback_buttonView ($data)');
        $this->assertStringContains($actual, 'parent::_standard_feedback_AnswerFeedback_buttonView($data)');
        $this->assertStringContains($actual, 'function _custom_feedback_CustomAnswerFeedback_rankLabels ($data)');
        $this->assertStringContains($actual, 'parent::_standard_feedback_AnswerFeedback_rankLabels($data);');
        $this->assertStringContains($actual, 'function _custom_feedback_CustomAnswerFeedback_ratingMeter ($data)');
        $this->assertStringContains($actual, 'parent::_standard_feedback_AnswerFeedback_ratingMeter($data);');
        $this->assertStringDoesNotContain($actual, 'rn:block');

        // Try with custom widgets
        list($actual) = $this->returnResultAndContent(function () use ($method) {
            $input = array(
                'relativePath' => 'custom/viewpartialtest/ExtendedCustomQuestionDetail',
                'extends_view' => array(
                    'custom/viewpartialtest/CustomQuestionDetail',
                    'standard/discussion/QuestionDetail',
                ),
            );
            return $method($input, 'view');
        });
        $this->assertStringContains($actual, "function _custom_viewpartialtest_ExtendedCustomQuestionDetail_header (\$data) {\n            parent::_custom_viewpartialtest_CustomQuestionDetail_header(\$data);");
        $this->assertStringDoesNotContain($actual, "function _custom_viewpartialtest_ExtendedCustomQuestionDetail_header (\$data) {\n            parent::_standard_discussion_QuestionDetail_header(\$data);");
        $this->assertStringDoesNotContain($actual, 'rn:block');
    }

    function testBuildMinifiedAssets() {
        $class = new ReflectionClass('\RightNow\Internal\Libraries\Deployer');
        $instance = $class->newInstanceArgs(array(new \RightNow\Internal\Libraries\BasicDeployOptions(get_instance()->_getAgentAccount())));

        $method = $class->getMethod('buildMinifiedAssets');
        $method->setAccessible(true);

        // Base case.
        $result = $method->invoke($instance, array('js' => array(), 'js_paths' => array(), 'interface' => array()), array(), array());

        $this->assertIdentical(array(
            'js'             => '',
            'interfaceCalls' => null,
            'js_files'       => array(),
        ), $result);

        // Normal case that includes several widgets.
        $widgetA = Registry::getWidgetPathInfo('standard/input/TextInput');
        $widgetB = Registry::getWidgetPathInfo('standard/feedback/SiteFeedback');
        $widgetC = Registry::getWidgetPathInfo('custom/extended/ParentWidget');
        $widgetD = Registry::getWidgetPathInfo('standard/navigation/NavigationTab');

        $instance->javaScriptFileManager->addFile($widgetA->logic, 'widget');
        $instance->javaScriptFileManager->addFile($widgetB->logic, 'widget');
        $instance->javaScriptFileManager->addFile($widgetC->logic, 'widget');
        $instance->javaScriptFileManager->addFile($widgetD->logic, 'widget');

        // Add some fake dependencies
        $instance->javaScriptFileManager->addWidgetDependencyRelationship($widgetA->logic, $widgetB->logic);
        $instance->javaScriptFileManager->addWidgetDependencyRelationship($widgetC->logic, $widgetA->logic);
        $instance->javaScriptFileManager->addWidgetDependencyRelationship($widgetD->logic, $widgetC->logic);

        $input = array(
            'js' => array(
                array(
                    'type' => $widgetA->type,
                    'path' => $widgetA->relativePath,
                    'fullPath' => $widgetA->logic,
                    'version' => null,
                    'minified' => 'Deep ',
                ),
                array(
                    'type' => $widgetB->type,
                    'path' => $widgetB->relativePath,
                    'fullPath' => $widgetB->logic,
                    'version' => null,
                    'minified' => 'Blue ',
                ),
                array(
                    'type' => $widgetC->type,
                    'path' => $widgetC->relativePath,
                    'fullPath' => $widgetC->logic,
                    'version' => null,
                    'minified' => 'Ocean ',
                ),
                array(
                    'type' => $widgetD->type,
                    'path' => $widgetD->relativePath,
                    'fullPath' => $widgetD->logic,
                    'version' => null,
                    'minified' => 'Fish',
                ),
            ),
            'js_paths' => array(
                $widgetA->logic,
                $widgetB->logic,
                $widgetC->logic,
                $widgetD->logic,
            ),
            'interface' => array(
                $widgetA->logic,
                $widgetB->logic,
                $widgetC->logic,
                $widgetD->logic,
            ),
        );
        $result = $method->invoke($instance, $input);
        $this->assertIsA($result['interfaceCalls'], 'array');
        $this->assertIdentical("\nBlue \nDeep \nOcean \nFish", $result['js']); // shows order is valid (B, A, C, D)
        $lastJS = end($result['js_files']);
        $this->assertEndsWith($lastJS['path'], 'standard/navigation/NavigationTab');

        // Helpers are pulled in.
        $input['js'][0]['minified'] = file_get_contents($widgetA->logic);
        $result = $method->invoke($instance, $input);
        $eventHelper = $result['js_files'][0]['path'];
        $this->assertIsA($result['interfaceCalls'], 'array');
        // replace %s to prevent confusion in CPTestCase with sprintf
        $this->assertBeginsWith(str_replace('%s', 'string', trim($result['js'])), 'RightNow.EventProvider=');
        $eventHelper = $result['js_files'][0]['path'];
        $this->assertEndsWith($eventHelper, 'EventProvider.js');

        // Helper is omitted because the caller specifies so.
        $result = $method->invoke($instance, $input, array($eventHelper));
        $this->assertIsA($result['interfaceCalls'], 'array');
        // replace %s to prevent confusion in CPTestCase with sprintf
        $this->assertStringDoesNotContain(str_replace('%s', 'string', trim($result['js'])), 'RightNow.EventProvider=');
        $this->assertEndsWith($result['js_files'][0]['path'], 'Field.js');
    }

    function testGetWidgetHelpers() {
        $method = $this->getMethodDeployer('getWidgetHelpers');

        $this->assertIdentical(array(), $method('', ''));

        $result = $method('RightNow.Form.bananas', null);
        $this->assertSame(2, count($result));
        $this->assertSame('helper', $result[0]['type']);
        $this->assertBeginsWith($result[0]['path'], '/euf/');
        $this->assertEndsWith($result[0]['path'], 'EventProvider.js');
        $this->assertBeginsWith($result[0]['fileSystemPath'], '/bulk/');
        $this->assertEndsWith($result[0]['fileSystemPath'], 'EventProvider.js');
        $this->assertTrue(array_key_exists('minified', $result[0]));
        $this->assertSame('helper', $result[1]['type']);
        $this->assertBeginsWith($result[1]['path'], '/euf/');
        $this->assertEndsWith($result[1]['path'], 'Form.js');
        $this->assertBeginsWith($result[1]['fileSystemPath'], '/bulk/');
        $this->assertEndsWith($result[1]['fileSystemPath'], 'Form.js');
        $this->assertTrue(array_key_exists('minified', $result[1]));

        $this->assertIdentical(array(), $method('RightNow.EventProvider.foo', array(
            \RightNow\Utils\Url::getCoreAssetPath('js/' . MOD_BUILD_SP . '.' . MOD_BUILD_NUM . '/min/widgetHelpers/EventProvider.js')
        )));
    }

    function testCreateWidgetPageCode() {
        $createWidgetPageCode = $this->getMethodDeployer('createWidgetPageCode');

        $input = array(
            'standard/login/BasicLoginForm' => array(
                'referencedBy' => array('spirit' => true),
            ),
        );

        list($result) = $this->returnResultAndContent(function () use ($createWidgetPageCode, $input) {
            return $createWidgetPageCode($input);
        });

        $childPosition = strpos($result, "class BasicLoginForm extends \\RightNow\\Widgets\\LoginForm");
        $parentPosition = strpos($result, "class LoginForm extends \\RightNow");
        $this->assertTrue($childPosition >= 0);
        $this->assertTrue($childPosition > $parentPosition, "child position should be below its parent");

        // clear the cache since CI runs in the same process
        \RightNow\Internal\Utils\WidgetViews::removeExtendingView('standard/login/BasicResetPassword');
    }

    function testGetJavaScriptInfoForWidgets () {
        $default = array('js' => array(), 'js_paths' => array(), 'interface' => array());
        $method = $this->getMethodDeployer('getJavaScriptInfoForWidgets');

        $result = $method(array());
        $this->assertIdentical($default, $result);

        $result = $this->returnResultAndContent(function () use ($method) {
            return $method(array('standard/utils/Blank' => array('referencedBy' => null)));
        });
        $this->assertIdentical($default, $result[0]);

        list($result) = $this->returnResultAndContent(function () use ($method) {
            return $method(array('standard/feedback/AnswerFeedback' => array('referencedBy' => null)));
        });
        $this->assertTrue(count($result['js']) > 0);
        $this->assertSame('standard', $result['js'][0]['type']);
        $this->assertSame('standard/feedback/AnswerFeedback', $result['js'][0]['path']);
        $this->assertTrue(strlen($result['js'][0]['minified']) > 100);
        $this->assertTrue(count($result['js_paths']) > 0);
        $this->assertTrue(count($result['interface']) > 0);

        // Omitted
        $result = $method(array('standard/feedback/AnswerFeedback' => array('referencedBy' => null)), array('standard/feedback/AnswerFeedback' => null));
        $this->assertIdentical($default, $result);

        // Does not exist
        $result = $method(array('standard/doesnot/exist' => array('referencedBy' => null)));
        $this->assertIdentical($default, $result);
    }

    function testIndicateWidgetsReferencedByThisView() {
        $method = $this->getMethodDeployer('indicateWidgetsReferencedByThisView');

        $result = $method(array('path' => array()), 'lovers');
        $this->assertIdentical(array('path' => array('referencedBy' => array('lovers' => true))), $result);
    }

    function testGetSubWidgetDependencies() {
        $method = $this->getMethodDeployer('getSubWidgetDependencies');

        $widget = Registry::getWidgetPathInfo('standard/search/AdvancedSearchDialog');
        $expected = array(
            'standard/search/KeywordText',
            'standard/search/SearchTypeList',
            'standard/search/WebSearchSort',
            'standard/search/WebSearchType',
            'standard/search/ProductCategorySearchFilter',
            'standard/search/FilterDropdown',
            'standard/search/SortList',
        );
        $result = $method($widget);
        $this->assertIdentical($expected, $result);

        $widget = Registry::getWidgetPathInfo('standard/input/FormInput');
        $expected = array(
            0 => 'standard/input/TextInput',
            1 => 'standard/input/SelectionInput',
            2 => 'standard/input/DateInput',
            3 => 'standard/input/PasswordInput',
            5 => 'standard/input/DisplayNameInput',
            6 => 'standard/output/FieldDisplay',
        );
        $result = $method($widget);
        $this->assertIdentical($expected, $result);

        $widget = Registry::getWidgetPathInfo('standard/discussion/QuestionComments');
        $expected = array(
            'standard/feedback/SocialContentFlagging',
            'standard/feedback/SocialContentRating',
            'standard/input/FileAttachmentUpload',
            'standard/input/FormSubmit',
            'standard/input/RichTextInput',
            'standard/input/SocialFileAttachmentUpload',
            'standard/input/TextInput',
            'standard/moderation/ModerationInlineAction',
            'standard/output/DataDisplay',
            'standard/output/FieldDisplay',
            'standard/output/FileListDisplay',
            'standard/output/IncidentThreadDisplay',
            'standard/output/ProductCategoryDisplay',
        );
        $result = $method($widget);
        sort($expected);
        sort($result);
        $this->assertIdentical($expected, $result);
    }

    function testMakeTagsForWidgetCss(){
        $method = $this->getMethodDeployer('makeTagsForWidgetCss');

        $result = $method(null, null, "", "", null, null);
        $this->assertIdentical($result, "");

        $result = $method(null, null, "template CSS", "page CSS", null, null);
        $this->assertIdentical($result, "<style type=\"text/css\">\n<!--\ntemplate CSSpage CSS\n-->\n</style>\n");
    }

    function testCheckAndStripWidgetViewHelperReturnsEmptyStringForWidgetWithoutHelper() {
        $method = $this->getMethodDeployer('checkAndStripWidgetViewHelper');
        $this->assertIdentical('', $method(Registry::getWidgetPathInfo('standard/utils/Blank')));
    }

    function testCheckAndStripWidgetViewHelperReturnsHelperCode() {
        $method = $this->getMethodDeployer('checkAndStripWidgetViewHelper');
        $result = $method(Registry::getWidgetPathInfo('standard/discussion/QuestionComments'));

        $this->assertTrue(strlen($result) > 10);
        $this->assertEndsWith(preg_replace('/\s/', '', $result), "}}}");
        $this->assertBeginsWith(trim($result), 'namespace');
    }

    // @@@ 140421-000081
    function testRightNowLogoCompliance() {
        $method = new \ReflectionMethod('\RightNow\Internal\Libraries\RightNowLogoCompliance', 'getPages');
        $method->setAccessible(true);

        $results = $method->invoke(null);
        $this->assertTrue(is_array($results));
        $this->assertTrue(array_key_exists('/answers/detail.php', $results));
        $this->assertTrue(array_key_exists('/home.php', $results));

    // @@@ 140421-000081 - Reset logo pages to search using a directory were neither page will be found

        $setDirMethod = new \ReflectionMethod('\RightNow\Internal\Libraries\RightNowLogoCompliance', 'setPagesDirectory');
        $setDirMethod->setAccessible(true);
        $results = $setDirMethod->invoke(null, "..");

        $method = new \ReflectionMethod('\RightNow\Internal\Libraries\RightNowLogoCompliance', 'getPages');
        $method->setAccessible(true);

        $results = $method->invoke(null);
        $this->assertTrue(is_array($results));
        $this->assertFalse(array_key_exists('/answers/detail.php', $results));
        $this->assertFalse(array_key_exists('/home.php', $results));

    }

    function testGetRenderCallPathList() {
        $method = $this->getMethodDeployer('getRenderCallPathList');

        $viewCode = '<?=\\RightNow\\Utils\\Widgets::rnWidgetRenderCall(\'input/BasicFormStatusDisplay\', array());?>
                     <?=\\RightNow\\Utils\\Widgets::rnWidgetRenderCall(\'input/BasicFormSubmit\', array());?>';
        $expected = array(
                      0 => 'input/BasicFormStatusDisplay',
                      1 => 'input/BasicFormSubmit');
        $this->assertIdentical($method($viewCode), $expected);

        $viewCode = 'Thi@(::sIsABun+ch_=OfGibberi@@sh and &I\'m testin@g the^Return__val%ue@';
        $this->assertIdentical($method($viewCode), array());
    }

    // @@@ 150107-000095
    function testOutputBackupDirForSPDeploy() {
        $servicePackDeploy = new \RightNow\Internal\Libraries\ServicePackDeployOptions();
        $backupDir = $servicePackDeploy->getOutputBaseDir() . 'backup';
        if (is_dir($backupDir)) {
            rename($backupDir, $backupDir . 'orig');
        }
        $this->assertFalse(is_dir($backupDir));
        $servicePackBackupDir = $servicePackDeploy->getOutputBackupDir();
        $this->assertEqual($servicePackBackupDir, $backupDir);
        FileSystem::mkdirOrThrowExceptionOnFailure($backupDir, true);
        $servicePackBackupDir = $servicePackDeploy->getOutputBackupDir();
        $this->assertEqual($servicePackBackupDir, $backupDir . '/servicePack');
        @rmdir($backupDir);
        if (is_dir($backupDir . 'orig')) {
            rename($backupDir . 'orig', $backupDir);
        }
    }

    // @@@ 150403-000051
    function testGetAssetsSourceDir() {
        $upgradeProdDeploy = new \RightNow\Internal\Libraries\UpgradeProductionDeployOptions();
        $this->assertEqual(null, $upgradeProdDeploy->getAssetsSourceDir());
        $this->assertEqual(null, $upgradeProdDeploy->getTempAssetsSourceDir());

        $stageObject = new \RightNow\Internal\Libraries\Stage('staging_01', array('pushVersionChanges' => false, 'lockCreatedTime' => 0));
        $upgradeStagingDeploy = new \RightNow\Internal\Libraries\UpgradeStagingDeployOptions($stageObject);
        $this->assertEqual("{$upgradeStagingDeploy->stagingHtmlRootDir}source/", $upgradeStagingDeploy->getAssetsSourceDir());
        $this->assertEqual("{$upgradeStagingDeploy->stagingHtmlRootDir}temp_source/", $upgradeStagingDeploy->getTempAssetsSourceDir());

        $servicePackDeploy = new \RightNow\Internal\Libraries\ServicePackDeployOptions();
        $this->assertEqual(null, $servicePackDeploy->getAssetsSourceDir());
        $this->assertEqual(null, $servicePackDeploy->getTempAssetsSourceDir());

        $servicePackStagingDeploy = new \RightNow\Internal\Libraries\ServicePackStagingDeployOptions($stageObject);
        $this->assertEqual("{$servicePackStagingDeploy->stagingHtmlRootDir}source/", $servicePackStagingDeploy->getAssetsSourceDir());
        $this->assertEqual("{$servicePackStagingDeploy->stagingHtmlRootDir}temp_source/", $servicePackStagingDeploy->getTempAssetsSourceDir());
    }

    // @@@ 170109-000085
    function testUpgradeVerifyAllRightNowLogoPagesSet() {
        $upgradeProdDeploy = new \RightNow\Internal\Libraries\UpgradeProductionDeployOptions();
        $this->assertEqual(false, $upgradeProdDeploy->shouldVerifyAllRightNowLogoPagesSet());
        $upgradeStagingDeploy = new \RightNow\Internal\Libraries\UpgradeProductionDeployOptions();
        $this->assertEqual(false, $upgradeStagingDeploy->shouldVerifyAllRightNowLogoPagesSet());
    }
}
