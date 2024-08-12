<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Internal\Utils\Admin,
    RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

class AdminUtilsTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Utils\Admin';
    function testProcessAssetDirectives() {
        $dir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));
        umask(0);
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$dir}/nope.md", '');

        // No JS, CSS
        $return = Admin::processAssetDirectives(array("{$dir}/nope.md"));
        $this->assertIdentical(array('all' => array(), 'files' => array()), $return);

        // No directives
        $files = array(
             "{$dir}/nope.css" => '',
             "{$dir}/nope.js" => 1,
        );
        $this->writeContent($files);
        $return = Admin::processAssetDirectives(array_keys($files));
        $this->assertIdentical(array('all' => array(), 'files' => array()), $return);

        // Directives
        $files = array(
             "{$dir}/yeah.js" => "//=require banana.js\n// = require admin/js/versions/widgets.js\n//= require yui slider-base/slider-base.js\nYUI().use",
             "{$dir}/yeah.css" => "/*=require banana.css\n * = require admin/css/versions/widgets.css \n*= require yui overlay/assets/overlay-core.css\n*/\n.nonono{\nmargin:10px;}",
        );
        $this->writeContent($files);
        $return = Admin::processAssetDirectives(array_keys($files));
        $this->assertSame(6, count($return['all']));
        $this->assertIdentical(array(
            "{$dir}/yeah.js" => array('/euf/core/banana.js', '/euf/core/admin/js/versions/widgets.js', \RightNow\Utils\Url::getYUICodePath('slider-base/slider-base.js')),
            "{$dir}/yeah.css" => array('/euf/core/banana.css', '/euf/core/admin/css/versions/widgets.css', \RightNow\Utils\Url::getYUICodePath('overlay/assets/overlay-core.css')),
        ), $return['files']);

        // Incorrect directives
        $files = array(
             "{$dir}/yeah.css" => "//=require banana.js\n// = require admin/js/versions/widgets.js\n//= require yui slider-base/slider-base.js\nYUI().use",
             "{$dir}/yeah.js" => "/*=require banana.css\n * = require admin/css/versions/widgets.css \n*= require yui overlay/assets/overlay-core.css\n*/\n.nonono{\nmargin:10px;}",
        );
        $this->writeContent($files);
        $return = Admin::processAssetDirectives(array_keys($files));
        $this->assertIdentical(array('all' => array(), 'files' => array()), $return);

        FileSystem::removeDirectory($dir, true);
    }

    function testGetAllWidgetDirectories() {
        $return = Admin::getAllWidgetDirectories();
        $this->assertSame('custom', $return[0]);
        $this->assertTrue(in_array('standard', $return));
        $this->assertTrue(in_array('standard/chat', $return));
        $this->assertTrue(in_array('standard/feedback', $return));
        $this->assertTrue(in_array('standard/reports', $return));
        $this->assertTrue(array_search('standard/feedback/AnswerFeedback', $return) < array_search('standard/feedback/SiteFeedback', $return));
        $this->assertTrue(array_search('standard/feedback', $return) < array_search('standard/reports', $return));
        $this->assertTrue(Text::beginsWith(end($return), 'standard/utils/'));
    }

    function testgetLanguageInterfaceMap() {
        $oneLang = Admin::getLanguageInterfaceMap();
        $this->assertIsA($oneLang, 'array');
        $this->assertEqual(1, count($oneLang));
        $interface = \RightNow\Internal\Api::intf_name();
        $values = reset($oneLang);
        $this->assertEqual($interface, $values[1]);

        $allLangs = Admin::getLanguageInterfaceMap(true);
        $this->assertIsA($allLangs, 'array');
        $this->assertTrue(count($allLangs) > 1);
        $values = reset($allLangs);
        $this->assertEqual($interface, $values[1]);
    }

    function testGetLanguageLabels() {
        $method = $this->getMethod('getLanguageLabels', true);
        $langs = $method();
        $this->assertIsA($langs, 'array');
        $this->assertTrue(count($langs) > 1);
        $this->assertEqual($langs['en_US'], ENGLISH_EN_LBL);
    }

    function testGetUniqueCategories() {
        $widgets = array(
            array(
                'category' => array('Indian Food', 'Mexican Food')
            ),
            array(
                'category' => array('Indian Food', 'Italian Food')
            )
        );

        $method = $this->getMethod('getUniqueCategories');
        $result = $method($widgets);

        $this->assertIdentical(array(
            'Indian Food'  => 'category-95ba0545632d9d70aa8aaaf20e2f8389',
            'Italian Food' => 'category-8ffe64b2eee5f5d89c620dc900387b58',
            'Mexican Food' => 'category-5a37869e897a658e97843066fb27cfa1'
        ), $result);
    }

    function writeContent(array $files) {
        foreach ($files as $name => $content) {
            FileSystem::filePutContentsOrThrowExceptionOnFailure($name, $content);
        }
    }
}
