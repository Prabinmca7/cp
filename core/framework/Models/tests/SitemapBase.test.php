<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FakeSitemapBaseModelInstance extends \RightNow\Models\SitemapBase {

	function __construct() {
		parent::__construct();

        $this->reportID = 15138;
	}

	public function getTotalRows() {
		return parent::getTotalRows();
	}

	public function getReportData($settings) {
		return parent::getReportData($settings);
	}

	public function getReportSettings() {
		return parent::_getReportSettings();
	}

	public function processData(array $row) {
        list($questionId, $title, $bestAnswer, $bestComment, $lastModified) = $row;
        $path = $this->_processLink($questionId, $title);

        return array($path, $bestAnswer, $lastModified, $title, $questionId);
    }

    public function prePriorityCalculation(array $data) {}

    public function calculatePriority(array $data, array $miscData) {}
}

class SitemapBaseTest extends CPTestCase {

    function __construct() {
        parent::__construct();
    }

    function testTotalRows() {
    	$model = new FakeSitemapBaseModelInstance();
        $response = $model->getTotalRows();

        $this->assertTrue(is_numeric($response), "SitemapBaseTest::testTotalRows:: Row count is not numeric");
    }

    function testGetReportData() {
    	$model = new FakeSitemapBaseModelInstance();
        $settings = array('pageLimit' => 10, 'pageNumber' => 1);
        $response = $model->getReportData($settings);

        $this->assertIsA($response, 'array', "SitemapBaseTest::testProcess:: Response is not an array");
        $this->assertNotNull($response['data'], "SitemapBaseTest::testProcess:: No data received");
        $this->assertTrue(is_numeric($response['data'][0][0]), "SitemapBaseTest::testProcess:: Quesiton id is not numeric");
        $this->assertTrue(is_string($response['data'][0][1]));
        $this->assertTrue(is_numeric($response['data'][0][2]));
        $this->assertTrue(is_numeric($response['data'][0][3]));
        $this->assertTrue(is_numeric($response['data'][0][4]));
    }


}

?>