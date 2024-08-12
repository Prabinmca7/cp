<?php

use RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\ViewPartials\SharedPartial;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OverriddenSharedViewPartialTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Widget\ViewPartials\SharedPartial';

    function __construct () {
        parent::__construct();

        $this->fakeView = 'LIES';
        $this->extensionPath = APPPATH . 'config/extensions.yml';
        $this->viewPath = APPPATH . 'views/Partials/';
        $this->fakeExtension = array(
            'viewPartialExtensions' => array(
                'Partials.Forms.RequiredLabel',
            ),
        );
        umask(0);
    }

    function setUp () {
        // Stash off the contents of extensions.yml, write out the contents for the test,
        // and create the customer Partials dir.
        $this->originalExtensionList = file_get_contents($this->extensionPath);
        FileSystem::filePutContentsOrThrowExceptionOnFailure($this->extensionPath, yaml_emit($this->fakeExtension));
        FileSystem::mkdirOrThrowExceptionOnFailure($this->viewPath);
    }

    function tearDown () {
        // Undo the fakery that #setUp wrote.
        FileSystem::filePutContentsOrThrowExceptionOnFailure($this->extensionPath, $this->originalExtensionList);
        FileSystem::removeDirectory($this->viewPath . "Forms/", true);
    }

    function testOverriddenViewPartial () {
        FileSystem::mkdirOrThrowExceptionOnFailure($this->viewPath . 'Forms');
        FileSystem::filePutContentsOrThrowExceptionOnFailure($this->viewPath . 'Forms/RequiredLabel.html.php', $this->fakeView);

        // Invalidate view's static cache var so that it goes back
        // to the FS to get the test values.
        RightNow\Internal\Libraries\Widget\ExtensionLoader::$extensionRegistry = null;
        $codeExtensions = new \ReflectionProperty('RightNow\Internal\Utils\Framework', 'codeExtensions');
        $codeExtensions->setAccessible(true);
        $codeExtensions->setValue(null);

        $partial = new SharedPartial($this->fakeExtension['viewPartialExtensions'][0], 'holla');
        $content = $partial->getContents();

        $this->assertIsA($content, 'string');
        $this->assertIdentical(trim($content), $this->fakeView);
    }
}

class CustomSharedViewPartialTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Widget\ViewPartials\SharedPartial';

    function __construct () {
        parent::__construct();

        $this->viewPath = APPPATH . 'views/Partials/';
        $this->fakeView = 'Manglares';
        $this->customPartial = 'bananas.html.php';
        umask(0);
    }

    function setUp () {
        FileSystem::mkdirOrThrowExceptionOnFailure($this->viewPath);
        FileSystem::filePutContentsOrThrowExceptionOnFailure($this->viewPath . "/{$this->customPartial}", $this->fakeView);
    }

    function tearDown () {
        @unlink($this->viewPath . $this->customPartial);
    }

    function testCustomSharedViewPartialIsUsedWhenACoreDoesNot () {
        $view = new SharedPartial('Partials.' . basename($this->customPartial, '.html.php'), 'nevando');
        $content = $view->getContents();
        $this->assertIdentical($this->fakeView, $content);
    }

    function testFalseIsReturnedWhenNoPartialExistsInCoreOrCustom () {
        $view = new SharedPartial('Partials.volteretas', 'nevando');
        $content = $view->getContents();
        $this->assertFalse($content);
    }
}
