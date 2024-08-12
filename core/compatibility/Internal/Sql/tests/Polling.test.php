<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Sql\Polling as Sql;

class PollingSqlTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Sql\Polling';

    function __construct() {
        $this->surveyID = 1;
        $this->flowID = 1;
        $this->questionID = 1;
        $this->surveyType = SURVEY_TYPE_POLLING;
        $this->questionType = QUESTION_TYPE_CHOICE;
    }

    function testGetResultsBySurvey() {
        $methodInvoker = $this->getMethod('getResultsBySurvey');
        $actual = $methodInvoker($this->surveyID, $this->surveyType);
        $this->assertIdentical($actual, array());
    }

    function testGetResultsByQuestion() {
        $expected = array(
            'results' => array(
                array('count' => '0', 'response' => 'Citizen Kane'),
                array('count' => '0', 'response' => 'The Godfather'),
                array('count' => '0', 'response' => 'The Godfather Part II'),
                array('count' => '0', 'response' => 'The Graduate'),
                array('count' => '0', 'response' => 'Star Wars: A New Hope'),
                array('count' => '0', 'response' => 'Star Wars: Empire Strikes Back'),
                array('count' => '0', 'response' => 'Star Wars: The Return of the Jedi'),
                array('count' => '0', 'response' => 'Raiders of the Lost Ark'),
                array('count' => '0', 'response' => '2001: A Space Odyssey'),
                array('count' => '0', 'response' => 'E.T.'),
                array('count' => '0', 'response' => 'Apacolypse Now'),
                array('count' => '0', 'response' => 'LOTR: Return of the King'),
                array('count' => '0', 'response' => 'The Usual Suspects'),
                array('count' => '0', 'response' => 'Pulp Fiction'),
                array('count' => '0', 'response' => 'High Plains Drifter'),
                array('count' => '0', 'response' => 'Dirty Harry'),
                array('count' => '0', 'response' => 'Unforgiven'),
                array('count' => '0', 'response' => 'The Good, the Bad and the Ugly'),
                array('count' => '0', 'response' => 'Other'),
            ),
            'total' => 0,
            'question_name' => 'Greatest Movie',
        );
        $methodInvoker = $this->getMethod('getResultsByQuestion');
        $actual = $methodInvoker($this->questionID, $this->flowID);
        $this->assertIdentical($actual, $expected);
    }

    function testGetResultsByFlow() {
        $methodInvoker = $this->getMethod('getResultsByFlow');
        $actual = $methodInvoker($this->flowID, $this->questionType);
        $this->assertIdentical($actual, array());
    }
}
