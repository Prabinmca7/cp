<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SubscriptionButtonTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/SubscriptionButton';

    /**
    * UnitTest case to test widget controller with valid attributes to fetch subscription details for the logged in user
    */
    function testDefaultAttributes()
    {
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => '1000003', 'loc' => 'en_US'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNotNull($data['js']["subscriptionData"]);
        $this->assertNotNull($data['js']["subscriptionData"]->items[0]);
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
    
    /**
    * UnitTest case to test empty result set.
    */
    function testInvalidAnswerID(){
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => '12345'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNull($data["subscriptionData"]);
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
