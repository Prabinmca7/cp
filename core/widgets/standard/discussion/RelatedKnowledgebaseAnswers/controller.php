<?php
namespace RightNow\Widgets;
use RightNow\Utils\Url;

class RelatedKnowledgebaseAnswers extends \RightNow\Widgets\TopAnswers {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $question = $this->CI->model('CommunityQuestion')->get(Url::getParameter('qid'))->result;
        if (!isset($question->{$this->data['attrs']['related_by']}->ID) || !$question->{$this->data['attrs']['related_by']}->ID) return false;

        $this->data['attrs'][strtolower($this->data['attrs']['related_by']) . '_filter_id'] = $question->{$this->data['attrs']['related_by']}->ID;
        if (parent::getData() === false) {
            return false;
        }
    }
}
