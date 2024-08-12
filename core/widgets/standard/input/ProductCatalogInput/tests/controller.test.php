<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestProductCatalogInput extends WidgetTestCase
{
    public $testingWidget = "standard/input/ProductCatalogInput";

    function testGetData()
    {
        $this->createWidgetInstance(array('default_value' => 11));
        $data = $this->getWidgetData();

        $this->assertSame($data['attrs']['default_value'], 11);
        $this->assertSame($data['attrs']['name'], 'Asset.Product');
        $this->assertTrue(is_array($data['js']['hierData']));
        $this->assertSame($data['js']['hierData'][222014][0]['id'], 11);
        $this->assertSame($data['js']['hierData'][222014][0]['label'], 'MF4770M');
        $this->assertSame($data['js']['hierData'][222014][0]['hasChildren'], false);
        $this->assertSame($data['js']['hierData'][222014][0]['serialized'], true);
        $this->assertSame($data['js']['hierData'][222014][0]['selected'], true);
    }

    function testGetDefaultChain() 
    {
        $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getDefaultChain');

        $result = $method();
        $this->assertTrue(is_array($result));
        $this->assertSame(count($result), 0);

        $this->setWidgetAttributes(array('default_value' => 11));
        $result = $method();
        $this->assertTrue(is_array($result));
        $this->assertSame(count($result), 4);
        $this->assertSame($result, array(222004, 222008, 222014, 11));
    }
}