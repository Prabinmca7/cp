<?php

namespace RightNow\Models;

use RightNow\Api,
    RightNow\Utils\Framework,
    RightNow\Internal\Sql\Polling as Sql;

require_once CORE_FILES . 'compatibility/Internal/Sql/Polling.php';

/**
 * Retrieves questions for displaying polling data as well as submitting a poll question. Also allows retrieval of poll results.
 */
class Polling extends Base {
    const RESULTS_DECIMAL_PLACES = 2;
    const RESULTS_CACHE_TTL = 60;
    const RESULTS_CACHE_THRESHOLD = 500;

    /**
     * Returns all survey data necessary to create a poll
     *
     * @param int $surveyID The id of the polling survey
     * @return array Survey data
     */
    public function getSurveyData($surveyID) {
        if (!Framework::isValidID($surveyID)) {
            return $this->getResponseObject(null, null, "Invalid Survey ID: '$surveyID'");
        }

        $cacheKey = "Polling$surveyID";
        if (!$response = Framework::checkCache($cacheKey)) {
            $data = array();
            list($data['flow_id'],
                 $data['survey_disabled'],
                 $data['multi_submit'],
                 $data['survey_type'],
                 $data['expiration_date'],
                 $data['survey_intf_id'],
                 $data['doc_id'],
                 $designXml) = array_pad(Sql::getResultsBySurvey($surveyID), 8, null);

            // parse the design xml for options
            $parser = xml_parser_create();
            xml_parse_into_struct($parser, isset($designXml) ? $designXml : '', $values, $index);
            xml_parser_free($parser);
            $optionsIndex = isset($index['OPTIONS']) && is_array($index['OPTIONS']) && !empty($index['OPTIONS']) ? $index['OPTIONS'][0] : null;
            $attributeValueArray = (isset($values[$optionsIndex]) && isset($values[$optionsIndex]['attributes'])) ? $values[$optionsIndex]['attributes'] : null;

            $data['title'] = isset($attributeValueArray['POLLINGTITLE']) ? $attributeValueArray['POLLINGTITLE'] : null;
            $data['show_results_link'] = isset($attributeValueArray['POLLINGSHOWRESULTSLINK']) ? $attributeValueArray['POLLINGSHOWRESULTSLINK'] === 'true' : false;           
            $data['show_total_votes'] = isset($attributeValueArray['POLLINGSHOWTOTALVOTES']) ? $attributeValueArray['POLLINGSHOWTOTALVOTES'] === 'true' : false;
            $data['show_chart'] = isset($attributeValueArray['POLLINGSHOWCHART']) ? $attributeValueArray['POLLINGSHOWCHART'] === 'true' : false;

            $data['submit_button_label'] = \RightNow\Utils\Config::getMessage(SUBMIT_CMD);
            $data['view_results_label'] = \RightNow\Utils\Config::getMessage(VIEW_RESULTS_CMD);
            $data['ok_button_label'] = \RightNow\Utils\Config::getMessage(OK_CMD);
            $data['turn_text'] = \RightNow\Utils\Config::getMessage(THANK_YOU_PARTICIPATING_POLL_MSG);
            $data['total_votes_label'] = \RightNow\Utils\Config::getMessage(TOTAL_VOTES_LBL) . " ";

            $response = $this->getResponseObject($data, 'is_array');
            Framework::setCache($cacheKey, $response);
        }

        return $response;
    }

    /**
     * Returns all question data necessary to create a poll
     *
     * @param int $surveyID The id of the polling survey
     * @param int $questionID The id of the question requested
     * @param bool $syndicated True if the question is for a syndicated widget
     *
     * @return array The survey data as an array.
     */
    public function getPollQuestion($surveyID, $questionID, $syndicated) {
        return $this->getQuestion($surveyID, $questionID, false, $syndicated);
    }

    /**
     * Returns question data necessary to create a poll preview
     *
     * @param int $surveyID The id of the polling survey
     * @param int $questionID The id of the question requested
     * @param bool $syndicated True if the question is for a syndicated widget
     *
     * @return array The survey data as an array.
     */
    public function getPreviewQuestion($surveyID, $questionID, $syndicated) {
        return $this->getQuestion($surveyID, $questionID, true, $syndicated);
    }

    /**
     * Returns the results of a polling survey via the associated flow and question
     *
     * @param int $flowID The id of the polling survey's associated flow
     * @param int $questionID The id of the question requested
     * @param bool $testMode If set to true then fake data is returned
     * @param bool $useMemcache If set to false then memcache will not be used to get the results
     * @return array Poll result data
     */
    public function getPollResults($flowID, $questionID, $testMode = false, $useMemcache = true) {
        if ($testMode) {
            return $this->getResponseObject($this->getQuestionResultsFromDatabase($flowID, $questionID, true), 'is_array');
        }

        $results = null;
        if ($useMemcache) {
            $memcacheKey = "$flowID-$questionID-" . Api::intf_id();
            $results = $this->memcacheGet($memcacheKey);
        }

        if (!is_array($results)) {
            $results = $this->getQuestionResultsFromDatabase($flowID, $questionID, false);
        }

        if ($useMemcache && is_array($results) && intval($results['total']) > self::RESULTS_CACHE_THRESHOLD) {
            $this->memcacheSet($memcacheKey, $results);
        }

        return $this->getResponseObject($results, 'is_array');
    }

    /**
     * Returns the list of all questions (questionID, questionName) associated to this flow
     *
     * @param int $flowID The id of the polling survey's associated flow
     * @return array An array consisting of a (questionID, questionName) object for each question
     */
    public function getResultsPageQuestionList($flowID) {
        return $this->getResponseObject(Sql::getResultsByFlow($flowID), 'is_array');
    }

    /**
     * Submits a polling survey
     *
     * @param int $flowID The id of the polling survey's associated flow
     * @param int $docID The id of the polling survey's associated document
     * @param int $questionID The id of the question being submitted
     * @param string $resultString JSON encoded results to the poll
     * @param int $questionType The type of the question
     * @param bool $syndicated True if the question is for a syndicated widget
     * @param int $src The ID of an object to associate the results with (i_id, chat_id, etc)
     * @param int $tbl The TBL define of the object to associate the results with (VTBL_INCIDENTS, TBL_CHATS, etc)
     * @return bool True if submission was successful
     */
    public function submitPoll($flowID, $docID, $questionID, $resultString, $questionType, $syndicated, $src = 0, $tbl = 0) {
        $questionData = $questionOrder = $typeData = array();

        foreach (json_decode($resultString) as $result) {
            $response = $result->response;
            $responseID = $result->id;
            if (\RightNow\Utils\Text::stringContains($responseID, '_r_')) {
                $id = str_replace(array('q_', '_r'), '', $responseID);
                $questionData[$id] = $questionOrder[$id] = $response;
                $typeData[str_replace('q_', '', $responseID)] = $questionType;
            }
            else if (\RightNow\Utils\Text::stringContains($responseID, 'other') === false) {
                if (strlen(isset($questionData[$questionID]) ? $questionData[$questionID] : '') === 0) {
                    $questionData[$questionID] = $questionOrder[$questionID] = $response;
                }
                else {
                    $questionData[$questionID] = isset($questionData[$questionID]) ? "{$questionData[$questionID]};$response" : $response;
                    $questionOrder[$questionID] = isset($questionOrder[$questionID]) ? "{$questionOrder[$questionID]};$response" : $response;
                }
                $typeData[$questionID] = $questionType;
            }
            else if (strlen($response) > 0) {
                $questionData[$questionID] = "{$questionData[$questionID]};_other_$response";
                $questionOrder[$questionID] = "{$questionOrder[$questionID]};_other_$response";
            }
        }

        // make explicit array to prevent opcode-related failures
        $arguments = array('flowID' => $flowID,
                           'docID' => $docID,
                           'syndicated' => $syndicated,
                           'typeData' => $typeData,
                           'questionData' => $questionData,
                           'questionOrder' => $questionOrder,
                           'questionID' => $questionID,
                           'src' => $src,
                           'tbl' => $tbl);
        return $this->updatePollingStats($arguments);
    }

    /**
     * Update polling stats via a dqa insert.
     *
     * @param array $data An associative array containing the necessary data.
     * @return bool True upon success
     */
    private function updatePollingStats(array $data) {
        $surveyData = array(
            'score' => 0,
            'last_page_num' => 1,
            'flags' => 0,
            'question_count' => 1,
            'survey_type' => SURVEY_TYPE_POLLING,
        );

        $src = $data['src'];
        $tbl = $data['tbl'];
        if ($src > 0 && $tbl > 0) {
            $surveyData['source'] = $tbl;
            $surveyData['source_id'] = $src;
        }

        $contactID = Framework::isLoggedIn() ? $this->CI->session->getProfileData('contactID') : 0;
        $pairdata = array('source_upd' => array('lvl_id1' => SRC1_FLOW, 'lvl_id2' => SRC2_FLOW_SURVEY), 'c_id' => $contactID);

        Api::flow_rules($contactID, $data['flowID'], 2, true, $data['docID'], 0, $pairdata,
            $surveyData, $data['questionData'], $data['questionOrder'], $data['typeData'], 0);

        Api::dqa_insert(DQA_POLLING_STATS, array(
            'question_id' => intval($data['questionID']),
            'submissions' => 1,
            'flow_id' => intval($data['flowID']),
            'ts' => time(),
            'widget_type' => $data['syndicated'] ? WIDGET_TYPE_SPW : WIDGET_TYPE_PW,
        ));

        return true;
    }

    /**
     * Returns question data necessary to create a poll
     *
     * @param int $surveyID The id of the polling survey
     * @param int $questionID The id of the question requested
     * @param bool $isAdmin Specify as true to obtain "preview" question.
     * @param bool $syndicated True if the question is for a syndicated widget
     * @return array The array of survey data.
     */
    private function getQuestion($surveyID, $questionID, $isAdmin, $syndicated) {
        $surveyData = $this->getSurveyData($surveyID);
        $result = $surveyData->result;

        if (($flowID = $surveyData->result['flow_id']) && ($xml = Api::ma_serve_poll_get($surveyID, $flowID, $questionID, $isAdmin, $syndicated))) {
            $result['question_id'] = $this->getStringFromXml($xml, 'rn:polling_question_id');
            $result['question_type'] = $this->getStringFromXml($xml, 'rn:polling_question_type');
            $result['element_type'] = $this->getStringFromXml($xml, 'rn:polling_element_type');
            $result['question'] = $this->getStringFromXml($xml, 'rn:polling_question');
            $result['answer_area'] = $this->getStringFromXml($xml, 'rn:polling_answer_area');
            $result['max_responses_met'] = ($this->getStringFromXml($xml, 'rn:polling_max_responses_met') === 'true');
            $result['expire_msg'] = $this->getStringFromXml($xml, 'rn:polling_expire_msg');
        }

        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * Parses xml string for the data contained between start and end $tag
     *
     * @param string $xml The xml haystack
     * @param string $tag The opening and closing tag (minus </>).
     * @return string Content between the start and end $tag
     */
    private function getStringFromXml($xml, $tag) {
        if(($position = strpos($xml, "<{$tag}>")) !== false) {
            $position += strlen("<{$tag}>");
            $length = strpos($xml, "</{$tag}>", $position) - $position;
            return substr($xml, $position, $length);
        }
        return '';
    }

    /**
     * Returns the results of a polling survey from the database
     *
     * @param int $flowID The id of the polling survey's associated flow
     * @param int $questionID The id of the question requested
     * @param bool $testMode If set to true then fake data is returned
     * @return array An array containing all of the question results
     */
    private function getQuestionResultsFromDatabase($flowID, $questionID, $testMode = false) {
        $data = Sql::getResultsByQuestion($questionID, $flowID);
        $total = $data['total'];
        $questionName = $data['question_name'];
        $results = $data['results'];

        if ($testMode) {
            $total = 1234;
            // crazy math is to get a nice upward trending curve
            $graphConstant = 2;
            $totalChoices = count($results);
            $totalPercentage = 0;
            $i = 1;
            foreach($results as &$result) {
                $columnHeight = ((100 / pow($graphConstant, $totalChoices)) * (pow($graphConstant, $i) - pow($graphConstant, ($i - 1))));
                $percentTotal = number_format($columnHeight, self::RESULTS_DECIMAL_PLACES, '.', '');
                $result['percent_total'] = $i === $totalChoices ? 100 - $totalPercentage : $percentTotal;
                $totalPercentage += $percentTotal;
                $i++;
            }
        }
        else {
            $totalExists = ($total > 0);
            foreach($results as &$result)
                $result['percent_total'] = ($totalExists) ? number_format((100 * intval($result['count']) / $total), self::RESULTS_DECIMAL_PLACES, '.', '') : 0;
        }

        return array('question_name' => $questionName, 'total' => $total, 'question_results' => json_encode($results));
    }

    /**
     * Returns the results of a polling survey from memcache
     *
     * @param string $key The memcache key
     * @return array|null A json decoded array from memcache.
     */
    private function memcacheGet($key) {
        try {
            $data = Api::memcache_value_fetch(MEMCACHE_TYPE_POLL_RESULTS, Api::memcache_value_deferred_get(MEMCACHE_TYPE_POLL_RESULTS, array($key)));
            return json_decode($data[$key], true);
        }
        catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Stores the json encoded results of a polling survey in memcache
     *
     * @param string $key The memcache key
     * @param string $value The value to set
     * @return null
     */
    private function memcacheSet($key, $value) {
        try {
            Api::memcache_value_set(MEMCACHE_TYPE_POLL_RESULTS, $key, json_encode($value), self::RESULTS_CACHE_TTL);
        }
        catch (\Exception $e) {
            return null;
        }
    }
}
