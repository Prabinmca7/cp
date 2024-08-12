<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Framework;
use RightNow\Utils\Text as Text;

class TestDocumentRating extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/DocumentRating";

    /**
    * UnitTest case to test widget controller with default attributes.
    */
    function testDefaultAttributes()
    {
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => '1000034'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIsA($data['ratingData'], 'Array');
        //$this->assertNotNull($data['ratingData']['surveyRecordID']);
        //$this->assertNotNull($data['ratingData']['questions']);
        //$this->assertNotNull($data['ratingData']['contentID']);
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test null resultset.
    */
    function testNullResultSetWithInvalidValue()
    {
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => '12345'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNull($data['ratingData']['surveyRecordID']);
        $this->assertNull($data['ratingData']['questions']);
        $this->assertNull($data['ratingData']['contentID']);
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test empty resultset.
    */
    function testEmptyResultSet(){
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => ''));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNull($data['ratingData']);
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test Invalid IM Api url.
    */
    function testConfigInvalidImApiUrl(){
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'p://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => '111'));
        $this->createWidgetInstance();
        Framework::setCache('OKCS_DOC_111', false);
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/okcs/DocumentRating - URL protocol for config IM_API_URL is not set'));
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
