<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class RecentSearchesTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/RecentSearches';
    
    function testGetData () {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('CP_COOKIES_ENABLED', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIsA($data['js'],'Array');
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
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['source_id'] = 'OKCSSearch, KFSearch, SocialSearch';
        $data = $this->getWidgetData();
        $this->assertIsA($data['js'], 'Array');
    }
}
