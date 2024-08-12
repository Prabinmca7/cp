<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use \RightNow\Libraries\GuidedAssistance,
    \RightNow\Utils\Text;

class GuidedAssistanceLibraryTest extends CPTestCase 
{
    function testGuidedAssistance() {
        $guide = new GuidedAssistance(1, "Test Guide");
        $guide->guideSessionID = \RightNow\Api::generate_session_id();

        $question = new \RightNow\Libraries\Question();
        $question->questionID = 1;
        $question->guideID = 1;
        $question->text = "<b>This is a test question</b>";
        $question->taglessText = htmlspecialchars(strip_tags($question->text), ENT_QUOTES, 'UTF-8');
        $question->agentText = "Some agent text";
        $question->type = 1;
        $question->name = "Test Question 1";
        $guide->addQuestion($question);

        $question = new \RightNow\Libraries\Question();
        $question->questionID = 2;
        $question->guideID = 1;
        $question->text = "<b>This is another test question</b>";
        $question->taglessText = htmlspecialchars(strip_tags($question->text), ENT_QUOTES, 'UTF-8');
        $question->agentText = "Some agent text";
        $question->type = 1;
        $question->name = "Test Question 2";
        $guide->addQuestion($question);

        $response = new \RightNow\Libraries\Response();
        $response->responseID = 1;
        $response->parentQuestionID = 1;
        $response->text = "Yes";
        $response->type = 8;
        $response->childQuestionID = 2;
        $response->responseText = "Do something";
        $response->value = 1;
        $parentQuestion = $guide->getQuestionByID($response->parentQuestionID);
        $parentQuestion->addResponse($response);

        $response = new \RightNow\Libraries\Response();
        $response->responseID = 2;
        $response->parentQuestionID = 1;
        $response->text = "No";
        $response->type = 4;
        $response->childGuideID = 2;
        $response->responseText = "";
        $response->value = 0;
        $parentQuestion = $guide->getQuestionByID($response->parentQuestionID);
        $parentQuestion->addResponse($response);

        $question = $guide->getQuestionByID(2);
        $question->addNameValuePair("key1", "value1");
        $question->addNameValuePair("key2", "value2");

        $guideArray = $guide->toArray();

        $this->assertIdentical(1, $guideArray['guideID']);
        $this->assertIdentical('Test Guide', $guideArray['name']);
        $this->assertTrue(array_key_exists('guideSessionID', $guideArray));
        $this->assertTrue(is_array($guideArray['questions']));
        $this->assertIdentical(count($guideArray['questions']), 2);

        $question = $guideArray['questions'][0];
        $this->assertIdentical(1, $question['questionID']);
        $this->assertIdentical(1, $question['guideID']);
        $this->assertIdentical('Test Question 1', $question['name']);
        $this->assertIdentical('<b>This is a test question</b>', $question['text']);
        $this->assertIdentical('This is a test question', $question['taglessText']);
        $this->assertIdentical('Some agent text', $question['agentText']);
        $this->assertIdentical(1, $question['type']);
        $this->assertTrue(is_array($question['responses']));
        $this->assertIdentical(count($question['responses']), 2);
        $this->assertTrue(array_key_exists('newFormToken', $guideArray));
        $this->assertNotNull($guideArray['newFormToken']);

        $response = $question['responses'][0];
        $this->assertIdentical($response['responseID'], 1);
        $this->assertIdentical($response['responseText'], 'Do something');
        $this->assertIdentical($response['text'], 'Yes');
        $this->assertIdentical($response['type'], 8);
        $this->assertIdentical($response['value'], 1);
        $this->assertIdentical($response['parentQuestionID'], 1);
        $this->assertIdentical($response['childQuestionID'], 2);
        $this->assertFalse(isset($response['childGuideID']));

        $response = $question['responses'][1];
        $this->assertIdentical($response['responseID'], 2);
        $this->assertIdentical($response['childGuideID'], 2);
        $this->assertFalse(isset($response['childQuestionID']));

        $question = $guideArray['questions'][1];
        $this->assertIdentical(2, $question['questionID']);
        $this->assertTrue(is_array($question['nameValuePairs']));
        $this->assertIdentical(count($question['nameValuePairs']), 2);
        $this->assertIdentical($question['nameValuePairs']['key1'], 'value1');
        $this->assertIdentical($question['nameValuePairs']['key2'], 'value2');
    }
}
