<?php

use RightNow\Internal\Libraries;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
class ThemeParserTest extends CPTestCase {
    function testStaticMethods() {
        $this->assertIdentical(true, call_user_func('ThemeParserTest::returnTrue'));
        $this->assertIdentical(false, call_user_func('ThemeParserTest::returnFalse'));
        $this->assertIdentical(true, call_user_func('ThemeParserTest::doesNotContainsXyz', 'asdf'));
        $this->assertIdentical(false, call_user_func('ThemeParserTest::doesNotContainsXyz', 'asxyzdf'));
    }

    function testParse1() {
        $contentExpected = $content = 'asdlfjasdlfkuqwoeruqwoeruqowe';
        $themes = Libraries\ThemeParser::parse($content);
        $this->assertIdentical($content, $contentExpected);
        $this->assertTrue(is_array($themes));
        $this->assertIdentical(count($themes), 0);
    }

    function testParse2() {
        $contentExpected = $content = 'asdl<rn:theme path="/euf/assets/foo"/>fjasdlfkuqw<rn:theme path="/euf/assets/bar" css="my.css"/>oeruqwoeruqowe';
        $themes = Libraries\ThemeParser::parse($content);
        $this->assertIdentical($content, $contentExpected);
        $this->assertTrue(is_array($themes));
        $this->assertIdentical(count($themes), 2);
        $this->assertIdentical('/euf/assets/foo', $themes[0]->getParsedPath());
        $this->assertIdentical(false, $themes[0]->getParsedCss());
        $this->assertIdentical('/euf/assets/bar', $themes[1]->getParsedPath());
        $this->assertIdentical('my.css', $themes[1]->getParsedCss());
    }

    function testParse3() {
        $contentExpected = $content = '<!-- <rn:theme path="/euf/assets/foo"/> -->asdlfjasdlfk<!-- <rn:theme path="/euf/assets/foo"/> -->uqwoeruqwoeruqowe<!-- <rn:theme path="/euf/assets/foo"/> -->';
        $themes = Libraries\ThemeParser::parse($content);
        $this->assertIdentical($content, $contentExpected);
        $this->assertTrue(is_array($themes));
        $this->assertIdentical(count($themes), 0);
    }

    function testParse4() {
        $contentExpected = $content = '<!-- <rn:theme path="/euf/assets/foo"/> -->asdl<rn:theme path="/euf/assets/foo"/>fjasdlfkuqw<rn:theme path="/euf/assets/bar" css="my.css"/>oeruqwoeruqowe<!-- <rn:theme path="/euf/assets/foo"/> -->';
        $themes = Libraries\ThemeParser::parse($content);
        $this->assertIdentical($content, $contentExpected);
        $this->assertTrue(is_array($themes));
        $this->assertIdentical(count($themes), 2);
        $this->assertIdentical('/euf/assets/foo', $themes[0]->getParsedPath());
        $this->assertIdentical(false, $themes[0]->getParsedCss());
        $this->assertIdentical('/euf/assets/bar', $themes[1]->getParsedPath());
        $this->assertIdentical('my.css', $themes[1]->getParsedCss());
    }

    function testGetTagName() {
        $content = '<rn:theme path="/euf/assets/newtheme" css="site.css, {YUI}/widget-stack/assets/skins/sam/widget-stack.css, {YUI}/widget-modality/assets/skins/sam/widget-modality.css, {YUI}/overlay/assets/overlay-core.css, {YUI}/panel/assets/skins/sam/panel.css" />';

        $themes = Libraries\ThemeParser::parse($content);
        $this->assertIdentical('rn:theme', $themes[0]->getTagName());
    }

    function testThemeParseAndValidate() {
        $contentExpected = $content = 'asdlfjasdlfkuqwoeruqwoeruqowe';
        $themes = Libraries\ThemeParser::parseAndValidate($content, '');
        $this->assertIdentical($content, $contentExpected);
        $this->assertTrue(is_array($themes));
        $this->assertIdentical(count($themes), 0);
    }

    function testConvertListOfThemesToRuntimeInformation() {
        $templateThemes = array('/euf/assets/themes/standard' => Libraries\Theme::__set_state(array(
            'parsedTag' => '<rn:theme path="/euf/assets/themes/standard" css="site.css"/>',
            'parsedPath' => '/euf/assets/themes/standard',
            'resolvedPath' => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard',
            'parsedCss' => 'site.css',
            'resolvedCssPaths' =>
            array (
                0 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard/site.css',
            ),
        )));
        $pageThemes = array();
        $runtime = Libraries\ThemeParser::convertListOfThemesToRuntimeInformation($pageThemes, $templateThemes);
        $this->assertTrue(is_array($runtime));
        $this->assertIdentical(count($runtime), 3);
        $this->assertIdentical($runtime[0], $templateThemes['/euf/assets/themes/standard']->getParsedPath());
        $this->assertIdentical($runtime[1], $templateThemes['/euf/assets/themes/standard']->getParsedPath());
    }

    function testYUIPlaceholder() {
        $theme = new \RightNow\Internal\Libraries\Theme('<rn:theme path="/euf/assets/themes/standard" css="site.css, {YUI}/overlay/assets/overlay-core.css, /rnt/rnw/yui_3.13/panel/assets/skins/sam/panel.css" />', '/euf/assets/themes/standard', 'site.css, {YUI}/overlay/assets/overlay-core.css, /rnt/rnw/yui_3.13/panel/assets/skins/sam/panel.css');
        $theme->validate(new \RightNow\Internal\Libraries\NormalThemeResolver());
        $resolvedCssPaths = $theme->getResolvedCssPaths();
        $this->assertTrue(is_array($resolvedCssPaths));
        $this->assertIdentical(count($resolvedCssPaths), 3);
        $this->assertIdentical($resolvedCssPaths[0], HTMLROOT . '/euf/assets/themes/standard/site.css');
        $this->assertIdentical($resolvedCssPaths[1], HTMLROOT . YUI_SOURCE_DIR . 'overlay/assets/overlay-core.css');
        $this->assertIdentical($resolvedCssPaths[2], HTMLROOT . '/rnt/rnw/yui_3.13/panel/assets/skins/sam/panel.css');
    }

    public function testCoreAssetPlaceholder() {
        $theme = new \RightNow\Internal\Libraries\Theme('<rn:theme path="/euf/assets/themes/standard" css="site.css,{CORE_ASSETS}/thirdParty/css/font-awesome.min.css"/>', '/euf/assets/themes/standard', 'site.css, {CORE_ASSETS}/thirdParty/css/font-awesome.min.css');
        $theme->validate(new \RightNow\Internal\Libraries\NormalThemeResolver());
        $resolvedCssPaths = $theme->getResolvedCssPaths();
        $this->assertTrue(is_array($resolvedCssPaths));
        $this->assertIdentical(count($resolvedCssPaths), 2);
        $this->assertIdentical($resolvedCssPaths[0], HTMLROOT . '/euf/assets/themes/standard/site.css');
        $this->assertIdentical($resolvedCssPaths[1], HTMLROOT . '/euf/core/thirdParty/css/font-awesome.min.css');
    }

    public function testBadCss() {
        $theme = new \RightNow\Internal\Libraries\Theme('<rn:theme path="/euf/assets/themes/standard" css="{YUI }/overlay/assets/overlay-core.css" />', '/euf/assets/themes/standard', '{YUI }/overlay/assets/overlay-core.css');
        try {
            $theme->validate(new \RightNow\Internal\Libraries\NormalThemeResolver());
            $this->fail();
        }
        catch (\RightNow\Internal\Libraries\ThemeException $e) {
            $message = $e->getMessage();
            $this->assertStringContains($message, "'rn:theme' tag");
            $this->assertStringContains($message, '{YUI }/overlay/assets/overlay-core.css');
        }

        $theme = new \RightNow\Internal\Libraries\Theme('<rn:theme path="/euf/assets/themes/standard" css="overlay-core.css" />', '/euf/assets/themes/standard', 'overlay-core.css');
        try {
            $theme->validate(new \RightNow\Internal\Libraries\NormalThemeResolver());
            $this->fail();
        }
        catch (\RightNow\Internal\Libraries\ThemeException $e) {
            $message = $e->getMessage();
            $this->assertStringContains($message, "'rn:theme' tag");
            $this->assertStringContains($message, 'overlay-core.css');
        }

        // @@@ QA 141006-000095 Do not allow use of two periods (parent directory) in CSS file path
        $theme = new \RightNow\Internal\Libraries\Theme('<rn:theme path="/euf/assets/themes/standard" css="../standard/site.css" />', '/euf/assets/themes/standard', '../standard/site.css');
        try {
            $theme->validate(new \RightNow\Internal\Libraries\NormalThemeResolver());
            $this->fail();
        }
        catch (\RightNow\Internal\Libraries\ThemeException $e) {
            $this->assertIdentical($e->getMessage(), "In %s, the 'css' attribute of the <rn:theme path=\"/euf/assets/themes/standard\" css=\"../standard/site.css\" /> tag specifies a CSS file, ../standard/site.css, which contains two periods in the directory structure which are not allowed. Theme CSS files must be specified relative to the theme path or beginning with '/'.");
        }
    }

    function testTranslateThemesToReferenceEquivalent() {
        $themes = array(
            '/euf/assets/themes/standard' => Libraries\Theme::__set_state(array(
                'parsedTag' => '<rn:theme path="/euf/assets/themes/standard" css="site.css"/>',
                'parsedPath' => '/euf/assets/themes/standard',
                'resolvedPath' => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard',
                'parsedCss' => 'site.css',
                'resolvedCssPaths' =>
                array (
                    0 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard/site.css',
                ),
            )),
            '/euf/assets/themes/mobile' => Libraries\Theme::__set_state(array(
                'parsedTag' => '<rn:theme path="/euf/assets/themes/mobile" css="site.css"/>',
                'parsedPath' => '/euf/assets/themes/mobile',
                'resolvedPath' => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/mobile',
                'parsedCss' => 'site.css',
                'resolvedCssPaths' =>
                array (
                    0 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/mobile/site.css',
                ),
            )),
        );

        $yuiPath = HTMLROOT . \RightNow\Utils\Url::getYUICodePath();
        $absoluteThemePath = '/bulk/httpd/html/per_site_html_root/' . \RightNow\Api::intf_name() . '/euf/assets/themes/';

        $translatedThemes = Libraries\ThemeParser::translateThemesToReferenceEquivalent($themes, 'path/to/nowhere');

        $this->assertIsA($translatedThemes['/euf/assets/themes/standard'], 'RightNow\Internal\Libraries\Theme');
        $this->assertIdentical(array_keys($translatedThemes), array('/euf/assets/themes/standard', '/euf/assets/themes/mobile'));
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getParsedTag(), null);
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getParsedPath(), '/euf/assets/themes/standard');
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getResolvedPath(), "{$absoluteThemePath}standard");
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getParsedCss(), 'site.css');
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getResolvedCssPaths(), array("{$absoluteThemePath}standard/site.css"));

        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getParsedTag(), null);
        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getParsedPath(), '/euf/assets/themes/mobile');
        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getResolvedPath(), "{$absoluteThemePath}mobile");
        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getParsedCss(), 'site.css');
        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getResolvedCssPaths(), array("{$absoluteThemePath}mobile/site.css"));

        $themes = array(
            '/euf/assets/themes/badForm' => Libraries\Theme::__set_state(array(
                'parsedTag' => '<rn:theme path="/euf/assets/themes/standard" css="site.css"/>',
                'parsedPath' => '/euf/assets/themes/standard',
                'resolvedPath' => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard',
                'parsedCss' => 'site.css',
                'resolvedCssPaths' =>
                array (
                    0 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard/site.css',
                ),
            )),
        );

        try
        {
            $translatedThemes = Libraries\ThemeParser::translateThemesToReferenceEquivalent($themes, 'path/to/nowhere');
            $this->assertTrue(false, 'Expected Exception not thrown');
        }
        catch (Exception $e)
        {
            $this->assertTrue(true, 'Expected Exception caught');
        }
    }

    // #translateThemesToReferenceEquivalent contains hard-coded theme CSS that must match what's in our checked-in stock themes otherwise this test fails.
    function testHardCodedReferenceCssMatchesStockTemplates() {
        $templatePath = CUSTOMER_FILES . 'views/templates';

        // Resolve the live site standard and mobile themes normally
        $standardTemplate = file_get_contents("{$templatePath}/standard.php");
        $this->assertIsA($standardTemplate, 'string');
        $themes = Libraries\ThemeParser::parseAndValidate($standardTemplate, '', new Libraries\NormalThemeResolver());

        $mobileTemplate = file_get_contents("{$templatePath}/mobile.php");
        $this->assertIsA($mobileTemplate, 'string');
        $themes = array_merge($themes, Libraries\ThemeParser::parseAndValidate($mobileTemplate, '', new Libraries\NormalThemeResolver()));

        // Get the hard-coded stuff
        $hardCodedReferenceThemes = Libraries\ThemeParser::translateThemesToReferenceEquivalent(array(
            '/euf/assets/themes/mobile' => new Libraries\Theme('tag', '/euf/assets/themes/mobile', ''),
            '/euf/assets/themes/standard' => new Libraries\Theme('tag', '/euf/assets/themes/standard', ''),
        ), '');

        // Make sure they match
        foreach ($hardCodedReferenceThemes as $key => $theme) {
            $this->assertIdentical($theme->getResolvedCssPaths(), $themes[$key]->getResolvedCssPaths(), "The reference mode theme CSS doesn't match dev mode theme CSS. Make sure they match (#translateThemesToReferenceEquivalent)");
        }
    }

    function testRemoveInnerTraversals() {
        $values = array(
            array('/one/two/three/four.php', '/one/two/three/four.php'),
            array('one/two/three/four.php', 'one/two/three/four.php'),
            array('/one/two/three/../four.php', '/one/two/four.php'),
            array('/one/two/three/../../four.php', '/one/four.php'),
            array('one/two/three/../../four.php', 'one/four.php'),
            array('/one/two/three/../../../four.php', '/four.php'),
            array('/one/../../two/three/../../../four.php', '/four.php'),
            array('../one/two/three/four.php', '../one/two/three/four.php'),
            array('../../one/two/three/four.php', '../../one/two/three/four.php'),
            array('../../../one/two/three/four.php', '../../../one/two/three/four.php'),
            array('../../../one/two/three/../../four.php', '../../../one/four.php'),
        );

        $invoke = RightNow\UnitTest\Helper::getMethodInvoker('\RightNow\Internal\Libraries\Minify_CSS_UriRewriter', 'removeInnerTraversals');
        foreach ($values as $pairs) {
            list($input, $expected) = $pairs;
            $actual = $invoke($input);
            $this->assertEqual($expected, $actual, "input:'$input'  expected:'$expected'  actual:'$actual'");
        }


    }
    
    function testTranslateThemesToKAReferenceEquivalent() {
        $themes = array(
            '/euf/assets/themes/standard' => Libraries\Theme::__set_state(array(
                'parsedTag' => '<rn:theme path="/euf/assets/themes/standard" css="site.css"/>',
                'parsedPath' => '/euf/assets/themes/standard',
                'resolvedPath' => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard',
                'parsedCss' => 'site.css',
                'resolvedCssPaths' =>
                array (
                    0 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard/site.css',
                    1 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard/okcs.css'
                ),
            )),
            '/euf/assets/themes/mobile' => Libraries\Theme::__set_state(array(
                'parsedTag' => '<rn:theme path="/euf/assets/themes/mobile" css="site.css"/>',
                'parsedPath' => '/euf/assets/themes/mobile',
                'resolvedPath' => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/mobile',
                'parsedCss' => 'site.css',
                'resolvedCssPaths' =>
                array (
                    0 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/mobile/site.css',
                    1 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/mobile/okcs.css',
                ),
            )),
        );

        $yuiPath = HTMLROOT . \RightNow\Utils\Url::getYUICodePath();
        $absoluteThemePath = '/bulk/httpd/html/per_site_html_root/' . \RightNow\Api::intf_name() . '/euf/assets/themes/';

        $translatedThemes = Libraries\ThemeParser::translateThemesToKAReferenceEquivalent($themes, 'path/to/nowhere');

        $this->assertIsA($translatedThemes['/euf/assets/themes/standard'], 'RightNow\Internal\Libraries\Theme');
        $this->assertIdentical(array_keys($translatedThemes), array('/euf/assets/themes/standard', '/euf/assets/themes/mobile'));
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getParsedTag(), null);
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getParsedPath(), '/euf/assets/themes/standard');
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getResolvedPath(), "{$absoluteThemePath}standard");
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getParsedCss(), 'site.css');
        $this->assertIdentical($translatedThemes['/euf/assets/themes/standard']->getResolvedCssPaths(), array("{$absoluteThemePath}standard/site.css","{$absoluteThemePath}standard/okcs.css", "{$absoluteThemePath}standard/okcs_search.css", "{$absoluteThemePath}standard/intent.css"));

        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getParsedTag(), null);
        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getParsedPath(), '/euf/assets/themes/mobile');
        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getResolvedPath(), "{$absoluteThemePath}mobile");
        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getParsedCss(), 'site.css');
        $this->assertIdentical($translatedThemes['/euf/assets/themes/mobile']->getResolvedCssPaths(), array("{$absoluteThemePath}mobile/site.css","{$absoluteThemePath}mobile/okcs.css","{$absoluteThemePath}mobile/intent.css"));

        $themes = array(
            '/euf/assets/themes/badForm' => Libraries\Theme::__set_state(array(
                'parsedTag' => '<rn:theme path="/euf/assets/themes/standard" css="site.css"/>',
                'parsedPath' => '/euf/assets/themes/standard',
                'resolvedPath' => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard',
                'parsedCss' => 'site.css',
                'resolvedCssPaths' =>
                array (
                    0 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard/site.css',
                    1 => '/nfs/users/ma/lwickland/src/cp2/rnw/scripts/euf/webfiles/assets/themes/standard/okcs.css',
                ),
            )),
        );

        try
        {
            $translatedThemes = Libraries\ThemeParser::translateThemesToReferenceEquivalent($themes, 'path/to/nowhere');
            $this->assertTrue(false, 'Expected Exception not thrown');
        }
        catch (Exception $e)
        {
            $this->assertTrue(true, 'Expected Exception caught');
        }
    }

    public static function returnTrue() {
        return true;
    }

    public static function returnFalse() {
        return false;
    }

    public static function doesNotContainsXyz($s) {
        return !\RightNow\Utils\Text::stringContains($s, 'xyz');
    }
}
