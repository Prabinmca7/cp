<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsProductCategoryBreadcrumbTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/OkcsProductCategoryBreadcrumb';

    function test () {
        $this->createWidgetInstance();
        $widgetData = $this->widgetInstance->getData();
    }
}
