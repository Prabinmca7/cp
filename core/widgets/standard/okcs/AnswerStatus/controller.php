<?php
namespace RightNow\Widgets;
use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class AnswerStatus extends \RightNow\Libraries\Widget\Base {
    private $answerViewApiVersion = 'v1';
    
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $metaArray = $imContentData = $imContentDataArray = array();
        
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $docID = Url::getParameter('a_id');
        $locale = Url::getParameter('loc');
        $searchCacheData = Url::getParameter('s');
        $answerData = Url::getParameter('answer_data');
        $attributes = $this->data['attrs'];
        $answerID = Text::getSubstringBefore(Url::getParameter('s'), '_');
        $searchSession = Text::getSubstringAfter(Url::getParameter('s'), '_');
        $searchData = array('answerId' => $answerID, 'searchSession' => $searchSession, 'prTxnId' => Url::getParameter('prTxnId'), 'txnId' => Url::getParameter('txnId'));
        $answer = $this->CI->model('Okcs')->getAnswerViewData($docID, $locale, $searchData, $answerData, $this->answerViewApiVersion);
        $isAggRatAvailbale = false;
        if (strpos($this->data['attrs']['custom_metadata'], 'aggregate_rating') !== false) {
            $aggregateRating = $this->CI->model('Okcs')->getAggregateRating($answer['contentRecordID']);
            $answer['questionsCount'] = count($aggregateRating->questions[0]);
            $answer['aggregateRating'] = $aggregateRating->questions[0]->averageResponse;
            $answer['answersCount'] = count($aggregateRating->questions[0]->answers);
            if($aggregateRating !== null && $answer['questionsCount'] > 0 && $answer['answersCount'] === 5) {
                $isAggRatAvailbale = true;
            }    
        }
        if (isset($answer->errors) && $answer->errors) {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($answer->error));
            return false;
        }
        $metaArray = explode('|', $this->data['attrs']['custom_metadata']);
        foreach($metaArray as $metaArrayItem) {
            if($isAggRatAvailbale || (!$isAggRatAvailbale && strtolower(trim($metaArrayItem)) !== 'aggregate_rating')) {
                $answerStatusValue = $answer[$this->getAttributeValue($metaArrayItem)];
                $userGroupList = '';
                if($metaArrayItem === "user_group") {
                    foreach($answerStatusValue as $value) {
                        $userGroupList .= $value->name . ',';
                    }
                    $answerStatusValue = rtrim($userGroupList, ",");
                }
                $imContentData = array(
                                    'answer_key' => $metaArrayItem ,
                                    'value' => $answerStatusValue ,
                                    'label' => $this->getAttributeLabel($metaArrayItem, $answer)
                                    );
                array_push($imContentDataArray, $imContentData);
            }
        }
        $this->data = $answer;
        $this->data['customMetadata'] = $imContentDataArray;
        $this->data['attrs'] = $attributes;
    }
    function getAttributeValue($value) {
        $returnValue = null;
        switch (strtolower(trim($value))) {
            case "document_id":
                $returnValue = "docID";
                break;
            case "version":
                $returnValue = "version";
                break;
            case "status":
                $returnValue = "published";
                break;
            case "display_date":
                $returnValue = "publishedDate";
                break;
            case "aggregate_rating":
                $returnValue = "aggregateRating";
                break;
            case "owner":
                $returnValue = "owner";
                break;
            case "answer_id":
                $returnValue = "answerId";
                break;
            case "last_modifier":
                $returnValue = "lastModifier";
                break;
            case "last_modified":
                $returnValue = "lastModifiedDate";
                break;
            case "creator":
                $returnValue = "creator";
                break;
            case "user_group":
                $returnValue = "userGroup";
                break;
            default:
                $returnValue = "";
        }
        return $returnValue;
    }
    
    function getAttributeLabel($value, $answer) {
        $returnValue = null;
        switch (strtolower(trim($value))) {
            case "document_id":
                $returnValue = $this->data['attrs']['label_doc_id'];
                break;
            case "version":
                $returnValue = $this->data['attrs']['label_version'];
                break;
            case "status":
                 $returnValue = $this->data['attrs']['label_status'];
                break;
            case "display_date":
                if($answer['published'] === Config::getMessage(PUBLISHED_LBL)){
                    $returnValue = $this->data['attrs']['label_published_date'];
                } 
                else if ($answer['published'] === Config::getMessage(DRAFT_LBL)){
                    $returnValue = $this->data['attrs']['label_modified_date'];
                }
                break;
            case "aggregate_rating":
                 $returnValue = $this->data['attrs']['label_aggregate_rating'];
                break;
            case "owner":
                $returnValue = $this->data['attrs']['label_owner'];
                break;
            case "answer_id":
                $returnValue = $this->data['attrs']['label_answer_id'];
                break;
            case "last_modifier":
                $returnValue = $this->data['attrs']['label_last_modifier'];
                break;
            case "last_modified":
                $returnValue = $this->data['attrs']['label_last_modified'];
                break;
            case "creator":
                $returnValue = $this->data['attrs']['label_creator'];
                break;
            case "user_group":
                $returnValue = $this->data['attrs']['label_user_group'];
                break;
            default:
                $returnValue = "";
        }
        return $returnValue;
    }
}
