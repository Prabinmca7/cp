<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsSetNotificationFrequencyTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/OkcsSetNotificationFrequency';

    function test () {
        $this->createWidgetInstance();
        $widgetData = $this->widgetInstance->getData();
    }
}
