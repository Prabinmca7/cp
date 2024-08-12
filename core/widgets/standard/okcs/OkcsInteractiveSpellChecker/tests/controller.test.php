<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text as Text;

class TestOkcsInteractiveSpellChecker extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/OkcsInteractiveSpellChecker";

    /*
    * Common function with all the set up configurations
    */
    function setUp() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        parent::setUp();
    }

    /*
    * Common function with all the tear down configurations
    */
    function tearDown() {
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
        parent::tearDown();
    }

    /*
    * UnitTest case to test single misspelt word.
    */
    function testSingleMisspeltWord()
    {
        $this->addUrlParameters(array('kw' => 'Automatci'));
        \RightNow\Libraries\Search::clearCache();
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['source_id'] = 'OKCSSearch';
        $data = $this->getWidgetData();
        $this->assertTrue(Text::stringContains($data['js']['fieldParaphrase'], 'automatic'));
    }
    
    /*
    * UnitTest case to test multiple misspelt words.
    */
    function testMultipleMisspeltWords() {
        $this->addUrlParameters(array('kw' => 'ValidInteractiveSpellCheck'));//This keyword has mock response for mis-spelt string "Tihs is samle tst cotent for automatci"
        \RightNow\Libraries\Search::clearCache();
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['source_id'] = 'OKCSSearch';
        $data = $this->getWidgetData();
        $this->assertTrue(Text::stringContains($data['js']['fieldParaphrase'], 'Ths is sample test content for automatic'));
    }

    /**
    * UnitTest case to test Multiple source Ids
    */
    function testMultipleSourceIds() {
        $this->addUrlParameters(array('kw' => 'Automatci'));
        \RightNow\Libraries\Search::clearCache();
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['source_id'] = 'OKCSSearch, KFSearch, SocialSearch';
        $data = $this->getWidgetData();
        $this->assertTrue(Text::stringContains($data['js']['fieldParaphrase'], 'automatic'));
    }
}
