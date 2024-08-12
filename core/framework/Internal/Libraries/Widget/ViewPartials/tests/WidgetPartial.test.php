<?php

use RightNow\Utils\Text,
    RightNow\Internal\Libraries\Widget\ViewPartials\WidgetPartial;

require_once CPCORE . 'Internal/Libraries/Widget/ViewPartials/Partial.php';

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class WidgetViewPartialTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Widget\ViewPartials\WidgetPartial';

    function testGetOptimizedContents () {
        $partial = new WidgetPartial('bananas', 'standard/utils/Blank', 'Optimized');
        $results = $partial->getContents();
        $this->assertIdentical('_standard_utils_Blank_bananas', $results);
    }

    function testGetNonOptimizedContents () {
        $widgetPartials = glob(CORE_WIDGET_FILES . 'standard/*/*/*.html.php');
        $this->assertTrue(count($widgetPartials) > 0, "No standard widget partials found");

        // Examine all standard widget partials, ensuring their rn:blocks are subbed out.
        foreach ($widgetPartials as $path) {
            list(, $widgetPath) = explode('widgets/', $path);
            $partialName = basename($widgetPath, '.html.php');
            $widgetPath = Text::getSubstringBefore($widgetPath, '/' . "{$partialName}.html.php");
            $partial = new WidgetPartial($partialName, $widgetPath, 'NonOptimized');
            $viewContent = $partial->getContents();

            $this->assertIsA($viewContent, 'string');
            $this->assertTrue(strlen($viewContent) > 0, "{$widgetPath}/{$partialName}.html.php has an empty view");
            $this->assertStringDoesNotContain($viewContent, 'rn:block');
        }
    }
}
