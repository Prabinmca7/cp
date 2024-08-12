<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestRelatedAnswersHelper extends CPTestCase {
    public $testingClass = "RightNow/Helpers/RelatedAnswerHelper";

    function __construct() {
        parent::__construct();
        $this->helper = new \RightNow\Helpers\RelatedAnswersHelper;
    }

    function setUp() {
        $this->addUrlParameters(array("kw" => "shoes"));
    }

    function tearDown() {
        $this->restoreUrlParameters();
    }

    function testFormatTitle() {
        $result = $this->helper->formatTitle("walrus shoes", 0);
        $this->assertSame($result, "walrus shoes");

        $result = $this->helper->formatTitle("walrus <shoes>", 0);
        $this->assertSame($result, "walrus &lt;shoes&gt;");

        $result = $this->helper->formatTitle("walrus shoes and books", 8);
        $this->assertSame($result, "walrus...");

        $result = $this->helper->formatTitle("walrus shoes and books", 9);
        $this->assertSame($result, "walrus...");

        $result = $this->helper->formatTitle("walrus shoes and books", 0, true);
        $this->assertSame($result, "walrus <em class='rn_Highlight'>shoes</em> and books");

        $result = $this->helper->formatTitle("walrus shoes and books", 15, true);
        $this->assertSame($result, "walrus <em class='rn_Highlight'>shoes</em>...");
    }
}
