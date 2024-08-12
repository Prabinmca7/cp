<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text as Text;

class AnswerTitleTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/AnswerTitle';

    /**
    * UnitTest case to test invalid IM Api Url scenario.
    */
    function testConfigInvalidImApiUrl(){
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'p://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        
        $this->addUrlParameters(array('a_id' => '111'));
        $this->createWidgetInstance();
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/okcs/AnswerTitle - URL protocol for config IM_API_URL is not set'));

        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test an invalid Document Id.
    */
    function testInvalidDocId() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => 'XXXX', 'loc' => 'en_US'));
        $this->createWidgetInstance();
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        $this->assertFalse($result);
        $this->assertTrue(Text::stringContains($content, 'Widget Error: standard/okcs/AnswerTitle - Invalid answer Id'));
        
        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test default attributes.
    */
    function testDefaultAttributes() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => '1000014', 'loc' => 'en_US'));
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIdentical($data['answer']['title'], 'test v3 123');
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test default attributes.
    */
    function testEmptyTitle() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->addUrlParameters(array('a_id' => '1000043', 'loc' => 'en_US'));
        \RightNow\Utils\Framework::removeCache('ANSWER_KEY');
        $widget = $this->createWidgetInstance();
        $widget->data['attrs']['label_no_title'] = "Custom - No title";
        $data = $this->getWidgetData();
        $this->assertIdentical($data['answer']['title'], $widget->data['attrs']['label_no_title']);
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}