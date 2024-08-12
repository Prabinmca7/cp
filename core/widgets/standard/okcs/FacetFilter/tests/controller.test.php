<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Framework;
use RightNow\Utils\Text as Text;

class TestFacetFilter extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/FacetFilter";

    /**
    * UnitTest case to test default attribute.
    */
    function testDefaultAttributes()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        \Rnow::updateConfig('CP_ANSWERS_DETAIL_URL', 'answers/answer_view', true);
        $this->addUrlParameters(array('kw' => 'Test'));
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['source_id'] = 'OKCSSearch';
        $data = $this->getWidgetData();
        $this->assertIsA($data['facets'], 'Array');
        $this->assertIdentical(4, count($data['facets']));
        $this->assertNotNull($data['facets']);
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
        \Rnow::updateConfig('CP_ANSWERS_DETAIL_URL', 'answers/detail', true);
    }

    /**
    * UnitTest case to test Multiple source Ids
    */
    function testMultipleSourceIds()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        
        $this->addUrlParameters(array('kw' => 'Test'));
        $widget->data['attrs']['source_id'] = 'OKCSSearch, KFSearch, SocialSearch';
        $data = $this->getWidgetData();
        $this->assertIsA($data['facets'], 'Array');
        $this->assertIdentical(4, count($data['facets']));
        
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test url parameters
    */
    function testUrlParameters()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);

        $this->addUrlParameters(array('kw' => 'Nokia', 'facet' => 'CMS-PRODUCT.RN_PRODUCT_7%2CCMS-CATEGORY_REF.RN_CATEGORY_4%2CDOC_TYPES.CMS-XML%2CCOLLECTIONS.OKKB-TEST'));
        
        $widget->data['attrs']['source_id'] = 'OKCSSearch, KFSearch, SocialSearch';
        $data = $this->getWidgetData();
        $this->assertIsA($data['facets'], 'Array');
        $this->assertIdentical(4, count($data['facets']));
        $this->assertIsA($data['facetObject'], 'Array');
        $this->assertIdentical('CMS-CATEGORY_REF.RN_CATEGORY_4:Cat1', $data['facetObject']['CMS-CATEGORY_REF'][0]);
        $this->assertIdentical('CMS-PRODUCT.RN_PRODUCT_7:Mobile', $data['facetObject']['CMS-PRODUCT'][0]);
        $this->assertIdentical('DOC_TYPES.CMS-XML:ARTICLES', $data['facetObject']['DOC_TYPES'][0]);
        $this->assertIdentical('COLLECTIONS.OKKB-TEST:OKKB-TEST', $data['facetObject']['COLLECTIONS'][0]);

        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
