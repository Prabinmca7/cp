<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsVisualProductCategorySelectorTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/OkcsVisualProductCategorySelector';

    function test () {
        $this->createWidgetInstance();
        $widgetData = $this->widgetInstance->getData();
    }
}
