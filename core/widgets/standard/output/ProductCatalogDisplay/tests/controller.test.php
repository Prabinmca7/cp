<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect;

class TestProductCatalogDisplay extends WidgetTestCase {
    public $testingWidget = "standard/output/ProductCatalogDisplay";

    function testGetData() {
        $this->user1 = (object) array(
            'login' => 'jerry@indigenous.example.com.invalid.070503.invalid',
            'email' => 'jerry@indigenous.example.com.invalid',
        );
        $this->addUrlParameters(array("asset_id" => 8));
        $this->logIn($this->user1->login);

        $this->createWidgetInstance(array('name' => 'Asset.Product'));
        $data = $this->getWidgetData();
        $this->assertSame($data['value'], array (222004 => array ('ID' => 222004, 'Name' => 'Printers', 'Depth' => 0),
                                                 222008 => array ('ID' => 222008, 'Name' => 'LaserJet', 'Depth' => 1),
                                                 222014 => array ('ID' => 222014, 'Name' => 'Canon', 'Depth' => 2),
                                                 11 => array ('ID' => 11, 'Name' => 'MF4770M', 'Depth' => 3)));
        $this->restoreUrlParameters();
        $this->logOut();

        $this->addUrlParameters(array("product_id" => 11));
        $this->logIn($this->user1->login);

        $this->createWidgetInstance(array('name' => 'Asset.Product'));
        $data = $this->getWidgetData();
        $this->assertSame($data['value'], array (222004 => array ('ID' => 222004, 'Name' => 'Printers', 'Depth' => 0),
                                                 222008 => array ('ID' => 222008, 'Name' => 'LaserJet', 'Depth' => 1),
                                                 222014 => array ('ID' => 222014, 'Name' => 'Canon', 'Depth' => 2),
                                                 11 => array ('ID' => 11, 'Name' => 'MF4770M', 'Depth' => 3)));
        $this->restoreUrlParameters();
        $this->logOut();
    }

    function testGenerateTree()
    {
        $this->createWidgetInstance();
        $method = $this->getWidgetMethod('generateTree');
        $result = $method(7, false);
        $this->assertTrue(is_array($result));
        $this->assertSame($result, array (222004 => array ('ID' => 222004, 'Name' => 'Printers', 'Depth' => 0),
                                          222006 => array ('ID' => 222006, 'Name' => 'DotMatrix', 'Depth' => 1),
                                          222011 => array ('ID' => 222011, 'Name' => 'HP', 'Depth' => 2),
                                          7 => array ( 'ID' => 7, 'Name' => 'HP2932A', 'Depth' => 3, )));
    }

    function testCreateResultItem()
    {
        $this->createWidgetInstance();
        $method = $this->getWidgetMethod('createResultItem');
        $result = $method(7, 'HP2932A', 3);
        $this->assertTrue(is_array($result));
        $this->assertSame($result, array ('ID' => 7, 'Name' => 'HP2932A', 'Depth' => 3));
    }

}
