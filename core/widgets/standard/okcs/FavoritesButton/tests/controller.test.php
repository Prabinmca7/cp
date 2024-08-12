<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FavoritesButtonTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/FavoritesButton';

    /**
    * UnitTest case to test widget controller with valid attributes to fetch favorite details for the logged in user
    */
    function testDefaultAttributes()
    {
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => '1000003'));// is a favorite answer
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNotNull($data['js']["favoriteData"]);
        $this->assertSame('501CEB19532344858EB3B0FADE31E930', $data['js']['favoriteID']);
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
    
    /**
    * UnitTest case to test invalid result set.
    */
    function testInvalidAnswerID(){
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => '1000007'));// not a favorite answer
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNotNull($data['js']["favoriteData"]);
        $this->assertNull($data['js']['favoriteID']);
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
