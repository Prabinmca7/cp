<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class PollingTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Polling';

    function __construct() {
        $this->CI = get_instance();
        $this->model = $this->CI->model('Polling');
        $this->surveyID = 1;
        $this->questionID = 1;
        $this->flowID = 1;
        $this->expected = array (
            'doc_id' => NULL,
            'survey_intf_id' => NULL,
            'expiration_date' => NULL,
            'survey_type' => NULL,
            'multi_submit' => NULL,
            'survey_disabled' => NULL,
            'flow_id' => NULL,
            'title' => NULL,
            'show_results_link' => false,
            'show_total_votes' => false,
            'show_chart' => false,
            'submit_button_label' => 'Submit',
            'view_results_label' => 'View Results',
            'ok_button_label' => 'OK',
            'turn_text' => 'Thank you for participating in this poll.',
            'total_votes_label' => 'Total Votes: ',
        );
        $this->expectedTestMode = array (
            'question_name' => 'Greatest Movie',
            'total' => 1234,
            'question_results' => '[{"count":"0","response":"Citizen Kane","percent_total":"0.00"},{"count":"0","response":"The Godfather","percent_total":"0.00"},{"count":"0","response":"The Godfather Part II","percent_total":"0.00"},{"count":"0","response":"The Graduate","percent_total":"0.00"},{"count":"0","response":"Star Wars: A New Hope","percent_total":"0.00"},{"count":"0","response":"Star Wars: Empire Strikes Back","percent_total":"0.01"},{"count":"0","response":"Star Wars: The Return of the Jedi","percent_total":"0.01"},{"count":"0","response":"Raiders of the Lost Ark","percent_total":"0.02"},{"count":"0","response":"2001: A Space Odyssey","percent_total":"0.05"},{"count":"0","response":"E.T.","percent_total":"0.10"},{"count":"0","response":"Apacolypse Now","percent_total":"0.20"},{"count":"0","response":"LOTR: Return of the King","percent_total":"0.39"},{"count":"0","response":"The Usual Suspects","percent_total":"0.78"},{"count":"0","response":"Pulp Fiction","percent_total":"1.56"},{"count":"0","response":"High Plains Drifter","percent_total":"3.13"},{"count":"0","response":"Dirty Harry","percent_total":"6.25"},{"count":"0","response":"Unforgiven","percent_total":"12.50"},{"count":"0","response":"The Good, the Bad and the Ugly","percent_total":"25.00"},{"count":"0","response":"Other","percent_total":50}]',
        );
        $this->expectedNonTestMode = array (
            'question_name' => 'Greatest Movie',
            'total' => 0,
            'question_results' => '[{"count":"0","response":"Citizen Kane","percent_total":0},{"count":"0","response":"The Godfather","percent_total":0},{"count":"0","response":"The Godfather Part II","percent_total":0},{"count":"0","response":"The Graduate","percent_total":0},{"count":"0","response":"Star Wars: A New Hope","percent_total":0},{"count":"0","response":"Star Wars: Empire Strikes Back","percent_total":0},{"count":"0","response":"Star Wars: The Return of the Jedi","percent_total":0},{"count":"0","response":"Raiders of the Lost Ark","percent_total":0},{"count":"0","response":"2001: A Space Odyssey","percent_total":0},{"count":"0","response":"E.T.","percent_total":0},{"count":"0","response":"Apacolypse Now","percent_total":0},{"count":"0","response":"LOTR: Return of the King","percent_total":0},{"count":"0","response":"The Usual Suspects","percent_total":0},{"count":"0","response":"Pulp Fiction","percent_total":0},{"count":"0","response":"High Plains Drifter","percent_total":0},{"count":"0","response":"Dirty Harry","percent_total":0},{"count":"0","response":"Unforgiven","percent_total":0},{"count":"0","response":"The Good, the Bad and the Ugly","percent_total":0},{"count":"0","response":"Other","percent_total":0}]',
          );
    }

    function responseCheck($response, $expectedReturn = 'array') {
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, $expectedReturn);
        $this->assertIsA($response->errors, 'array');
        $this->assertIsA($response->warnings, 'array');
        $this->assertIdentical(0, count($response->errors), var_export($response->errors, true));
        $this->assertIdentical(0, count($response->warnings), var_export($response->warnings, true));
    }

    function testgetSurveyData() {
        $response = $this->model->getSurveyData($this->surveyID);
        $this->responseCheck($response);
        $this->assertIdentical($response->result, $this->expected);

        $response = $this->model->getSurveyData(0);
        $this->assertNull($response->result);
        $errors = $response->errors[0];
        $this->assertIdentical("Invalid Survey ID: '0'", (string) $response->errors[0]);
    }

    function testGetPollQuestion() {
        $response = $this->model->getPollQuestion($this->surveyID, $this->questionID, true);
        $this->responseCheck($response);
        $this->AssertIdentical($response->result, $this->expected);
    }

    function testGetPreviewQuestion() {
        $response = $this->model->getPreviewQuestion($this->surveyID, $this->questionID, true);
        $this->responseCheck($response);
        $this->AssertIdentical($response->result, $this->expected);
    }

    function testGetPollResults() {
        $testMode = true;
        $useMemcache = true;
        $response = $this->model->getPollResults($this->surveyID, $this->questionID, $testMode, $useMemcache);
        $this->responseCheck($response);
        $this->AssertIdentical($response->result, $this->expectedTestMode);

        $testMode = false;
        $useMemcache = true;
        $response = $this->model->getPollResults($this->surveyID, $this->questionID, $testMode, $useMemcache);
        $this->responseCheck($response);
        $this->AssertIdentical($response->result, $this->expectedNonTestMode);
    }

    function testgetResultsPageQuestionList() {
        $response = $this->model->getResultsPageQuestionList($this->flowID);
        $this->responseCheck($response);
        $this->AssertIdentical($response->result, array());
    }

    function testGetQuestionResultsFromDatabase() {
        $invoker = $this->getMethod('getQuestionResultsFromDatabase');
        $testMode = true;
        $actual = $invoker($this->surveyID, $this->questionID, $testMode);
        $this->AssertIdentical($actual, $this->expectedTestMode);

        $testMode = false;
        $actual = $invoker($this->surveyID, $this->questionID, $testMode);
        $this->AssertIdentical($actual, $this->expectedNonTestMode);
    }

    function testGetStringFromXml() {
        $getStringFromXml = $this->getMethod('getStringFromXml');
        $expected = 'hiya';
        $tag = 'rn:polling_question_id';
        $this->AssertIdentical($expected, $getStringFromXml("<{$tag}>{$expected}</{$tag}>", $tag));
        $this->AssertIdentical('', $getStringFromXml("<blah>{$expected}</blah>", $tag));
    }

    function testMemcacheFunctions() {
        $setter = $this->getMethod('memcacheSet');
        $getter = $this->getMethod('memcacheGet');
        $input = array('one' => 1, 'two' => 2, 'three' => 3);
        $key = '1-2-3';
        $setter($key, $input);
        $output = $getter($key);
        $this->AssertIdentical($input, $output);
        $this->AssertNull($getter('9-8-7'));
    }
}
