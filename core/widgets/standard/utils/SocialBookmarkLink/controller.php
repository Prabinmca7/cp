<?php
namespace RightNow\Widgets;

class SocialBookmarkLink extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'check_question_exist_ajax' => array(
                'method' => 'checkQuestionExist',
            ),
        ));
    }

    function getData() {
        if ($this->data['attrs']['object_type'] === 'answer') {
            $objectID = \RightNow\Utils\Url::getParameter('a_id');
            $path = '/app/' . $this->CI->page . '/a_id/' . $objectID;

            if (!($answer = $this->CI->model('Answer')->get($objectID)->result)) {
                return false;
            }
        }
        else if ($this->data['attrs']['object_type'] === 'question') {
            $objectID = \RightNow\Utils\Url::getParameter('qid');
            $path = '/app/' . $this->CI->page . '/qid/' . $objectID;

            if (!($socialQuestion = $this->CI->model('CommunityQuestion')->get($objectID)->result)) {
                return false;
            }

            // If the social question is not active then hide the widget
            if (!$socialQuestion->SocialPermissions->isActive()) {
                $this->classList->add('rn_Hidden');
            }

            $this->data['js'] = array(
                'objectID' => $objectID,
                'activeStatusWithTypeID' => STATUS_TYPE_SSS_QUESTION_ACTIVE
            );
        }
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        
        $pageTitle = \RightNow\Libraries\SEO::getDynamicTitle($this->data['attrs']['object_type'], $objectID);
        $pageTitle = urlencode(htmlspecialchars_decode($pageTitle));
        $pageUrl = \RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage', $path);

        $this->data['sites'] = array();
        $pages = explode(',', $this->data['attrs']['sites']);
        foreach ($pages as $page) {
            list($name, $title, $link) = explode('>', trim($page, ' "\''));
            $link = str_replace('|URL|', $pageUrl, $link);
            $link = str_replace('|TITLE|', $pageTitle, $link);
            $this->data['sites'] []= array('name' => trim($name, '"\''), 'title' => trim($title, '"\''), 'link' => trim($link, '"\''));
        }
        if (!count($this->data['sites'])) return false;
    }

    /**
     * Checks the status of the question and returns the corresponding error message
     * @param array $parameters Post parameters
     */
    function checkQuestionExist (array $parameters) {
        \RightNow\Libraries\AbuseDetection::check();
        echo get_instance()->model('CommunityQuestion')->get($parameters['qid'])->toJson();
    }

}
