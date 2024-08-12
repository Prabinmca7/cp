<?php

use RightNow\Libraries\ClientLoader,
    RightNow\Utils\Text,
    RightNow\Internal\Libraries\Widget\PathInfo,
    RightNow\Internal\Libraries\Widget\Registry;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ClientLoaderTest extends CPTestCase {
    public $testingClass = '\RightNow\Libraries\ClientLoader';

    function testParseJavaScript() {
        $method = $this->getMethod('parseJavaScript', array(new \RightNow\Internal\Libraries\DevelopmentModeClientLoaderOptions));

        $return = $method('');
        $this->assertIdentical(array(array(), array()), $return);

        // String content
        $return = $method('RightNow.Interface.getMessage("ANS_ENABLED_DESC_LBL"), RightNow.Interface.getConfig("CP_404_URL")');
        $this->assertIdentical(array('ANS_ENABLED_DESC_LBL'), array_keys($return[0]));
        $this->assertIdentical(array('CP_404_URL'), array_keys($return[1]));

        // Array of contents
        $return = $method(array(
            'name1' => 'asfds RightNow.Interface.getMessage("ANS_ENABLED_DESC_LBL")',
            'name2' => '(◡ ‿ ◡ ✿)',
            'name3' => 'RightNow.Interface.getConfig("CP_404_URL")',
        ));
        $this->assertIdentical(array('ANS_ENABLED_DESC_LBL'), array_keys($return[0]));
        $this->assertIdentical(array('CP_404_URL'), array_keys($return[1]));
    }

    function testGetJavascriptContent() {
        $instance = new ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());

        $instance->setJavaScriptModule(ClientLoader::MODULE_NONE);

        $instance->addJavaScriptInclude('/Desert.js', 'widget');
        $instance->addJavaScriptInclude('/Dinner.js', 'widget');
        $instance->addJavaScriptInclude('/Appetizer.js', 'widget');

        $content = $instance->getJavascriptContent(false);

        $expectedResult = <<<EXPECTEDRESULT
<script src='/Desert.js'></script>
<script src='/Dinner.js'></script>
<script src='/Appetizer.js'></script>
EXPECTEDRESULT;

        $this->assertEqual($expectedResult, trim($content));
    }

    function testModuleTypes() {
        $instance = new ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());
        $instance->setJavaScriptModule(ClientLoader::MODULE_NONE);
        $this->assertTrue($instance->isJavaScriptModuleNone());
        $this->assertFalse($instance->isJavaScriptModuleStandard());
        $this->assertFalse($instance->isJavaScriptModuleMobile());

        $instance->setJavaScriptModule('banana');
        $this->assertFalse($instance->isJavaScriptModuleNone());
        $this->assertFalse($instance->isJavaScriptModuleStandard());
        $this->assertFalse($instance->isJavaScriptModuleMobile());

        $instance->setJavaScriptModule('');
        $this->assertTrue($instance->isJavaScriptModuleStandard());
        $this->assertFalse($instance->isJavaScriptModuleNone());
        $this->assertFalse($instance->isJavaScriptModuleMobile());

        $instance->setJavaScriptModule(ClientLoader::MODULE_STANDARD);
        $this->assertTrue($instance->isJavaScriptModuleStandard());
        $this->assertFalse($instance->isJavaScriptModuleNone());
        $this->assertFalse($instance->isJavaScriptModuleMobile());

        $instance->setJavaScriptModule(ClientLoader::MODULE_MOBILE);
        $this->assertTrue($instance->isJavaScriptModuleMobile());
        $this->assertFalse($instance->isJavaScriptModuleNone());
        $this->assertFalse($instance->isJavaScriptModuleStandard());
    }

    function testConvertWidgetInterfaceCalls() {
        $class = new \ReflectionClass('\RightNow\Internal\Libraries\ClientLoader');
        $instance = $class->newInstanceArgs(array(new \RightNow\Internal\Libraries\DevelopmentModeClientLoaderOptions));
        $method = $class->getMethod('convertWidgetInterfaceCalls');
        $method->setAccessible(true);
        $calls = $class->getProperty('widgetInterfaceCalls');
        $calls->setAccessible(true);

        // Empty
        $method->invoke($instance, array(), array());
        $result = $calls->getValue($instance);
        $this->assertIdentical(array('config' => array(), 'message' => array()), $result);

        // String message stays a string, config must be an int
        $method->invoke($instance, array('foo' => array('value' => 'banana')), array('bar' => array('value' => CP_404_URL)));
        $result = $calls->getValue($instance);
        $this->assertSame(1, count($result['message']));
        $this->assertSame(1, count($result['config']));
        $this->assertIdentical(array('foo'), array_keys($result['message']));
        $this->assertIdentical(array('bar'), array_keys($result['config']));

        // String given for config slot
        try {
            $method->invoke($instance, array('foo' => array('value' => PCT_S_PCT_S_REFERENCING_WIDGET_EX_MSG)), array('bar' => array('value' => 'banana')));
            $this->fail("Exception wasn't thrown");
        }
        catch (\Exception $e) {}

        // Append onto existing
        $method->invoke($instance, array('whoah' => array('value' => PCT_S_PCT_S_REFERENCING_WIDGET_EX_MSG)), array());
        $result = $calls->getValue($instance);
        $this->assertSame(2, count($result['message']));
    }

    function testGetClientInitializer() {
        list(
            $class,
            $getClientInitializer,
            $setJavaScriptModule
        ) = $this->reflect('method:getClientInitializer', 'method:setJavaScriptModule');

        $instance = $class->newInstanceArgs(array(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions));

        $setJavaScriptModule->invoke($instance, '');
        $clientCode = $getClientInitializer->invoke($instance);
        $this->assertTrue(Text::stringContains($clientCode, "module:'standard'"));

        $setJavaScriptModule->invoke($instance, 'STANDARD');
        $clientCode = $getClientInitializer->invoke($instance);
        $this->assertTrue(Text::stringContains($clientCode, "module:'standard'"));

        $setJavaScriptModule->invoke($instance, 'mobilE');
        $clientCode = $getClientInitializer->invoke($instance);
        $this->assertTrue(Text::stringContains($clientCode, "module:'mobile'"));
    }

    function testGetProfileData()
    {
        $this->downgradeErrorReporting();
        $class = new \ReflectionClass('\RightNow\Internal\Libraries\ClientLoader');
        $instance = $class->newInstanceArgs(array(new \RightNow\Internal\Libraries\DevelopmentModeClientLoaderOptions));
        $method = $class->getMethod('getProfileData');
        $method->setAccessible(true);

        $result = json_decode($method->invoke($instance));
        $this->assertTrue(is_object($result));
        $this->assertFalse($result->isLoggedIn);

        // check that the right keys exist
        $result = get_object_vars($result);
        foreach (array('isLoggedIn', 'previouslySeenEmail') as $key) {
            $this->assertTrue(array_key_exists($key, $result));
        }

        foreach (array('firstName', 'lastName', 'email', 'contactID', 'socialUserID') as $key) {
            $this->assertFalse(array_key_exists($key, $result));
        }

        // logged in as a contact
        $this->logIn();
        get_instance()->session->setSessionData(array('previouslySeenEmail' => 'blah@blah.com'));

        $result = json_decode($method->invoke($instance));
        $this->assertTrue(is_object($result));
        $this->assertTrue($result->isLoggedIn);
        $this->assertIdentical($result->previouslySeenEmail, 'blah@blah.com');
        $this->assertIdentical($result->contactID, 1286);
        $this->assertIdentical($result->socialUserID, 109);

        $result = get_object_vars($result);
        foreach (array('isLoggedIn', 'previouslySeenEmail', 'firstName', 'lastName', 'email', 'contactID', 'socialUserID') as $key) {
            $this->assertTrue(array_key_exists($key, $result));
            $this->assertNotNull($result[$key]);
        }

        // cleanup
        get_instance()->session->setSessionData(array('previouslySeenEmail' => null));
        $this->logOut();
        $this->restoreErrorReporting();
    }

    function testAdditionalJavaScriptReferences() {
        $instance = new ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());

        // add include
        $value = $instance->addJavaScriptInclude("/path/to/somefile.js");
        $jsValue = "<script src='/path/to/somefile.js'></script>";
        $this->assertSame($jsValue, $value);

        // add inline code
        $instance->addJavaScriptInLine("var test = true;", true);
        $value = $instance->getAdditionalJavaScriptReferences();
        $this->assertSame(Text::stringContains($value, "var test = true;"), true);

        // add another file as inline code
        $testJavaScriptFile = CPCORE . 'Libraries/tests/test.js';
        file_put_contents($testJavaScriptFile, "var anotherTest = false;");
        $instance->addJavaScriptInline($testJavaScriptFile);

        $value = $instance->getAdditionalJavaScriptReferences();
        $jsValue .= "\n<script>\nvar test = true;\nvar anotherTest = false;\n</script>\n";
        $this->assertSame($jsValue, $value);

        unlink($testJavaScriptFile);
    }

    function testGetYuiConfiguration() {
        $extractJson = function ($string) {
            return json_decode(Text::getSubstringAfter(Text::getSubstringBefore($string, ";</script>"), "YUI_config="));
        };
        $instance = new ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());
        $value = $instance->getYuiConfiguration();
        // since the YUI version can change, parse the JSON returned and check for specific property names
        $json = $extractJson($value);

        // make sure we have the following:
        // {"comboBase":"...","lang":"...","fetchCSS":...,"groups":{"gallery-treeview":{"base":"...","modules":{"gallery-treeview":{"path":"..."}}}}}
        $this->assertTrue(isset($json->comboBase));
        $this->assertTrue(isset($json->lang));
        $this->assertIdentical(array('en-US', 'en-US'), $json->lang);
        $this->assertTrue(isset($json->fetchCSS));
        $this->assertTrue(isset($json->groups->{'gallery-treeview'}->base));
        $this->assertTrue(isset($json->groups->{'gallery-treeview'}->modules->{'gallery-treeview'}->path));
        $this->assertIsA($json->modules, 'stdClass');

        $value = $instance->getYuiConfiguration(array('old' => 'jacket'));
        $json = $extractJson($value);
        $this->assertSame('jacket', $json->old);
    }

    function testCreateCSSTag() {
        $instance = new ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());
        $value = $instance->createCSSTag('/rnt/rnw/css/admin.css', true);
        if (preg_match("/href='(.+?)'/", $value, $matches)) {
            $this->assertSame(Text::beginsWith($matches[1], "http"), true); // could be http or https
            $this->assertSame(Text::endsWith($matches[1], "/rnt/rnw/css/admin.css"), true);
        }
        else {
            $this->fail();
        }

        // should return null, each cssPath can only be used once
        $value = $instance->createCSSTag('/rnt/rnw/css/admin.css', false);
        $this->assertIdentical($value, null);

        // second parm defaults to false, make sure that doesn't change
        $value = $instance->createCSSTag('/rnt/rnw/css/ma.css');
        if (preg_match("/href='(.+?)'/", $value, $matches)) {
            $this->assertSame(Text::beginsWith($matches[1], "http"), false);
            $this->assertSame($matches[1], "/rnt/rnw/css/ma.css");
        }
        else {
            $this->fail();
        }
    }

    function testAddHeadContent() {
        $instance = new ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());
        $instance->addStylesheet("/rnt/rnw/css/admin.css");

        $value = $instance->createCSSTag('/rnt/rnw/css/ma.css');
        $instance->addHeadContent($value);
        $value = $instance->getHeadContent();

        // addStylesheet uses " instead of ' like createCSSTag
        if (preg_match_all('/^\<link.*href=["\'](.+?)["\']/m', $value, $matches)) {
            $this->assertSame("/rnt/rnw/css/admin.css", $matches[1][0]);
            $this->assertSame("/rnt/rnw/css/ma.css", $matches[1][1]);
        }
        else {
            $this->fail();
        }
    }

    function testAddWidgetStatics() {
        $class = new \ReflectionClass('\RightNow\Internal\Libraries\ClientLoader');
        $instance = $class->newInstanceArgs(array(new \RightNow\Internal\Libraries\DevelopmentModeClientLoaderOptions));

        $method = $class->getMethod('addWidgetStatics');
        $method->setAccessible(true);

        $method->invoke($instance, array(
            'fullJavaScriptPath' => 'AVerySpecialJSFile',
            'jsClassName' => 'AVerySpecialJSClass',
            'statics' => array('Super Important', 'Statics'),
        ));

        $statics = $class->getProperty('widgetStatics');
        $statics->setAccessible(true);

        $expectedResult = array(
            'AVerySpecialJSFile_AVerySpecialJSClass' => array(
                'class' => 'AVerySpecialJSClass',
                'static' => array('Super Important', 'Statics'),
            ),
        );

        $result = $statics->getValue($instance);

        $this->assertSame($result, $expectedResult);
    }

    function testLoadJavaScriptResource() {
        $instance = new ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());

        $instance->setJavaScriptModule(ClientLoader::MODULE_NONE);

        // Expected return value for use of the <script> tag
        $getScript = function(array $urls, array $options = array()) {
            $js = '';
            foreach ($urls as $url) {
                $js .= \RightNow\Utils\Tags::createJSTag($url, $options['async'] ? 'async defer' : '') . '\n';
            }
            return $js;
        };

        // Expected return value for use of Y.Get.js
        $getYUI = function(array $urls, array $options = array()) {
            if ($callback = $options['callback']) {
                unset($options['callback']);
            }
            return \RightNow\Utils\Tags::createYUIGetJSTag($urls, $options, $callback);
        };

        // Generates unique urls
        $template = 'http://somesite/somejs%d.js';
        $getUrls = function($urls) use ($template) {
            static $counter = 0;
            $list = array();
            foreach((is_array($urls) ? $urls : array($urls)) as $placeholder) {
                $list[] = sprintf($template, $counter);
                $counter++;
            }
            return $list;
        };

        $callback = 'function (err) {if (err) {console.log("Resource failed to load!");} else {console.log("Resource was loaded successfully");}}';

        // Test scenarios containing inputs for $urls and $options, as well as the expected return value.
        // %X% denotes a url generated at run-time, ensuring it is unique.
        $inputs = array(
            array(
                '%1%',
                array(),
                array('type' => 'script', 'async' => false)
            ),
            array(
                array('%1%', '%2%'),
                array('async' => false),
                array('type' => 'script', 'async' => false)
            ),
            array(
                '%1%',
                array('async' => true),
                array('type' => 'script', 'async' => true)
            ),
            array(
                '%1%',
                array('attributes' => array('class' => 'my-js'), 'timeout' => 5000),
                array('type' => 'yui', 'attributes' => array('class' => 'my-js'), 'timeout' => 5000),
            ),
            array(
                array('%1%', '%2%'),
                array('callback' => $callback, 'async' => true),
                array('type' => 'yui', 'callback' => $callback, 'async' => true)
            ),
            array(
                array('%1%', '%2%'),
                array('callback' => $callback),
                array('type' => 'yui', 'callback' => $callback, 'async' => false)
            ),
        );


        $expectedReferences = '';
        foreach($inputs as $input) {
            list($placeholder, $options, $args) = $input;
            $urls = $getUrls($placeholder);
            $type = $args['type'];
            unset($args['type']);
            if ($type === 'script') {
                $expected = $getScript($urls, $options);
            }
            else {
                $expected = $getYUI($urls, $options);
            }

            // Test return value
            $actual = $instance->loadJavaScriptResource($urls, $options);
            $this->assertIdentical($expected, $actual);

            // Test references from JavaScriptFileManager used to write the code to the page output
            $references = $instance->getAdditionalJavaScriptReferences();
            foreach($urls as $url) {
                $url = \RightNow\Utils\Text::getSubstringAfter($url, 'http://somesite/');
                $this->assertStringContains($references, $url);
            }
        }
    }
}

class JavaScriptFileManagerTest extends CPTestCase {
    function testGetJavaScriptPathDependencyCount() {
        $class = new \ReflectionClass('\RightNow\Internal\Libraries\JavaScriptFileManager');
        $instance = $class->newInstance();

        $method = $class->getMethod('getJavaScriptPathDependencyCount');
        $method->setAccessible(true);

        $testArg = array(
            'c' => 'b',
            'a' => 'b',
            'b' => 'z',
            'd' => 'a',
            'f' => 'g',
            'w' => 'g',
            'r' => 'g',
            'g' => 'x'
        );

        $result = $method->invoke($instance, $testArg, 'c');
        $this->assertSame($result, 2);

        $result = $method->invoke($instance, $testArg, 'x');
        $this->assertSame($result, 0);

        $result = $method->invoke($instance, $testArg, 'd');
        $this->assertSame($result, 3);
    }

    function testAddWidgetDependencyRelationship() {
        $class = new \ReflectionClass('\RightNow\Internal\Libraries\JavaScriptFileManager');
        $instance = $class->newInstance();

        $method = $class->getMethod('addWidgetDependencyRelationship');
        $method->invoke($instance, 'house', 'foundation');
        $method->invoke($instance, 'physics', 'theory of relativity');

        $widgetDependencyRelationships = $class->getProperty('widgetDependencyRelationships');
        $widgetDependencyRelationships->setAccessible(true);
        $this->assertSame($widgetDependencyRelationships->getValue($instance), array(
            'house' =>  'foundation',
            'physics' =>  'theory of relativity'
        ));
    }

    function testGetDependencySortedWidgetJavaScriptFiles() {
        $class = new \ReflectionClass('\RightNow\Internal\Libraries\JavaScriptFileManager');

        $widgetTestArgs = array(
            'd', 's', 'q', 'c', 'b', 'a', 'z', 'f', 'g', 'w', 'r', 'x'
        );
        $dependencyTestArgs = array(
            'c' => 'b',
            'a' => 'b',
            'b' => 'z',
            'd' => 'a',
            'f' => 'g',
            'w' => 'g',
            'r' => 'g',
            'g' => 'x'
        );
        $expectedResult = array( // order in this array is very important,
            's',                 // so its listed this way to be easier to read
            'q',
            'x',
            'z',
            'b',
            'g',
            'a',
            'c',
            'r',
            'f',
            'w',
            'd'
        );

        $instance = $class->newInstance();

        $addFileMethod = $class->getMethod('addFile');
        foreach($widgetTestArgs as $widgetTestArg) {
            $addFileMethod->invoke($instance, $widgetTestArg, 'widget');
        }

        $method = $class->getMethod('getDependencySortedWidgetJavaScriptFiles');
        $result = $method->invoke($instance);

        // no dependency relationships - widget attribute should be unchanged
        $this->assertSame($result, $widgetTestArgs);

        $addWidgetJavaScriptDependencyRelationshipMethod = $class->getMethod('addWidgetDependencyRelationship');
        foreach($dependencyTestArgs as $filePath => $dependsOn) {
            $addWidgetJavaScriptDependencyRelationshipMethod->invoke($instance, $filePath, $dependsOn);
        }

        $result = $method->invoke($instance);

        // dependency relationships - widget attribute should be updated (sorted)
        $this->assertSame($result, $expectedResult);

        // all paths should be accounted for
        $this->assertSame(array_diff($result, $expectedResult), array());

        // all dependencies should come before their dependent files
        foreach($dependencyTestArgs as $filePath => $dependsOn) {
            $filePathPosition = array_search($filePath, $result);
            $dependsOnPosition = array_search($dependsOn, $result);
            $this->assertTrue($dependsOnPosition < $filePathPosition);
        }
    }

    function testAddDependentWidgetRelationships() {
        $getClassAndInstance = function() {
            $class = new \ReflectionClass('\RightNow\Internal\Libraries\JavaScriptFileManager');
            $instance = $class->newInstance();
            return array($class, $instance);
        };

        // 'Development Mode' params
        list($class, $instance) = $getClassAndInstance();
        $testAddDependentWidgetRelationshipsMethod = $class->getMethod('addDependentWidgetRelationships');

        $getWidgetJavaScriptPathPath = $class->getMethod('getWidgetJavaScriptPath');
        $grandChildWidgetPathInfo = Registry::getWidgetPathInfo('custom/extended/ChildWidget');
        $grandChildWidgetJSPath = $getWidgetJavaScriptPathPath->invoke($instance, $grandChildWidgetPathInfo->logic);

        $widgetProperty = $class->getProperty('widget');
        $widgetProperty->setAccessible(true);
        $widgetProperty->setValue($instance, array($grandChildWidgetJSPath));

        $testAddDependentWidgetRelationshipsMethod->invoke($instance, $grandChildWidgetJSPath, $grandChildWidgetPathInfo->meta['extends']['widget']);

        $widgetDependencyRelationships = $class->getProperty('widgetDependencyRelationships');
        $widgetDependencyRelationships->setAccessible(true);

        $result = $widgetDependencyRelationships->getValue($instance);

        $firstDep = each($result);
        $this->assertTrue(Text::endsWith($firstDep['key'], '/custom/extended/ChildWidget/1.0/logic.js'));
        $this->assertTrue(Text::endsWith($firstDep['value'], '/custom/extended/ParentWidget/1.0/logic.js'));

        $secondDep = each($result);
        $this->assertTrue(Text::endsWith($secondDep['key'], '/custom/extended/ParentWidget/1.0/logic.js'));
        $this->assertTrue(Text::endsWith($secondDep['value'], '/custom/extended/GrandParentWidget/1.0/logic.js'));

        // 'Production Mode' params
        list($class, $instance) = $getClassAndInstance();
        $testAddDependentWidgetRelationshipsMethod = $class->getMethod('addDependentWidgetRelationships');

        // Actual value of array below would be something like '/bulk/httpd/cgi-bin/[sitename].cfg/scripts/cp/customer/development/widgets/custom/extended/ParentWidget/1.0/logic.js',
        // but its truncated here as to avoid hard-coded enviroment strings which would result in errors in testing.
        $widgetProperty = $class->getProperty('widget');
        $widgetProperty->setAccessible(true);
        $widgetProperty->setValue($instance, array('/site/potato/widgets/custom/extended/ParentWidget/1.0/logic.js'));

        $grandChildWidgetPathInfo = \RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo('custom/extended/ChildWidget');
        $testAddDependentWidgetRelationshipsMethod->invoke($instance, $grandChildWidgetPathInfo->logic, $grandChildWidgetPathInfo->meta['extends']['widget'], true);

        $widgetDependencyRelationships = $class->getProperty('widgetDependencyRelationships');
        $widgetDependencyRelationships->setAccessible(true);

        $result = $widgetDependencyRelationships->getValue($instance);

        $firstDep = each($result);
        $this->assertTrue(Text::endsWith($firstDep['key'], '/custom/extended/ChildWidget/1.0/logic.js'));
        $this->assertTrue(Text::endsWith($firstDep['value'], '/custom/extended/ParentWidget/1.0/logic.js'));

        $secondDep = each($result);
        $this->assertTrue(Text::endsWith($secondDep['key'], '/custom/extended/ParentWidget/1.0/logic.js'));
        $this->assertTrue(Text::endsWith($secondDep['value'], '/custom/extended/GrandParentWidget/1.0/logic.js'));
    }

    function testPreProcessWidgetJavaScript() {
        $trimWidgetFiles = function ($widgetFiles) {
            foreach($widgetFiles as &$widgetFile) {
                $widgetFile = Text::getSubstringBefore(Text::getSubstringAfter($widgetFile, 'widgets/'), '/logic.js');
            }
            return $widgetFiles;
        };

        $trimFrameworkFiles = function ($frameworkFiles) {
            foreach($frameworkFiles as &$frameworkFile) {
                $frameworkFile = Text::getSubstringAfter($frameworkFile, 'debug-js/');
                if(Text::stringContains($frameworkFile, 'modules/'))
                    $frameworkFile = Text::getSubstringAfter($frameworkFile, 'modules/');
            }
            return $frameworkFiles;
        };

        $widgets = array(
            'standard/searchsource/SourceSearchField',
            'standard/searchsource/SourceSearchButton',
            'standard/searchsource/SourceProductCategorySearchFilter',
            'standard/searchsource/DisplaySearchSourceFilters',
            'standard/searchsource/SourceFilter',
            'standard/searchsource/SourceSort',
            'standard/searchsource/SourceResultDetails',
            'standard/searchsource/SourcePagination',
            'standard/knowledgebase/RssIcon',
            'standard/search/BrowserSearchPlugin',
            'standard/utils/ClickjackPrevention',
            'standard/utils/CapabilityDetector',
            'standard/navigation/NavigationTab',
            'standard/login/AccountDropdown',
            'standard/search/SimpleSearch',
            'standard/feedback/SiteFeedback',
            'standard/utils/OracleLogo',
            'standard/login/LogoutLink',
            'standard/user/UserInfoDialog',
            'standard/login/LoginDialog',
            'standard/login/OpenLogin',
            'standard/input/FormInput',
            'standard/input/FormSubmit',
            'standard/input/SelectionInput',
            'standard/input/DateInput',
            'standard/input/PasswordInput',
            'standard/input/TextInput',
            'standard/output/FieldDisplay'
        );

        $widgetCallsArg = array();
        foreach($widgets as $widget) {
            $widgetCallsArg[$widget] = array(
                'meta' => \RightNow\Internal\Utils\Widgets::getWidgetInfo(Registry::getWidgetPathInfo($widget))
            );
        }

        $pageMetaArg = array(
            'javascript_module' => 'standard',
            'title'             => ' . \RightNow\Utils\Config::msgGetFrom(FIND_ANS_HDG) . ',
            'template'          => 'standard.php',
            'clickstream'       => 'answer_list'
        );

        $class = new \ReflectionClass('\RightNow\Internal\Libraries\ClientLoader');

        $method = $class->getMethod('preProcessWidgetJavaScript');
        $method->setAccessible(true);

        $statics = $class->getProperty('widgetStatics');
        $statics->setAccessible(true);

        // Module type of none
        $instance = $class->newInstanceArgs(array(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions));
        $instance->javaScriptFiles->moduleType = ClientLoader::MODULE_NONE;
        $method->invoke($instance, $widgetCallsArg, $pageMetaArg);

        $this->assertSame($instance->javaScriptFiles->widget, array());

        $expectedFrameworkFiles = array(
            'RightNow.js',
            'RightNow.UI.js',
            'RightNow.Ajax.js',
            'RightNow.Url.js',
            'RightNow.Text.js',
            'RightNow.UI.AbuseDetection.js',
            'RightNow.Event.js',
        );
        $this->assertSame($trimFrameworkFiles($instance->javaScriptFiles->framework), $expectedFrameworkFiles);

        $widgetStatics = $statics->getValue($instance);
        $this->assertSame($widgetStatics, array());

        // Default use
        $instance = $class->newInstanceArgs(array(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions));
        $method->invoke($instance, $widgetCallsArg, $pageMetaArg);

        // Collection of widgets which may represent a typical page
        $expectedWidgets = array(
            'standard/searchsource/SourceSearchField',
            'standard/searchsource/SourceSearchButton',
            'standard/searchsource/SourceProductCategorySearchFilter',
            'standard/searchsource/DisplaySearchSourceFilters',
            'standard/searchsource/SourceFilter',
            'standard/searchsource/SourceSort',
            'standard/searchsource/SourceResultDetails',
            'standard/searchsource/SourcePagination',
            'standard/navigation/NavigationTab',
            'standard/login/AccountDropdown',
            'standard/search/SimpleSearch',
            'standard/feedback/SiteFeedback',
            'standard/login/LogoutLink',
            'standard/user/UserInfoDialog',
            'standard/login/LoginDialog',
            'standard/login/OpenLogin',
            'standard/input/FormSubmit',
            'standard/input/SelectionInput',
            'standard/input/DateInput',
            'standard/input/PasswordInput',
            'standard/input/TextInput'
        );
        $this->assertSame($trimWidgetFiles($instance->javaScriptFiles->widget), $expectedWidgets);

        $expectedFrameworkFiles = array(
            'RightNow.js',
            'RightNow.UI.js',
            'RightNow.Ajax.js',
            'RightNow.Url.js',
            'RightNow.Text.js',
            'RightNow.UI.AbuseDetection.js',
            'RightNow.Event.js',
            'widgetHelpers/EventProvider.js',
            'widgetHelpers/SourceSearchFilter.js',
            'widgetHelpers/ProductCategory.js',
            'widgetHelpers/RequiredLabel.js',
            'widgetHelpers/Form.js',
            'widgetHelpers/Field.js'
        );
        $this->assertSame($trimFrameworkFiles($instance->javaScriptFiles->framework), $expectedFrameworkFiles);

        $widgetStatics = $statics->getValue($instance);

        $this->assertSame(array_keys($widgetStatics), array(
            'standard/searchsource/SourceProductCategorySearchFilter_RightNow.Widgets.SourceProductCategorySearchFilter',
            'standard/searchsource/SourcePagination_RightNow.Widgets.SourcePagination',
            'standard/login/LoginDialog_RightNow.Widgets.LoginDialog',
            'standard/login/OpenLogin_RightNow.Widgets.OpenLogin',
            'standard/input/FormSubmit_RightNow.Widgets.FormSubmit',
            'standard/input/SelectionInput_RightNow.Widgets.SelectionInput',
            'standard/input/DateInput_RightNow.Widgets.DateInput',
            'standard/input/PasswordInput_RightNow.Widgets.PasswordInput',
            'standard/input/TextInput_RightNow.Widgets.TextInput'
        ));

        foreach($widgetStatics as $widgetStatic => $widgetStaticInfo) {
            $this->assertTrue(strlen($widgetStaticInfo['class']) > 0);
            $this->assertTrue(Text::endsWith($widgetStatic, $widgetStaticInfo['class']));
            $this->assertTrue(array_key_exists('templates', $widgetStaticInfo['static']) || array_key_exists('requires', $widgetStaticInfo['static']));
        }

        // Only js_path is included in meta
        $instance = $class->newInstanceArgs(array(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions));
        $method->invoke($instance, array(
            array(
                'meta' => array(
                    'js_path' => 'standard/searchsource/SourceResultListing'
                )
            )
        ), $pageMetaArg);

        $widgetFile = Text::getSubstringBefore(Text::getSubstringAfter($instance->javaScriptFiles->widget[0], 'widgets/'), '/logic.js');
        $this->assertIdentical('standard/searchsource/SourceResultListing', $widgetFile);

        $expectedFrameworkFiles = array(
            'RightNow.js',
            'RightNow.UI.js',
            'RightNow.Ajax.js',
            'RightNow.Url.js',
            'RightNow.Text.js',
            'RightNow.UI.AbuseDetection.js',
            'RightNow.Event.js',
            'widgetHelpers/EventProvider.js',
            'widgetHelpers/SourceSearchFilter.js'
        );
        $this->assertSame($trimFrameworkFiles($instance->javaScriptFiles->framework), $expectedFrameworkFiles);

        $widgetStatics = $statics->getValue($instance);
        $this->assertSame($widgetStatics, array());
    }
}
