<?php
use RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

require_once CORE_FILES . 'util/tarball/AdminAssets.php';

class AdminAssetsTest extends CPTestCase {
    function setUp() {
        $this->dir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));
        umask(0);
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/temp.md", '');
        parent::setUp();
    }

    function tearDown() {
        FileSystem::removeDirectory($this->dir, true);
        parent::tearDown();
    }

    function testNoOp() {
        $this->assertIdentical(0, AdminAssets::optimize(false, $this->dir));
    }

    function testAssetsNoDirectives() {
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/banana.js", "//blah\nfunction yeah(){}");
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/banana.css", "/* blah */\n.help { margin:   10px; }");

        $this->assertIdentical(2, AdminAssets::optimize(false, $this->dir));
        $this->assertIdentical("function yeah(){}", trim(file_get_contents("{$this->dir}/banana.js")));
        $this->assertIdentical(".help{margin: 10px;}", trim(file_get_contents("{$this->dir}/banana.css")));
    }

    function testAssetsYuiDirectives() {
        // A legit YUI file and a YUI file that doesn't exist.
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/banana.js", "//=require yui slider-base/slider-base.js\n// = require yui banana/banana-base.js\nfunction yeah(){}");
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/banana.css", "/*=require yui overlay/assets/overlay-core.css\n * = require yui banana/banana-core.css \n*/\n.help { margin:   10px; }");

        $this->assertIdentical(2, AdminAssets::optimize(false, $this->dir));

        $js = file_get_contents("{$this->dir}/banana.js");
        $this->assertTrue(Text::stringContains($js, "YUI.add('slider-base'"));
        $this->assertTrue(Text::stringContains($js, "function yeah(){}"));

        $css = file_get_contents("{$this->dir}/banana.css");
        $this->assertTrue(Text::stringContains($css, ".yui3-overlay"));
        $this->assertTrue(Text::stringContains($css, ".help{margin: 10px;}"));
    }

    function testAssetsCssUrlRewriter() {
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/banana.css", "/*=require yui datatable-sort/assets/skins/sam/datatable-sort.css\n*/\n.help { margin:   10px; }");

        AdminAssets::optimize(false, $this->dir);
        $css = file_get_contents("{$this->dir}/banana.css");

        // Make sure the paths were re-written
        $this->assertTrue(Text::stringContains($css, "url(/rnt/rnw/"));
        $this->assertFalse(Text::stringContains($css, "../../../../assets/skins/sam/sprite.png"));
    }

    function testGenericAssets() {
        // A legit file and a file that doesn't exist.
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/banana.js", "//=require banana.js\n// = require admin/js/versions/manage.js\nfunction yeah(){}");
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/banana.css", "/*=require banana.css\n * = require admin/css/versions/manage.css \n*/\n.help { margin:   10px; }");

        $this->assertIdentical(2, AdminAssets::optimize(false, $this->dir));

        $js = file_get_contents("{$this->dir}/banana.js");
        $this->assertTrue(Text::stringContains($js, "YUI()"));
        $this->assertTrue(Text::stringContains($js, "function yeah(){}"));

        $css = file_get_contents("{$this->dir}/banana.css");
        $this->assertTrue(Text::stringContains($css, "#content"));
        $this->assertTrue(Text::stringContains($css, ".help{margin: 10px;}"));
    }

    function testBoth() {
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/banana.js", "//=require yui slider-base/slider-base.js\n// = require admin/js/versions/manage.js\nfunction yeah(){}");
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->dir}/banana.css", "/*=require yui overlay/assets/overlay-core.css\n * = require admin/css/versions/manage.css \n*/\n.help { margin:   10px; }");

        $this->assertIdentical(2, AdminAssets::optimize(false, $this->dir));

        $js = file_get_contents("{$this->dir}/banana.js");
        $this->assertTrue(Text::stringContains($js, "YUI.add('slider-base'"));
        $this->assertTrue(Text::stringContains($js, "YUI()"));
        $this->assertTrue(Text::stringContains($js, "function yeah(){}"));

        $css = file_get_contents("{$this->dir}/banana.css");
        $this->assertTrue(Text::stringContains($css, ".yui3-overlay"));
        $this->assertTrue(Text::stringContains($css, "#content"));
        $this->assertTrue(Text::stringContains($css, ".help{margin: 10px;}"));
    }
}
