<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use \RightNow\Connect\v1_4 as Connect;

class TestAccountDropdown extends WidgetTestCase {
    public $testingWidget = "standard/login/AccountDropdown";

    function testGetNameToDisplay() {
        $this->createWidgetInstance();
        $this->getWidgetData();
        $this->logIn();

        $getNameToDisplay = $this->getWidgetMethod('getNameToDisplay');

        $contact = $this->CI->model('Contact')->get()->result;
        $socialUser = $this->CI->model('CommunityUser')->get()->result;

        $nameToDisplay = $getNameToDisplay($contact);
        $this->assertIsA($nameToDisplay, 'string');
        $this->assertSame($nameToDisplay, 'slatest');

        $this->logOut();
    }
}
