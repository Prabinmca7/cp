<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsRecentlyViewedContentTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/OkcsRecentlyViewedContent';

    function test () {
        $this->createWidgetInstance();
        $widgetData = $this->widgetInstance->getData();
    }
}
