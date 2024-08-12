<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Libraries\Decorator;

class TestIncidentThreadDisplayHelper extends CPTestCase {
    public $testingClass = "RightNow/Helpers/IncidentThreadDisplayHelper";

    function __construct() {
        parent::__construct();
        $this->helper = new \RightNow\Helpers\IncidentThreadDisplayHelper;
        $this->incidentModel = get_instance()->model('Incident');
    }

   function testGetThreadAuthorInfo() {
        $this->logIn('mcarbone@example.com.invalid.070503.invalid');
        $incident = $this->incidentModel->get(133)->result;

        $i = 0;
        $expected = array(
            "Voice Integration",
            "Staff Account (Faith Carson) via channel 'Email'",
            "Customer (Mary Carbone)",
        );
        foreach ((array)$incident->Threads as $thread) {
            Decorator::add($thread, 'Present/IncidentThreadPresenter');
            $this->assertIdentical($expected[$i], $this->helper->getThreadAuthorInfo($thread));
            $i++;
        }

        $this->logOut();

        $this->logIn('ehannan@rightnow.com.invalid');
        $incident = $this->incidentModel->get(141)->result;

        $i = 0;
        $expected = array(
            "Rule Response",
            "Customer (Erich Hannan) via channel 'Service Web'",
        );
        foreach ((array)$incident->Threads as $thread) {
            Decorator::add($thread, 'Present/IncidentThreadPresenter');
            $this->assertIdentical($expected[$i], $this->helper->getThreadAuthorInfo($thread));
            $i++;
        }

        $this->logOut();
    }
}
