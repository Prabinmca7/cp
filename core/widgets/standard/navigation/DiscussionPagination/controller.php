<?php

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class DiscussionPagination extends \RightNow\Libraries\Widget\Base {
    
    const NO_QUESTION = 0;

    function __construct ($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'get_next_prev_ajax' => array(
                'method' => 'getNextPrev',
            ),
        ));
    }

    function getData() {
        $questionID = Url::getParameter('qid');
        $question = $this->CI->model('CommunityQuestion')->get($questionID)->result;
        if (!$question)
            return false;
        
        $dataType = $this->data['attrs']['type'];
        if ($dataType === 'product') {
            if ($question->Product) {
                $this->data['prodcat_id'] = $question->Product->ID;
                if ($question->Product && $question->Product->Name !== null) {
                    $this->data['prodcat_name'] = substr($question->Product->Name, 0, 75);
                } else {
                    $this->data['prodcat_name'] = null; // Or any default value you prefer
                }                
            }
        } else if ($dataType === 'category') {
            if ($question->Category) {
                $this->data['prodcat_id'] = $question->Category->ID;
                $this->data['prodcat_name'] = substr($question->Category->Name, 0, 75);
            }
        }

        if (!isset($this->data['prodcat_id']) || !$this->data['prodcat_id'])
            return false;

        $this->data['js']['prodcat_id'] = $this->data['prodcat_id'];
        $returnData = $this->CI->model('CommunityQuestion')->getPrevNextQuestionID($question->ID, $this->data['prodcat_id'], $dataType, array('oldestNewestQuestion' => true));

        if (!$returnData->result)
            return false;

        if ($returnData->result['oldestQuestion'] === $questionID) {
            $this->data['isOldestQuestion'] = true;
        }

        if ($returnData->result['newestQuestion'] === $questionID) {
            $this->data['isNewestQuestion'] = true;
        }
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    function getNextPrev (array $parameters) {
        $paginatorLink = $parameters['paginatorLink'];
        $key = ($paginatorLink === 'oldestQuestion' || $paginatorLink === 'newestQuestion') ? 'oldestNewestQuestion' : $paginatorLink;

        if (!$question = $this->CI->model('CommunityQuestion')->get($parameters['qid'])->result) {
            echo self::NO_QUESTION;
        }
        $returnData = $this->CI->model('CommunityQuestion')->getPrevNextQuestionID($parameters['qid'], $parameters['prodcat_id'], $this->data['attrs']['type'], array($key => true));

        if ($returnData->result && $returnData->result[$paginatorLink]) {
            echo $returnData->result[$paginatorLink];
        }
        else {
            echo self::NO_QUESTION;
        }
    }
}
