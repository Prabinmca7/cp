<?

require_once CORE_WIDGET_FILES . 'standard/search/ProductCategorySearchFilter/controller.php';

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SourceProductCategorySearchFilterTest extends WidgetTestCase {
    public $testingWidget = 'standard/searchsource/SourceProductCategorySearchFilter';

    function setUp() {
        // clear out the token processCache
        $reflectionClass = new ReflectionClass('RightNow\Utils\Framework');
        $reflectionProperty = $reflectionClass->getProperty('processCache');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(array());
    }

    function testDefaultBehavior () {
        \RightNow\Libraries\Search::clearCache();

        $instance = $this->createWidgetInstance(array('source_id' => 'KFSearch'));
        $this->assertNull($instance->getData());

        if (strtolower($instance->data['attrs']['filter_type']) === 'product') {
            $this->assertSame('p', $instance->data['js']['filter']['key']);
            $this->assertNull($instance->data['js']['filter']['value']);
            $this->assertSame('product', $instance->data['js']['filter']['type']);
            $this->assertSame(HM_PRODUCTS, $instance->data['js']['hm_type']);
        }
        else {
            $this->assertSame('c', $instance->data['js']['filter']['key']);
            $this->assertNull($instance->data['js']['filter']['value']);
            $this->assertSame('category', $instance->data['js']['filter']['type']);
            $this->assertSame(HM_CATEGORIES, $instance->data['js']['hm_type']);
        }

        $this->assertIdentical(array(), $instance->data['js']['initial']);
        $this->assertSame(1, count($instance->data['js']['hierData']));
        $this->assertTrue(count($instance->data['js']['hierData'][0]) > 1);
    }

    function testPrefill () {
        $this->addUrlParameters(array('p' => '1,2'));

        $instance = $this->createWidgetInstance(array('source_id' => 'SocialSearch'));
        $this->assertNull($instance->getData());
        $this->assertIdentical(array('value' => '1,2', 'key' => 'p', 'type' => 'product'), $instance->data['js']['filter']);
        $this->assertIdentical(array('1', '2'), $instance->data['js']['initial']);
        $this->assertSame(3, count($instance->data['js']['hierData']));
        $this->assertTrue(count($instance->data['js']['hierData'][0]) > 1);
        $this->assertTrue(count($instance->data['js']['hierData'][1]) > 1);
        $this->assertTrue(count($instance->data['js']['hierData'][2]) > 1);

        $this->restoreUrlParameters();
    }

    function testErrorIsOutput () {
        $this->createWidgetInstance(array('source_id' => 'doesntexist'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $output) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertStringContains($output, 'Widget Error: standard/searchsource/SourceProductCategorySearchFilter - No search sources were provided');
    }
}
