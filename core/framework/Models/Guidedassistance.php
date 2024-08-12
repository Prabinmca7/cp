<?php

namespace RightNow\Models;
use RightNow\Api;

require_once CORE_FILES . 'compatibility/Internal/Sql/Guidedassistance.php';

/**
 * Model to retrieve GuidedAssistant guides.
 */
class Guidedassistance extends Base {

    /**
     * Returns a GuidedAssistance object from the database based on the guide ID.
     * @param int $guideID The ID for the guide
     * @param int $languageID An alternate interface's language to use for the guide; if left unspecified, the current interface's language id is used
     * @return \RightNow\Libraries\GuidedAssistance The GuidedAssistance object with the specified guide ID
     */
    public function get($guideID, $languageID = null) {
        if(!$guideID || !is_int($guideID) || $guideID < 0)
            return $this->getResponseObject(null, null, 'GuideID must be not be null, less than zero, or of a non-integer type');
        if(!$languageID)
            $languageID = Api::lang_id(LANG_DIR);

        $cacheHandle = "guide$guideID:$languageID";
        $guide = \RightNow\Utils\Framework::checkCache($cacheHandle);
        if($guide !== null)
            return $this->getResponseObject($guide);

        $pairdata = array(
            'id' => intval($guideID),
            'sub_tbl' => array(
                'tbl_id1' => TBL_WF_SCRIPT_NODES,
                'tbl_id2' => TBL_WF_SCRIPT_EDGES,
                'tbl_id3' => TBL_WF_SCRIPT_EDGE2ANS,
                'tbl_id4' => TBL_LABELS,
                'tbl_id5' => TBL_WF_SCRIPT_NODE_DATA
            )
        );

        $gaApi = Api::decision_tree_get($pairdata);

        if($gaApi) {
            require_once CPCORE . 'Libraries/GuidedAssistance.php';

            $questions = array();
            $guide = new \RightNow\Libraries\GuidedAssistance($gaApi['dt_id'], $this->getLabel($gaApi['label'], $languageID));

            foreach($gaApi['questions'] as $question) {
                //create ea. question
                $tempQuestion = new \RightNow\Libraries\Question();
                $tempQuestion->questionID = $question['dt_question_id'];
                $tempQuestion->guideID = isset($question['id']) ? $question['id'] : null;
                $tempQuestion->text = $this->getLabel($question['label'], $languageID, true);
                $tempQuestion->taglessText = \RightNow\Utils\Text::escapeHtml(strip_tags($tempQuestion->text));
                $tempQuestion->agentText = $this->getLabel($question['help_text'], $languageID);
                $tempQuestion->type = $question['response_type'];
                $tempQuestion->name = isset($question['question_name']) ? $question['question_name'] : '';
                $guide->addQuestion($tempQuestion);
            }

            if(isset($gaApi['answers']) && is_array($gaApi['answers']) && count($gaApi['answers']) > 0) {
                //retrieve all answers before processing responses
                $answerIDs = array();
                foreach($gaApi['answers'] as $answer) {
                    array_push($answerIDs, $answer['a_id']);
                }
                $answers = $this->CI->model('Answer')->getAnswerSummary($answerIDs);
                if(!$answers->error){
                    $answers = $answers->result;
                    foreach($gaApi['answers'] as $index => $answer) {
                        if($foundAnswer = $answers[$answer['a_id']]) {
                            if($foundAnswer['StatusType'] === STATUS_TYPE_PUBLIC && $foundAnswer['LanguageID'] === $languageID) {
                                $gaApi['answers'][$index]['retrieved'] = $foundAnswer;
                            }
                        }
                    }
                }
            }
            foreach($gaApi['responses'] as $response) {
                //create ea. response
                $tempResponse = new \RightNow\Libraries\Response();
                $tempResponse->responseID = $response['dt_response_id'];
                $tempResponse->parentQuestionID = $response['parent_dt_question_id'];
                $tempResponse->text = $this->getLabel($response['label'], $languageID);
                $tempResponse->type = $response['type'];
                $tempResponse->childQuestionID = isset($response['child_dt_question_id']) ? $response['child_dt_question_id'] : null;
                $tempResponse->childGuideID = isset($response['child_dt_id']) ? $response['child_dt_id'] : null;
                $tempResponse->responseText = isset($response['help_text']) ? nl2br($this->getLabel($response['help_text'], $languageID)) : '';
                $tempResponse->value = isset($response['response_value']) ? $response['response_value'] : '';
                if(isset($response['image_id']) && $response['image_id']) {
                    $guidedAssistance = new \RightNow\Internal\Sql\GuidedAssistance();
                    $tempResponse->imageID = $response['image_id'] . '/' . $guidedAssistance->getWorkflowImageCreation($response['image_id']);
                    $tempResponse->showCaption = $response['show_caption'] ? true : false;
                }
                if(isset($response['call_url']) && $response['call_url']) {
                    $tempResponse->url = htmlspecialchars_decode($response['call_url']);
                    $tempResponse->urlType = $response['call_url_method'];
                }

                //Add answer IDs if they exist
                if(isset($gaApi['answers']) && is_array($gaApi['answers']) && count($gaApi['answers']) > 0) {
                    $answers = array();
                    foreach($gaApi['answers'] as $answer) {
                        if($answer['dt_response_id'] === $tempResponse->responseID && $answer['retrieved']) {
                            array_push($answers, array('summary' => $answer['retrieved']['Summary'], 'link' => '/app/' . \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL) . "/a_id/{$answer['retrieved']['ID']}", 'id' => $answer['retrieved']['ID']));
                        }
                    }
                    if(count($answers))
                        $tempResponse->childAnswers = $answers;
                }
                $parentQuestion = $guide->getQuestionByID($tempResponse->parentQuestionID);
                $parentQuestion->addResponse($tempResponse);
            }

            if(isset($gaApi['name_values']) && $gaApi['name_values']) {
                //create name-value pairs
                foreach($gaApi['name_values'] as $nvp) {
                    //assert that all question IDs specified in a nvp exist in the guide
                    $question = $guide->getQuestionByID($nvp['dt_question_id']);
                    $question->addNameValuePair($nvp['name'], $nvp['value']);
                }
            }
            $guide->guideSessionID = $this->getGuideSession();

            \RightNow\Utils\Framework::setCache($cacheHandle, $guide);
            return $this->getResponseObject($guide);
        }
        return $this->getResponseObject(null, null, "Invalid Guide ID: $guideID");
    }

    /**
     * Generate a guide session id. This will be converted into a 64bit unsigned integer
     * when stats are processed by DQA.
     * @return string Randomly generated session ID
     */
    protected function getGuideSession() {
        return Api::generate_session_id();
    }

    /**
     * Return the proper label based on the specified language id and decode options.
     * @param array|null $labelArray List of labels; must contain at least one item
     * @param int $languageID Language id other language interface
     * @param boolean $decode Whether the label should be decoded
     * @return string Correct label for the language specified
     */
    protected function getLabel($labelArray, $languageID, $decode = false) {
        if($languageID && count($labelArray) > 1) {
            //guide's labels can be specified through language id from an alternate interface
            foreach($labelArray as $labelItem) {
                if(isset($labelItem['lang_id']) && $labelItem['lang_id'] === intval($languageID)) {
                    $label = $labelItem['label'];
                    break;
                }
            }
        }
        else {
            $label = isset($labelArray['lbl_item0']['label']) ? $labelArray['lbl_item0']['label'] : '';
        }
        return ($decode) ? htmlspecialchars_decode($label, ENT_QUOTES) : $label;
    }
}
