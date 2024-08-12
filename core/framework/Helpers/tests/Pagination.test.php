<?php

use RightNow\Helpers\Pagination,
    RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class PaginationHelperTest extends CPTestCase {
    public $testingClass = 'RightNow\Helpers\PaginationHelper';

    function testPageLink() {
        $pageLink = $this->getMethod("pageLink");
        $url = $pageLink("3", array("key" => "page"));
        $this->assertTrue(Text::stringContains($url, "page/3"));
    }

    function testShouldShowHellip() {
        $shouldShowHellip = $this->getMethod("shouldShowHellip");
        $this->assertFalse($shouldShowHellip(1, 1, 1));
        $this->assertFalse($shouldShowHellip(1, 1, 2));
        $this->assertTrue($shouldShowHellip(7, 5, 11));
        $this->assertTrue($shouldShowHellip(3, 5, 15));
        $this->assertFalse($shouldShowHellip(11, 11, 11));
    }

    function testShouldShowPageNumber() {
        $shouldShowPageNumber = $this->getMethod("shouldShowPageNumber");
        $this->assertTrue($shouldShowPageNumber(1, 1, 1));
        $this->assertTrue($shouldShowPageNumber(12, 12, 12));
        $this->assertFalse($shouldShowPageNumber(3, 5, 10));
        $this->assertFalse($shouldShowPageNumber(9, 4, 18));
        $this->assertTrue($shouldShowPageNumber(5, 4, 200));
    }
}
