<?php

use RightNow\Connect\v1_4 as Connect;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestProdCatNotificationManager extends WidgetTestCase {
    public $testingWidget = "standard/notifications/ProdCatNotificationManager";

    function testGetData() {
        //Add a single product and verify the data structure
        $model = get_instance()->model('Notification');
        $this->logIn();
        $model->add('product', 6);
        $model->add('category', 161);

        $this->createWidgetInstance();
        $data = $this->getWidgetData();

        $viewData = $data['notifications'];
        $jsData = $data['js']['notifications'];

        $this->assertIdentical(count($viewData), 2);
        $this->assertIdentical($viewData[0]['label'], 'Category - Basics');
        $this->assertIdentical($viewData[0]['url'], '/app/categories/detail/c/161' . \RightNow\Utils\Url::sessionParameter());
        $this->assertTrue(isset($viewData[0]['startDate']));
        $this->assertIdentical($viewData[1]['label'], 'Product - Voice Plans');
        $this->assertIdentical($viewData[1]['url'], '/app/products/detail/p/6' . \RightNow\Utils\Url::sessionParameter());
        $this->assertTrue(isset($viewData[1]['startDate']));

        $this->assertTrue(count($viewData) === count($jsData));

        //@@@ QA 130607-000077 Add a couple more items, including a category and verify that the sort order of both arrays is identical
        $model->add('product', 160);
        $model->add('category', 161);
        $this->createWidgetInstance();
        $data = $this->getWidgetData();

        $viewData = $data['notifications'];
        $jsData = $data['js']['notifications'];
        $this->assertTrue(count($viewData) === count($jsData));

        foreach($viewData as $index => $notification) {
            $matches = array();
            $this->assertIdentical(preg_match("@/(?:p|c)/(\d+)@", $notification['url'], $matches), 1);
            $this->assertTrue(is_numeric($matches[1]));
            $this->assertIdentical($jsData[$index]['id'], intval($matches[1]));
        }

        $model->deleteAll('product');
        $model->deleteAll('category');
        $this->logOut();
    }
}
