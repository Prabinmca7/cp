<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class PerformanceTestTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/PerformanceTest';

    function test () {
        $this->createWidgetInstance();
        $widgetData = $this->widgetInstance->getData();
    }
}
