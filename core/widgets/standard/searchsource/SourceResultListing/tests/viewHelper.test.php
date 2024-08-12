<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\UnitTest\Fixture as Fixture,
    RightNow\Utils\Text;

class TestSourceResultListingHelper extends CPTestCase {
    public $testingClass = "RightNow/Helpers/SourceResultListingHelper";

    function __construct() {
        parent::__construct();
        $this->helper = new \RightNow\Helpers\SourceResultListingHelper;
    }

    function testConstructMoreLink() {
        $result = $this->helper->constructMoreLink("app/results", array(array("key" => "walrus", "value" => "shoes")));
        $this->assertTrue(Text::stringContains($result, "app/results/walrus/shoes"));

        $result = $this->helper->constructMoreLink("app/results", array(array("key" => "kw", "value" => "shrimp shoes")));
        $this->assertTrue(Text::stringContains($result, "app/results/kw/shrimp+shoes"));
    }

    function testFormatSummary() {
        $this->addUrlParameters(array("kw" => "dabba"));

        $result = $this->helper->formatSummary("yabba dabba do", false);
        $this->assertEqual($result, "yabba dabba do");

        $result = $this->helper->formatSummary("yabba dabba do", true);
        $this->assertEqual($result, "yabba <em class='rn_Highlight'>dabba</em> do");

        $this->restoreUrlParameters();
    }
}
