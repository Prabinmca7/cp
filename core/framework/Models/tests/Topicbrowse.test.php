<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TopicbrowseTest extends CPTestCase {
    function __construct() {
        $this->CI = get_instance();
        $this->model = $this->CI->model('Topicbrowse');
    }

    function responseCheck($response, $expectedReturn = 'array') {
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, $expectedReturn);
        $this->assertIsA($response->errors, 'array');
        $this->assertIsA($response->warnings, 'array');
        $this->assertIdentical(0, count($response->errors), var_export($response->errors, true));
        $this->assertIdentical(0, count($response->warnings), var_export($response->warnings, true));
    }
    
    function testGetTopicBrowseTree() {
        $response = $this->model->getTopicBrowseTree();
        $this->responseCheck($response);
        $this->assertIdentical(array(), $response->result);
    }

    function testGetBestMatchClusterID() {
        $searchQuery = 'phone';
        $response = $this->model->getBestMatchClusterID($searchQuery);
        $this->responseCheck($response, 'int');
        $this->assertIdentical(0, $response->result);
    }

    function testGetSearchBrowseTree() {
        $searchQuery = 'phone';
        $response = $this->model->getSearchBrowseTree($searchQuery);
        $this->responseCheck($response);
        $this->assertIdentical(array(array('display' => 'bestMatch')), $response->result);
    }
}
