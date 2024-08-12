<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Utils\Url;

class AnswersSitemapTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\AnswersSitemap';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\AnswersSitemap();
    }

    function testProcessData() {
        $response = $this->model->processData(array("<a href='/app/answers/detail/a_id/52' >52</a>", 22, 1269411000, "Enabling MMS on iPhone 3G and iPhone 3GS"));

        $expectedArray = array("/app/answers/detail/a_id/52/~/enabling-mms-on-iphone-3g-and-iphone-3gs", 1269411000, "Enabling MMS on iPhone 3G and iPhone 3GS", "52", array('score' => 22));
        $this->assertSame($response, $expectedArray, "AnswersSitemapTest:testProcessData: Response object is not same");
    }

    function testProcessAnswerLink() {
        $method = $this->getMethod("processAnswerLink");
        $response = $method("<a href='/app/answers/detail/a_id/52' >52</a>", "Enabling MMS on iPhone 3G and iPhone 3GS");

        $expectedArray = array("/app/answers/detail/a_id/52/~/enabling-mms-on-iphone-3g-and-iphone-3gs", "52");
        $this->assertSame($response, $expectedArray, "AnswersSitemapTest:testProcessAnswerLink: Response object is not same");
    }

    function prePriorityCalculation() {
        $method = $this->getMethod("prePriorityCalculation");

        // positive test case
        $data = array('lowerLimitKBAnswers' => 0.6);
        $response = $method($data);
        $this->assertSame($response, array('minPriority' => 0.6, 'maxPriority' => 1), "Pre Priority calculated data are not same");

        // negative test cases
        $data = array();
        $response = $method($data);
        $this->assertSame($response, array('minPriority' => 0, 'maxPriority' => 1), "Pre Priority negative test case. Max priority is not same");

        $data = array('upperLimitSocialQuestions' => 0.8);
        $response = $method($data);
        $this->assertSame($response, array('minPriority' => 0, 'maxPriority' => 1), "Pre Priority negative test case. Max priority is not same");
    }

    function testCalculatePriority() {
        $data = array('score' => 22);
        $miscData = array(
            'totalPages' => 3,
            'preHookData' => array('lowerLimitKBAnswers' => 0)
            );

        $this->model->getReportData(array('pageNumber' => 1));
        $this->model->processData(array("<a href='/app/answers/detail/a_id/52' >52</a>", 22, 1269411000, "Enabling MMS on iPhone 3G and iPhone 3GS"));

        $response = $this->model->calculatePriority($data, $miscData);
        $this->assertTrue(($response > 0) ? true : false, "Priority is not getting calculated or is 0");
    }
}