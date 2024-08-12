<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestProductCatalogSearchFilter extends WidgetTestCase
{
    public $testingWidget = "standard/search/ProductCatalogSearchFilter";

    function testGetData()
    {
        //@@@ QA 140109-000077 E2E CP ASSETS:  ProductCatalogSearchFilter widget doesn't throw a widget error in dev mode when an invalid report_id is given
        $this->createWidgetInstance();
        $this->setWidgetAttributes(array('report_id' => 123455));
        $data = $this->getWidgetData();
        $this->assertSame(count($data['js']), 0);

        $this->createWidgetInstance();
        $this->setWidgetAttributes(array('default_value' => 11));
        $data = $this->getWidgetData();
        $this->assertSame($data['attrs']['report_id'], 228);
        $this->assertSame($data['attrs']['default_value'], 11);
        $this->assertSame($data['attrs']['filter_type'], 'pc');
        $this->assertSame($data['js']['oper_id'], 1);
        $this->assertSame($data['js']['fltr_id'], 2);
        $this->assertSame($data['js']['report_def'], '~any~');
    }
}