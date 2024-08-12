<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect;
class AnswerTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Answer';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\Answer();
    }

    function testInvalidGet() {
        $response = $this->model->get('sdf');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get(null);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get("abc123");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get(456334);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
    }

    function testValidGet() {
        $response = $this->model->get(1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $answer = $response->result;
        $this->assertIsA($answer, KF_NAMESPACE_PREFIX . '\AnswerContent');
        $this->assertSame(1, $answer->ID);
        $this->assertTrue(is_string($answer->Summary));

        $response = $this->model->get("1");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $answer = $response->result;
        $this->assertIsA($answer, KF_NAMESPACE_PREFIX . '\AnswerContent');
        $this->assertSame(1, $answer->ID);
        $this->assertTrue(is_string($answer->Summary));
    }

    function testGetWithRelatedParameter(){
        $this->CI->router->segments[] = 'related';
        $this->CI->router->segments[] = '1';
        $parameterSegment = $this->CI->config->item('parm_segment');
        $this->CI->config->set_item('parm_segment', 1);

        $response = $this->model->get("1");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $answer = $response->result;
        $this->assertIsA($answer, KF_NAMESPACE_PREFIX . '\AnswerContent');
        $this->assertSame(1, $answer->ID);
        $this->assertTrue(is_string($answer->Summary));

        $this->CI->config->set_item('parm_segment', $parameterSegment);
    }

    function testExists(){
        $this->assertTrue($this->model->exists(1));
        $this->assertTrue($this->model->exists('1'));
        $this->assertTrue($this->model->exists(48));
        $this->assertTrue($this->model->exists('48'));

        $this->assertFalse($this->model->exists(0));
        $this->assertFalse($this->model->exists(-1));
        $this->assertFalse($this->model->exists(99));
        $this->assertFalse($this->model->exists("87"));
        $this->assertFalse($this->model->exists("5alive"));
        $this->assertFalse($this->model->exists("asdf"));
        $this->assertFalse($this->model->exists("3or4"));
    }

    function testEndUserVisible(){
        $this->assertTrue($this->model->isEndUserVisible(1));
        $this->assertTrue($this->model->isEndUserVisible('1'));
        $this->assertTrue($this->model->isEndUserVisible(48));
        $this->assertTrue($this->model->isEndUserVisible('48'));

        $this->assertFalse($this->model->isEndUserVisible(0));
        $this->assertFalse($this->model->isEndUserVisible(-1));
        $this->assertFalse($this->model->isEndUserVisible(99));
        $this->assertFalse($this->model->isEndUserVisible("87"));
        $this->assertFalse($this->model->isEndUserVisible("5alive"));
        $this->assertFalse($this->model->isEndUserVisible("asdf"));
        $this->assertFalse($this->model->isEndUserVisible("3or4"));
    }

    function testIsPrivate() {
        $this->assertFalse($this->model->isPrivate(1));
        $this->assertFalse($this->model->isPrivate('1'));
        $this->assertFalse($this->model->isPrivate(48));
        $this->assertFalse($this->model->isPrivate('48'));

        $answer = Connect\Answer::fetch(1);
        $answer->StatusWithType->Status->ID = ANS_PRIVATE;
        $answer->save();

        $this->assertTrue($this->model->isPrivate(1));
        $this->assertTrue($this->model->isPrivate('1'));

        $answer->StatusWithType->Status->ID = ANS_PUBLIC;
        $answer->save();
    }

    function testGetPopular(){
        $response = $this->model->getPopular();
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));

        //Limit tests
        $response = $this->model->getPopular(5);
        $this->assertResponseObject($response);
        $this->assertIdentical(5, count($response->result));
        $response = $this->model->getPopular(1);
        $this->assertResponseObject($response);
        $this->assertIdentical(1, count($response->result));
        $response = $this->model->getPopular(10);
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));
        $response = $this->model->getPopular(25);
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));
        $response = $this->model->getPopular(-10);
        $this->assertResponseObject($response);
        $this->assertIdentical(1, count($response->result));

        //Product/Category tests
        $response = $this->model->getPopular(10, 1);
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));
        $response = $this->model->getPopular(10, "1");
        $this->assertResponseObject($response);
        $this->assertIdentical(10, count($response->result));
        $response = $this->model->getPopular(10, null, 161);
        $this->assertResponseObject($response);
        $this->assertIdentical(9, count($response->result));
        $response = $this->model->getPopular(10, null, "161");
        $this->assertResponseObject($response);
        $this->assertIdentical(9, count($response->result));
        $response = $this->model->getPopular(10, 2, 161);
        $this->assertResponseObject($response);
        $this->assertIdentical(6, count($response->result));

        //Invalid Product/Category
        $response = $this->model->getPopular(10, "product ID", "category ID");
        $this->assertResponseObject($response, 'is_null', 1);
        $response = $this->model->getPopular(10, 300);
        $this->assertResponseObject($response, 'is_null', 1);
        $response = $this->model->getPopular(10, null, 300);
        $this->assertResponseObject($response, 'is_null', 1);
        $response = $this->model->getPopular(10, 132);
        $this->assertResponseObject($response, 'is_null', 0, 1);
        $response = $this->model->getPopular(10, 132, 161);
        $this->assertResponseObject($response, 'is_null', 0, 1);
    }

    function testGetRelatedAnswers() {
        $response = $this->model->getRelatedAnswers(48, 5);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertIsA($response->result, 'RightNow\Connect\Knowledge\v1\SummaryContentArray');
        $this->assertIdentical(true, count($response->result) <= 5);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        //Test when no results are returned
        $response = $this->model->getRelatedAnswers(1, 5);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));

        //Test when bad answerID is provided
        $response = $this->model->getRelatedAnswers(123, 5);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));

        //Test when limit is 0, result count will be <=10
        $response = $this->model->getRelatedAnswers(48, 0);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertIsA($response->result, 'RightNow\Connect\Knowledge\v1\SummaryContentArray');
        $this->assertIdentical(true, count($response->result) <= 10);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        //Test when limit >10, result count will be <=10
        $response = $this->model->getRelatedAnswers(48, 15);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertIsA($response->result, 'RightNow\Connect\Knowledge\v1\SummaryContentArray');
        $this->assertIdentical(true, count($response->result) <= 10);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testGetPreviousAnswers(){
        $this->setMockSession();
        $this->CI->session->setReturnValue('getSessionData', $this->createPreviousAnswerArray(array(1, "55", 60, "66", 63, "52", 56, 63, 56)));
        $this->CI->session->returnsAt(0, 'getSessionData', null);
        $this->CI->session->returnsAt(1, 'getSessionData', $this->createPreviousAnswerArray(array('one', array(), -1, false, null)));
        $this->CI->session->returnsAt(2, 'getSessionData', $this->createPreviousAnswerArray(array(1)));
        $this->CI->session->returnsAt(3, 'getSessionData', $this->createPreviousAnswerArray(array(1, 1, 1, 1, 1, 1, 1)));
        $this->CI->session->returnsAt(4, 'getSessionData', $this->createPreviousAnswerArray(array(1, 56, 70, 66, 63, 52)));
        $this->CI->session->returnsAt(5, 'getSessionData', $this->createPreviousAnswerArray(array(70)));
        $this->CI->session->setReturnValue('canSetSessionCookies', true);

        //Test invalid answer ID
        $response = $this->model->getPreviousAnswers("asdf", 5, 200);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));

        //Test when no results are returned
        $response = $this->model->getPreviousAnswers(1, 5, 200);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));

        //Test invalid values in Session for answer IDs
        $response = $this->model->getPreviousAnswers(1, 5, 200);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse(is_array($response->result));
        $this->assertIdentical(0, count($response->result));
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));

        //Test duplicate answer IDs
        $response = $this->model->getPreviousAnswers(1, 5, 200);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));

        $response = $this->model->getPreviousAnswers(1, 5, 200);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));

        // @@@ 131209-000116
        //Test answer without access - 70 has an access mask of 16 & should not be returned
        $response = $this->model->getPreviousAnswers(1, 5, 200);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(0, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));
        $this->assertIdentical(4, count($response->result));
        //Ensure answers with access are in the correct order
        $this->assertIdentical("52", $response->result[0][0]);
        $this->assertIdentical("63", $response->result[1][0]);
        $this->assertIdentical("66", $response->result[2][0]);
        $this->assertIdentical("56", $response->result[3][0]);

        //Test valid results
        $response = $this->model->getPreviousAnswers(1, 5, 200);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(0, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));
        $this->assertIdentical(5, count($response->result));
        //Ensure they are in the correct order
        $this->assertIdentical("56", $response->result[0][0]);
        $this->assertIdentical("63", $response->result[1][0]);
        $this->assertIdentical("52", $response->result[2][0]);
        $this->assertIdentical("66", $response->result[3][0]);
        $this->assertIdentical("60", $response->result[4][0]);

        $response = $this->model->getPreviousAnswers(60, 5, 10);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(0, count($response->errors));
        $this->assertIdentical(0, count($response->warnings));
        $this->assertIdentical(5, count($response->result));
        //Ensure they are in the correct order
        $this->assertIdentical("56", $response->result[0][0]);
        $this->assertIdentical("63", $response->result[1][0]);
        $this->assertIdentical("52", $response->result[2][0]);
        $this->assertIdentical("66", $response->result[3][0]);
        $this->assertIdentical("55", $response->result[4][0]);
        foreach($response->result as $result){
            //Allow for a little padding since we try not to truncate words
            $this->assertTrue(strlen($result[1]) <= 13);
        }

        $this->unsetMockSession();
    }

    function createPreviousAnswerArray($vals = array()) {
        if($vals === null)
            $prevAnswerArray[] = array('a_id' => null);
        foreach($vals as $val) {
            $prevAnswerArray[] = array('a_id' => $val);
        }
        return $prevAnswerArray;
    }

    function testGetAnswerSummary() {
        $response = $this->model->getAnswerSummary(1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');

        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertSame(1, count($response->result));

        $answer = $response->result[1];
        $this->assertSame("1", $answer['ID']);
        $this->assertIsA($answer['Summary'], 'string');
        $this->assertIsA($answer['LanguageID'], 'int');
        $this->assertIsA($answer['StatusType'], 'int');

        $response = $this->model->getAnswerSummary("1");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');

        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertSame(1, count($response->result));

        $answer = $response->result[1];
        $this->assertSame("1", $answer['ID']);
        $this->assertIsA($answer['Summary'], 'string');
        $this->assertIsA($answer['LanguageID'], 'int');
        $this->assertIsA($answer['StatusType'], 'int');

        //Test and make sure that this 'platinum' access level answer is not visible
        $response = $this->model->getAnswerSummary(68);
        $this->assertSame(array(), $response->result);

        //Test and make sure that the method is returning result even for invisible access level when validateAccessLevel is set as false
        $response = $this->model->getAnswerSummary(68, true, false);
        $this->assertNotEqual(0, count($response->result));

        $response = $this->model->getAnswerSummary(array(52, 57, 61));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');

        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(3, count($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $firstAnswer = $response->result['52'];
        $this->assertSame("52", $firstAnswer['ID']);
        $this->assertIsA($answer['Summary'], 'string');
        $this->assertIsA($answer['LanguageID'], 'int');
        $this->assertIsA($answer['StatusType'], 'int');
        $secondAnswer = $response->result['57'];
        $this->assertSame("57", $secondAnswer['ID']);
        $this->assertIsA($answer['Summary'], 'string');
        $this->assertIsA($answer['LanguageID'], 'int');
        $this->assertIsA($answer['StatusType'], 'int');
        $thirdAnswer = $response->result['61'];
        $this->assertSame("61", $thirdAnswer['ID']);
        $this->assertIsA($answer['Summary'], 'string');
        $this->assertIsA($answer['LanguageID'], 'int');
        $this->assertIsA($answer['StatusType'], 'int');

        // @@@ QA 130524-000093 Fix to show answer with review status in guided assistance.
        $answerObject = Connect\Answer::fetch(2);
        $answerInitialStatus = $answerObject->StatusWithType->Status->ID;
        $answerObject->StatusWithType->Status->ID = ANS_REVIEW;
        $answerObject->save();
        $response = $this->model->getAnswerSummary(2);
        $answer = $response->result[2];
        $this->assertSame(STATUS_TYPE_PRIVATE, $answer['StatusType']);
        $answerObject->StatusWithType->Status->ID = $answerInitialStatus;
        $answerObject->save();

        //Error conditions
        $response = $this->model->getAnswerSummary(array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getAnswerSummary('abc');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getAnswerSummary(-1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getAnswerSummary(99);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(0, count($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testEmailToFriend() {
        // invalid form token
        $return = $this->model->emailToFriend('b.b.invalid@invalid.invalid', 'Banana', 'banana@invalid.invalid', 52);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertTrue($return->result);

        // invalid to address
        $return = $this->model->emailToFriend('b.b.invalid', 'Banana', 'banana@invalid.invalid', 52);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);

        // invalid sender name
        $return = $this->model->emailToFriend('b.b.invalid@invalid.invalid', '', 'banana@invalid.invalid', 52);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);

        // invalid from address
        $return = $this->model->emailToFriend('b.b.invalid@invalid.invalid', 'Banana', 'banana', 52);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);

        // invalid answer id
        $return = $this->model->emailToFriend('b.b.invalid@invalid.invalid', 'Banana', 'banana@invalid.invalid', "2323212");
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);

        // abuse
        $this->setIsAbuse();
        $expected = \RightNow\Utils\Config::getMessage(REQUEST_PERFORMED_SITE_ABUSIVE_MSG);
        $response = $this->model->emailToFriend('b.b.invalid@invalid.invalid', 'Banana', 'banana@invalid.invalid', "52");
        $this->assertIsA($response->errors, 'array');
        $this->clearIsAbuse();

        // valid
        $this->setMockSession();
        $return = $this->model->emailToFriend('  b.b.invalid@invalid.invalid  ', 'Banana', 'banana@invalid.invalid', "52");
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        //The result is the value the API returned to us. We don't really care if that succeeded since it doesn't help us test our code and
        //the CC sites seem to have a poor track record of setting things up correctly.
        $this->assertTrue(is_bool($return->result));

        $this->unsetMockSession();
    }

    function testRate(){
        $this->assertTrue($this->model->rate(1, 1, 1));
        $this->assertTrue($this->model->rate("1", "1", "1"));
        $this->assertTrue($this->model->rate(1, 0, 5));
        $this->assertTrue($this->model->rate(1, -5, 50));
        $this->assertTrue($this->model->rate(1, "a", "b"));

        $this->assertFalse($this->model->rate("my answer", 1, 2));
        $this->assertFalse($this->model->rate(900, 1, 2));
        $this->assertFalse($this->model->rate("900", 1, 2));
    }

    function testExpandAnswerFields(){
        $expandFields = $this->getMethod('expandAnswerFields');

        $this->assertIdentical(1, $expandFields(1));
        $this->assertIdentical(true, $expandFields(true));
        $this->assertIdentical(null, $expandFields(null));

        $answer = array(
            'Question' => "Answer Question with &lt; &gt; &amp; and < and >",
            'Solution' => 'Answer Solution with <rn:answer_section title="Platinum" access="4">Unless you can see this platinum conditional section.</rn:answer_section>',
            'Summary' => "Answer Summary with < and > as well as &, ' and \""
        );

        $response = $expandFields($answer, false);
        $this->assertIdentical("Answer Question with &lt; &gt; &amp; and < and >", $response['Question']);
        $this->assertIdentical("Answer Solution with ", $response['Solution']);
        $this->assertIdentical("Answer Summary with < and > as well as &, ' and \"", $response['Summary']);

        $response = $expandFields($answer);
        $this->assertIdentical("Answer Question with &lt; &gt; &amp; and < and >", $response['Question']);
        $this->assertIdentical("Answer Solution with ", $response['Solution']);
        $this->assertIdentical("Answer Summary with &lt; and &gt; as well as &, ' and \"", $response['Summary']);

        $response = $expandFields((object)$answer, false);
        $this->assertIdentical("Answer Question with &lt; &gt; &amp; and < and >", $response->Question);
        $this->assertIdentical("Answer Solution with ", $response->Solution);
        $this->assertIdentical("Answer Summary with < and > as well as &, ' and \"", $response->Summary);

        $response = $expandFields((object)$answer);
        $this->assertIdentical("Answer Question with &lt; &gt; &amp; and < and >", $response->Question);
        $this->assertIdentical("Answer Solution with ", $response->Solution);
        $this->assertIdentical("Answer Summary with &lt; and &gt; as well as &, ' and \"", $response->Summary);
    }

    function testEscapeSummary() {
        $escapeSummary = $this->getMethod('escapeSummary');
        $this->assertIdentical('`!@#$%^&*()_+{}|:"&lt;&gt;?`-=[]\;\',./', $escapeSummary('`!@#$%^&*()_+{}|:"<>?`-=[]\;\',./'));
        $this->assertIdentical('A rather ordinary summary', $escapeSummary('A rather ordinary summary'));
        $this->assertIdentical('&gt;', $escapeSummary('&gt;'));
        $this->assertIdentical('&lt;', $escapeSummary('&lt;'));
        $this->assertIdentical('&gt;', $escapeSummary('>'));
        $this->assertIdentical('&lt;', $escapeSummary('<'));
        $this->assertIdentical('&', $escapeSummary('&'));
        $this->assertIdentical('"', $escapeSummary('"'));
        $this->assertIdentical("'", $escapeSummary("'"));
        $this->assertIdentical('', $escapeSummary(''));
        $this->assertIdentical('', $escapeSummary(null));
        $this->assertIdentical('123', $escapeSummary(123));
    }

    function testBuildFromHeader() {
        $build = $this->getMethod('buildFromHeader');

        // Base
        $result = $build('', '');
        $this->assertSame('"" <>', $result);

        // Typical
        $email = 'bananas@placesheen.com';
        $result = $build('Deep Sea', $email);
        $this->assertSame('"Deep Sea" ' . "<$email>", $result);

        // Email touches bounds
        $email = str_repeat('a', 69) . '@a.com';
        $name = 'bananas';
        $result = $build($name, $email);
        $this->assertSame(75, \RightNow\Utils\Text::getMultibyteStringLength($result));
        $this->assertSame($email, $result);

        $email = str_repeat('a', 74) . '@a.com';
        $result = $build($name, $email);
        $this->assertSame($email, $result);
        $this->assertSame(80, \RightNow\Utils\Text::getMultibyteStringLength($result));

        $email = str_repeat('a', 66) . '@a.com';
        $result = $build($name, $email);
        $this->assertSame('"ban" ' . "<$email>", $result);
        $this->assertSame(80, \RightNow\Utils\Text::getMultibyteStringLength($result));

        // Name touches bounds
        $email = 'bananas@placesheen.com';
        $name = str_repeat('益', 100);
        $result = $build($name, $email);
        $this->assertSame(80, \RightNow\Utils\Text::getMultibyteStringLength($result));
        $this->assertEndsWith($result, "<$email>");
        list($name,) = explode(' ', $result);
        $this->assertSame(55, \RightNow\Utils\Text::getMultibyteStringLength($name));
    }

    //@@@ QA 131020-000000
    function testGetFirstBottomMostProduct() {
        $getBottom = $this->getMethod('getFirstBottomMostProduct');

        // Answer ID 48 has prod with maximum levels defined
        $result = $getBottom(48)->result;
        $this->assertIdentical(array('ID' => "121"), $result);

        // Answer ID 1 has prod on first level defined
        $result = $getBottom(1)->result;
        $this->assertIdentical(array('ID' => "7"), $result);

        // Answer ID 20 has no product defined
        $result = $getBottom(20)->result;
        $this->assertIdentical(array(), $result);
    }

    //@@@ QA 131020-000000
    function testGetFirstBottomMostCategory() {
        $getBottom = $this->getMethod('getFirstBottomMostCategory');

        // Answer ID 48 has cat with maximum levels defined
        $result = $getBottom(48)->result;
        $this->assertIdentical(array('ID' => "127"), $result);

        // Answer ID 1 has cat on first level defined
        $result = $getBottom(1)->result;
        $this->assertIdentical(array('ID' => "68"), $result);

        // Answer ID 2 has no category defined
        $result = $getBottom(2)->result;
        $this->assertIdentical(array(), $result);
    }

    // This test is redundant to the above, which are helper classes for this private function. Creating test anyway.
    //@@@ QA 131020-000000
    function testGetFirstBottomMostProdCat() {
        $getBottom = $this->getMethod('getFirstBottomMostProdCat');

        // Test Answer ID 48; max levels prod and cat
        $result = $getBottom(48, HM_PRODUCTS)->result;
        $this->assertIdentical(array('ID' => "121"), $result);

        $result = $getBottom(48, HM_CATEGORIES)->result;
        $this->assertIdentical(array('ID' => "127"), $result);

        // Test Answer ID 1; shallow prod and cat
        $result = $getBottom(1, HM_PRODUCTS)->result;
        $this->assertIdentical(array('ID' => "7"), $result);

        $result = $getBottom(1, HM_CATEGORIES)->result;
        $this->assertIdentical(array('ID' => "68"), $result);

        // Answer ID 20 has no product defined
        $result = $getBottom(20, HM_PRODUCTS)->result;
        $this->assertIdentical(array(), $result);

        // Answer ID 2 has no category defined
        $result = $getBottom(2, HM_CATEGORIES)->result;
        $this->assertIdentical(array(), $result);
    }
}
