<?php

use RightNow\Api,
    RightNow\Utils\Config,
    RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SiteFeedbackTest extends WidgetTestCase {
    public $testingWidget = 'standard/feedback/SiteFeedback';

    function testSubmitSiteFeedback() {
        $this->createWidgetInstance(array('name' => 'SiteFeedback_1'));

        $data = $this->getWidgetData();
        $method = 'submitSiteFeedback';

        // with f_tok token
        $response = $this->callAjaxMethod($method, array(
            'rate' => 0,
            'f_tok' => $data['js']['f_tok'],
            'message' => 'I seem to have forgot my form token',
            'email' => 'foo@you.nill',
        ));
        $this->assertNotNull($response);
        $this->assertNotNull($response->ID);
        $this->assertTrue(is_numeric($response->ID) && $response->ID > 0);

        // No session cookie (so form token is not valid)
        $response = $this->callAjaxMethod($method,
            array(
                'rate' => 0,
                'f_tok' => $data['js']['f_tok'],
                'message' => 'best  site  ever..',
                'email' => 'foo@you.nill',
            ),
            true,
            $this->widgetInstance,
            array(),
            false
        );
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, "We're sorry, but that action cannot be completed at this time. Please refresh your page and try again."));

        // Valid feedback
        $data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = $this->callAjaxMethod($method,
            array(
                'rate' => 0,
                'f_tok' => $data['js']['f_tok'],
                'message' => 'best  site  ever..',
                'email' => 'foo@you.nill',
            ),
            true,
            $this->widgetInstance,
            array(),
            true
        );
        $incidentID = $response->ID;
        $this->assertTrue(is_numeric($incidentID) && $incidentID > 0);
    }
}
