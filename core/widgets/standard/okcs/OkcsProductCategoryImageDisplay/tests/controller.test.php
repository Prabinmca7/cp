<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsProductCategoryImageDisplayTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/OkcsProductCategoryImageDisplay';

    function testGetData() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);

        // Test with products
        $categoryID = 'RN_PRODUCT_1';
        $this->addUrlParameters(array('categoryRecordID' => $categoryID));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical('iphone', $data['js']['slug']);
        $this->assertIdentical('Iphone', $data['title']);
        $this->restoreUrlParameters();

        // Test with categories
        $categoryID = 'RN_CATEGORY_1';
        $this->addUrlParameters(array('categoryRecordID' => $categoryID));
        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical('operating-system', $data['js']['slug']);
        $this->assertIdentical('OPERATING_SYSTEM', $data['title']);
        $this->restoreUrlParameters();
    }
}

