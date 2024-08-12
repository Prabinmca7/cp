<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestSocialContentRatingHelper extends CPTestCase {
    public $testingWidget = "RightNow/Helpers/SocialContentRatingHelper";

    function __construct($label = null) {
        parent::__construct($label);
        $this->helper = new \RightNow\Helpers\SocialContentRatingHelper;
    }

    function testChooseCountLabel () {
        $this->assertSame('', $this->helper->chooseCountLabel(1, '', 'plural'));
        $this->assertSame('singular', $this->helper->chooseCountLabel('1', 'singular', 'plural'));
        $this->assertSame('singular', $this->helper->chooseCountLabel(1.0, 'singular', 'plural'));
        $this->assertSame('plural', $this->helper->chooseCountLabel(-1, '', 'plural'));
        $this->assertSame('plural', $this->helper->chooseCountLabel(0, '', 'plural'));
        $this->assertSame('4 plurals', $this->helper->chooseCountLabel(4, '', '%s plurals'));
    }
}
