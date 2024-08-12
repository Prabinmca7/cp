<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config;

class QuestionStatus extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$question = $this->CI->model('CommunityQuestion')->get(\RightNow\Utils\Url::getParameter('qid'))->result) {
            return false;
        }

        $state = $this->getQuestionState($question);
        if ($state['status'] === 'active') {
            if (!$state['locked']) {
                // Do not display widget for active unlocked questions
                return false;
            }
            // Display status as 'locked' for active questions.
            $state['status'] = 'locked';
        }
        
        //Do not display the widget for pending questions till we support the pending status
        if($state['status'] === 'pending') return false;
          
        $this->classList->add('rn_' . ucfirst($state['status']));
        if ($state['locked'] && $state['status'] !== 'locked') {
            $this->classList->add('rn_Locked');
        }
        $this->data['question'] = $question;
        $this->data['state'] = $state;
    }

    /*
     * Returns an array having keys:
     *   'locked' {boolean} True if question is locked.
     *   'status' {string} One of 'active', 'pending' or 'suspended'.
     * @param object $question The question Connect object
     * @return array An array containing the statuses and labels for the question.
     */
    protected function getQuestionState($question) {
        $statuses = array('locked' => false);
        foreach(array('locked', 'active', 'pending', 'suspended') as $status) {
            $method = 'is' . ucfirst($status);
            if ($question->SocialPermissions->$method()) {
                if ($status === 'locked') {
                    $statuses['locked'] = true;
                }
                else {
                    $statuses['status'] = $status;
                    break;
                }
            }
        }

        return $statuses;
    }
}
