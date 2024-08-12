<?php
namespace RightNow\Widgets;

class SurveyLink extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['survey_url'] = $this->CI->model('Survey')->buildSurveyURL($this->data['attrs']['survey_id']);
    }
}

