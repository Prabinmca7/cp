<?php
namespace RightNow\Widgets;
use RightNow\Utils\Url;

class OkcsRecentlyViewedContent extends \RightNow\Widgets\RecentlyViewedContent {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if($questionId = Url::getParameter('qid')) {
            $this->data['js']['questionId'] = $questionId;
            $this->data['js']['questionTitle'] = $this->CI->model('CommunityQuestion')->get($questionId)->result->Subject;
            $this->CI->model('Okcs')->setRecentlyViewedQuestions($questionId);
        }
        $this->data['js']['previousContent'] = $this->CI->model('Okcs')->getRecentlyViewedAnswers();
        $this->data['js']['previousQuestions'] = $this->CI->model('Okcs')->getRecentlyViewedQuestions();
        $this->data['js']['cpAnswerView'] = \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL);
        $this->data['js']['cpQuestionView'] = \RightNow\Utils\Config::getConfig(CP_SOCIAL_QUESTIONS_DETAIL_URL);
        $this->data['js']['currentAnswerId'] = $questionId ? $questionId : Url::getParameter('a_id');
    }
}
