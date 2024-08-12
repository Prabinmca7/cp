<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Utils\Url;

class QuestionsSitemapTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\QuestionsSitemap';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\QuestionsSitemap();
    }

    function testProcessData() {
        $id = 559;
        $title = "[ACTIVE]Question by moderator";
        $totalBestAnswers = 2;
        $totalComments = 0;
        $timestamp = 1416999187;
        $response = $this->model->processData(array($id, $title, $totalComments, $totalBestAnswers, $timestamp));
        $expectedArray = array(
            Url::defaultQuestionUrl($id)."/~/" . \RightNow\Libraries\SEO::getAnswerSummarySlug($title),
            $timestamp,
            $title,
            $id,
            array(
                'bestAnswerCount' => $totalBestAnswers,
                'commentCount' => $totalComments
            )
        );

        $this->assertSame($response, $expectedArray, "QuestionsSitemapTest:testProcessData: Response object is not same");
    }

    function testProcessQuestionLink() {
        $method = $this->getMethod("processLink");
        $id = 1;
        $title = "A sample title here";
        $response = $method($id, $title);

        $this->assertSame($response, Url::defaultQuestionUrl($id)."/~/" . \RightNow\Libraries\SEO::getAnswerSummarySlug($title));
    }

    function prePriorityCalculation() {
        $method = $this->getMethod("prePriorityCalculation");

        // positive test case
        $data = array('upperLimitSocialQuestions' => 0.8);
        $response = $method($data);
        $this->assertSame($response, array('minPriority' => 0, 'maxPriority' => 0.8), "Pre Priority calculated data are not same");

        // negative test cases
        $data = array();
        $response = $method($data);
        $this->assertSame($response, array('minPriority' => 0, 'maxPriority' => 1), "Pre Priority negative test case. Max priority is not same");

        $data = array('lowerLimit' => 0.3);
        $response = $method($data);
        $this->assertSame($response, array('minPriority' => 0, 'maxPriority' => 1), "Pre Priority negative test case. Max priority is not same");
    }

    function testCalculatePriority() {
        $data = array(
            'bestAnswerCount' => 3,
            'commentCount' => 5
            );
        $miscData = array(
            'totalPages' => 3,
            'preHookData' => array('upperLimitSocialQuestions' => 0.8)
            );
        $this->model->getReportData(array('pageNumber' => 1));
        $response = $this->model->calculatePriority($data, $miscData);
        $this->assertTrue(($response > 0) ? true : false, "Priority is not getting calculated or is 0");

        // negative test case
        $data = array(
            'bestAnswerCount' => 3
            );
        $miscData = array(
            'preHookData' => array('upperLimitSocialQuestions' => 0.8)
            );
        $response = $this->model->calculatePriority($data, $miscData);
        $this->assertTrue(($response > 0) ? true : false, "Priority is not getting calculated or is 0");
    }
}