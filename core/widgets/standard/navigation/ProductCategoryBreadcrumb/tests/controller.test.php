<?php
use RightNow\Api,
    RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Helper,
    RightNow\UnitTest\Fixture;

Helper::loadTestedFile(__FILE__);

class TestProductCategoryBreadcrumb extends WidgetTestCase {
    public $testingWidget = "standard/navigation/ProductCategoryBreadcrumb";

    function __construct() {
        parent::__construct();
        $this->interfaceID = \RightNow\Api::intf_id();
        $this->fixtureInstance = new Fixture();
    }

    function tearDown() {
        $this->fixtureInstance->destroy();
    }

    function getData($type, $param, $id, $method = null, array $attributes = array()) {
        $this->addUrlParameters(array($param => $id));
        $instance = $this->createWidgetInstance(array_merge(array('type' => $type), $attributes));
        $data = $this->getWidgetData($instance);
        if ($method) {
            $method = $this->getWidgetMethod($method, $widget);
            $data = $method();
        }
        $this->restoreUrlParameters();

        return $data;
    }

    function testBaseCase() {
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertEqual(0, count($data['levels']));
    }

    function testProductInUrl() {
        $data = $this->getData('product', 'p', 160);
        $this->assertSame(3, count($data['levels']));
        $last = end($data['levels']);
        $this->assertSame(160, $last['id']);

        $data2 = $this->getData('product', 'p', '1,4,160');
        $this->assertIdentical($data['levels'], $data2['levels']);
    }

    function testCategoryInUrl() {
        $data = $this->getData('category', 'c', 77);
        $this->assertSame(2, count($data['levels']));
        $last = end($data['levels']);
        $this->assertSame(77, $last['id']);

        $data2 = $this->getData('category', 'c', '71,77');
        $this->assertIdentical($data['levels'], $data2['levels']);
    }

    function testAnswer() {
        $data = $this->getData('product', 'a_id', 52);
        $levels = $data['levels'];
        $this->assertSame(2, count($levels));
        $this->assertSame(1, $levels[0]['id']);
        $this->assertSame(4, $levels[1]['id']);
    }

    function testQuestion() {
        $data = $this->getData('product', 'qid', 1);
        $levels = $data['levels'];
        $this->assertSame(3, count($levels));
        $this->assertSame(1, $levels[0]['id']);
        $this->assertSame(2, $levels[1]['id']);
        $this->assertSame(10, $levels[2]['id']);
    }

    function testGetLevelsForProduct() {
        $productID = 2;
        $levels = $this->getData('product', 'p', $productID, 'getLevels');
        $this->assertEqual(2, count($levels));
        $this->assertEqual($productID, $levels[1]['id']);
    }

    function testGetLevelsForCategory() {
        $categoryID = 77;
        $levels = $this->getData('category', 'c', $categoryID, 'getLevels');
        $this->assertEqual(2, count($levels));
        $this->assertEqual($categoryID, $levels[1]['id']);
    }

    function testGetLevelsForAnswer() {
        $levels = $this->getData('product', 'a_id', 52, 'getLevels');
        $this->assertEqual(2, count($levels));
        $this->assertEqual(4, $levels[1]['id']);
    }

    function testGetLevelsForQuestion() {
        $levels = $this->getData('product', 'qid', 1, 'getLevels');
        $this->assertEqual(3, count($levels));
        $this->assertEqual(10, $levels[2]['id']);
    }

    function testDisplayCurrentItem() {
        $productID = 1; // Mobile Phones
        $data = $this->getData('product', 'p', $productID, null, array('display_current_item' => true, 'display_first_item' => true));
        $levels = $data['levels'];
        $this->assertEqual(1, count($levels));
        $this->assertEqual($productID, $levels[0]['id']);

        $data = $this->getData('product', 'p', 1, null, array('display_current_item' => false, 'display_first_item' => true));
        $this->assertEqual(0, count($data['levels']));

        $productID = 8; // Mobile Phones -> Android -> Motorola Droid
        $data = $this->getData('product', 'p', $productID, null, array('display_current_item' => true, 'display_first_item' => true));
        $levels = $data['levels'];
        $this->assertEqual(3, count($levels));
        $this->assertEqual($productID, $levels[2]['id']);

        $data = $this->getData('product', 'p', $productID, null, array('display_current_item' => false, 'display_first_item' => true));
        $levels = $data['levels'];
        $this->assertEqual(2, count($levels));
        $this->assertEqual(2, $levels[1]['id']);
    }

    function testDisplayFirstItem() {
        $productID = 1; // Mobile Phones
        $data = $this->getData('product', 'p', $productID, null, array('display_first_item' => true));
        $levels = $data['levels'];
        $this->assertEqual(1, count($levels));
        $this->assertEqual($productID, $levels[0]['id']);

        $data = $this->getData('product', 'p', 1, null, array('display_first_item' => false));
        $this->assertEqual(0, count($data['levels']));

        $productID = 8; // Mobile Phones -> Android -> Motorola Droid
        $data = $this->getData('product', 'p', $productID, null, array('display_first_item' => true));
        $levels = $data['levels'];
        $this->assertEqual(3, count($levels));
        $this->assertEqual($productID, $levels[2]['id']);

        $data = $this->getData('product', 'p', $productID, null, array('display_first_item' => false));
        $levels = $data['levels'];
        $this->assertEqual(3, count($levels));
        $this->assertEqual($productID, $levels[2]['id']);
    }

    function testGetChainFromAssociatedObject() {
        $question = $this->fixtureInstance->make('QuestionWithProduct'); // Associated with P5

        $result = $this->getData('product', 'qid', $question->ID, null, array('display_first_item' => true));
        $this->assertSame(count($result['levels']), 5);

        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 0 WHERE id = 129"); //P2
        Connect\ConnectAPI::commit();
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}129Visible", false);
        \RightNow\Utils\Framework::removeCache("ProdcatModel1Product120-FormattedChain");

        $result = $this->getData('product', 'qid', $question->ID, null, array('display_first_item' => true));
        $this->assertSame(count($result['levels']), 1);
        $this->assertSame($result['levels'][0]['label'], 'p1');


        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 1 WHERE id = 129"); //P2
        Connect\ConnectAPI::commit();
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}129Visible", true);
        \RightNow\Utils\Framework::removeCache("ProdcatModel1Product120-FormattedChain");
    }
}