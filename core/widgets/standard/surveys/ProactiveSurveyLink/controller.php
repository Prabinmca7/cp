<?php
namespace RightNow\Widgets;

class ProactiveSurveyLink extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['js']['survey_url'] = $this->CI->model('Survey')->buildSurveyURL($this->data['attrs']['survey_id']);

        $freq = $this->data['attrs']['frequency'];
        if($freq < 100 && rand(0, 100) > $freq)
            return false;

        $linkCookie = intval($_COOKIE[$this->instanceID]);
        if($linkCookie > 0)
            return false;
    }
}

