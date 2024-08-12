<?php

use RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\ViewPartials\Handler as ViewPartialHandler;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ViewPartialHandlerTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Widget\ViewPartials\Handler';

    function testConstructorErrors () {
        try {
            $view = new ViewPartialHandler('', '');
            $this->fail("Should've thrown an error");
        }
        catch (\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'required');
        }

        try {
            $view = new ViewPartialHandler('confess..to..me', '');
            $this->fail("Should've thrown an error");
        }
        catch (\Exception $e) {
            $this->assertStringContains($e->getMessage(), '..');
        }

        try {
            $view = new ViewPartialHandler('fulfill/your/desires/for/you', '');
            $this->fail("Should've thrown an error");
        }
        catch (\Exception $e) {
            $this->assertStringContains($e->getMessage(), '/');
        }
    }

    function testValidConstructor () {
        try {
            new ViewPartialHandler('help', 'mind');
        }
        catch (\Exception $e) {
            $this->fail("Should not have thrown an exception");
        }
    }

    function testInvalidArgsInConstructorDontCauseGetViewContentToFreakOut () {
        $handler = new ViewPartialHandler('fjskl;', 'fdsa;j');
        $this->assertFalse($handler->view->getContents());
    }

    function testGetViewContent () {
        $widgetPath = \RightNow\Utils\Widgets::getFullWidgetVersionDirectory('standard/feedback/AnswerFeedback');
        require_once CORE_WIDGET_FILES . $widgetPath . 'controller.php';

        $widget = new \RightNow\Widgets\AnswerFeedback(array());

        $handler = new ViewPartialHandler('buttonView', $widgetPath);
        $content = $handler->view->getContents();

        $this->assertIsA($content, 'string');
        $this->assertStringDoesNotContain('rn:block', $content);
    }
}
