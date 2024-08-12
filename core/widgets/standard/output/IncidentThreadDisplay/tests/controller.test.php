<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestIncidentThreadDisplay extends WidgetTestCase
{
    public $testingWidget = "standard/output/IncidentThreadDisplay";

    function testGetData()
    {
        $this->createWidgetInstance();

        ob_start();
        $this->getWidgetData();
        $error = ob_get_contents();
        ob_end_clean();

        $this->assertTrue(\RightNow\Utils\Text::stringContains($error, "Widget Error"));
        $this->assertTrue(\RightNow\Utils\Text::stringContains($error, "'name' attribute is required"));

        // i_id with no login
        $this->addUrlParameters(array("i_id" => 133));
        $this->setWidgetAttributes(array('name' => 'incident.threads'));
        $data = $this->getWidgetData();
        $this->assertTrue(is_array($data['value']));
        $this->assertTrue(count($data['value']) === 0);

        // i_id with correct contact logged in
        $this->logIn("mcarbone@example.com.invalid.070503.invalid");
        $data = $this->getWidgetData();
        $this->assertTrue(is_array($data['value']));
        $this->assertTrue(count($data['value']) === 3);

        $this->restoreUrlParameters();
    }
}
