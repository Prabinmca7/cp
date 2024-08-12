<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Framework;
use RightNow\Utils\Text as Text;

class TestFacet extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/Facet";

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
    * UnitTest case to test maximum sub-facet size.
    */
    function testMaxSubFacetSizeAttribute()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        \Rnow::updateConfig('CP_ANSWERS_DETAIL_URL', 'answers/answer_view', true);
        $this->addUrlParameters(array('kw' => 'Test'));
        $widget = $this->createWidgetInstance(array('max_sub_facet_size' => 3));
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
    * UnitTest case to test truncate size.
    */
    function testTruncateSizeAttribute()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        \Rnow::updateConfig('CP_ANSWERS_DETAIL_URL', 'answers/answer_view', true);
        $this->addUrlParameters(array('kw' => 'Test'));
        $widget = $this->createWidgetInstance(array('truncate_size' => 10));
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
        $widget = $this->createWidgetInstance(array('truncate_size' => 10));
        $widget->data['attrs']['source_id'] = 'OKCSSearch, KFSearch, SocialSearch';
        $data = $this->getWidgetData();
        $this->assertIsA($data['facets'], 'Array');
        $this->assertIdentical(4, count($data['facets']));
        
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test Multi-facet search scenarios
    */
    function testMultiFacetProdCategFetch()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);

        $this->addUrlParameters(array('kw' => 'Test'));
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['source_id'] = 'OKCSSearch';
        $widget->data['attrs']['enable_multi_select'] = true;
        $data = $this->getWidgetData();

        // Products and categories are fetched as part of Facet widget's multi-facet flow
        $this->assertIsA($data['products'], 'Array');
        $this->assertIsA($data['categories'], 'Array');

        // Facets fetched properly as part of old flow
        $this->assertIsA($data['facets'], 'Array');

        //Product tests
        //Product without children
        $this->assertIdentical($data['products']['topLevelProduct']['RN_PRODUCT_1']->hasChildren, false);
        $this->assertIdentical($data['products']['topLevelProduct']['RN_PRODUCT_1']->children, NULL);// getChildCategories response
        //Product with children
        $this->assertIdentical($data['products']['topLevelProduct']['RN_PRODUCT_7']->hasChildren, true);
        $this->assertIsA($data['products']['topLevelProduct']['RN_PRODUCT_7']->children, 'Array');// getChildCategories response

        //Category tests
        //Category without children
        $this->assertIdentical($data['categories']['topLevelCategory']['RN_CATEGORY_4']->hasChildren, false);
        $this->assertIdentical($data['categories']['topLevelCategory']['RN_CATEGORY_4']->children, NULL);// getChildCategories response
        //Category with children
        $this->assertIdentical($data['categories']['topLevelCategory']['RN_CATEGORY_40']->hasChildren, true);
        $this->assertIsA($data['categories']['topLevelCategory']['RN_CATEGORY_40']->children, 'Array');// getChildCategories response

        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
