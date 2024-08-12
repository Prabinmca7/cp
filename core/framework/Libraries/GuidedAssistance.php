<?php
namespace RightNow\Libraries;

use RightNow\Utils\Framework;
require_once CPCORE . 'Libraries/BoundedObjectBase.php';

/**
 * Structure to represent a Guided Assistance guide.
 */
class GuidedAssistance extends BoundedObjectBase {

    protected $guideID;
    protected $name;
    protected $questions;
    protected $guideSessionID;

    function __construct($guideID, $name) {
        $this->guideID = $guideID;
        $this->name = $name;
        $this->questions = array();
    }

    /**
     * Returns the guide as an associative array.
     * @return array The guide
     */
    public function toArray() {
        if(count($this->questions)) {
            $questions = array();
            foreach($this->questions as $question) {
                if($question instanceof Question)
                    array_push($questions, $question->toArray());
            }
            $temp = $this->questions;
            $this->questions = $questions;
            $returnVal = array_filter(get_object_vars($this));
            $this->questions = $temp;
            return $returnVal;
        }
        else {
            return array_filter(get_object_vars($this));
        }
    }

    /**
     * Adds a question to the guide.
     * @param Question $question Object to add
     * @return void
     */
    public function addQuestion(Question $question) {
        array_push($this->questions, $question);
    }

    /**
     * Returns a guide question specified by the id
     * @param int $id The Question's id
     * @return Question Question object
     */
    public function getQuestionByID($id) {
        foreach($this->questions as $question) {
            if($question->questionID === intval($id)) {
                return $question;
            }
        }
    }
}

/**
 * Represents a Guided Assistance question.
 */
class Question extends BoundedObjectBase {

    protected $questionID;
    protected $guideID;
    protected $name;
    protected $text;
    protected $taglessText;
    protected $agentText;
    protected $type;
    protected $responses;
    protected $nameValuePairs;

    /**
     * No longer used but are kept around for backward compatibility
     * @internal
     */
    protected $imageID;

    /**
     * No longer used but are kept around for backward compatibility
     * @internal
     */
    protected $url;

    /**
     * No longer used but are kept around for backward compatibility
     * @internal
     */
    protected $displayOption;

    function __construct() {
        $this->responses = array();
        $this->nameValuePairs = array();
    }

    /**
     * Returns the question as an associative array.
     * @return array The question
     */
    public function toArray() {
        if(count($this->responses)) {
            $responses = array();
            foreach($this->responses as $response) {
                if($response instanceof Response)
                    array_push($responses, $response->toArray());
            }
            $temp = $this->responses;
            $this->responses = $responses;
            $returnVal = array_filter(get_object_vars($this));
            $this->responses = $temp;
            return $returnVal;
        }
        else {
            return array_filter(get_object_vars($this));
        }
    }

    /**
    * Adds the name and value to the question.
    * @param string $name The name
    * @param string $value The value
    * @return void
    */
    public function addNameValuePair($name, $value) {
        $this->nameValuePairs[$name] = $value;
    }

    /**
    * Adds a response to the question.
    * @param Response $response Response object to add
    * @return void
    */
    public function addResponse(Response $response) {
        array_push($this->responses, $response);
    }
}

/**
* Represents a Guided Assistance response.
*/
class Response extends BoundedObjectBase {

    protected $responseID;
    protected $responseText;
    protected $text;
    protected $type;
    protected $value;
    protected $parentQuestionID;
    protected $childQuestionID;
    protected $childGuideID;
    protected $childAnswers;

    /**
     * Optional: only included for URL result
     */
    protected $url;
    protected $urlType;

    /**
     * Optional: only included for Image responses
     */
    protected $imageID;
    protected $showCaption;

    function __construct() {
        $childAnswers = array();
    }

    /**
     * Returns the response as an associative array.
     * @return array The response
     */
    public function toArray() {
        $members = get_object_vars($this);
        foreach($members as $key => $value) {
            if($value === null)
                unset($members[$key]);
        }
        return $members;
    }
}
