<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url;

class RelatedAnswers extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$answerID = Url::getParameter('a_id')) {
            return false;
        }

        $this->data['appendedParameters'] = Url::getParametersFromList($this->data['attrs']['add_params_to_url']) . '/related/1' . Url::sessionParameter();

        $relatedAnswers = $this->CI->model('Answer')->getRelatedAnswers($answerID, $this->data['attrs']['limit']);
        if($relatedAnswers->error || (is_array($relatedAnswers->result) && count($relatedAnswers->result) === 0)) {
            return false;
        }

        $this->data['relatedAnswers'] = $relatedAnswers->result;

        // if the highlight attribute is enabled but we don't have a search term, turn highlight off
        if ($this->data['attrs']['highlight'] && !Url::getParameter('kw')) {
            $this->data['attrs']['highlight'] = false;
        }
    }
}
