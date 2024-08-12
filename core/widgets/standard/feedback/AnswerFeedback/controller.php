<?php
namespace RightNow\Widgets;

class AnswerFeedback extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['js'] = array(
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
            'isProfile' => false,
            'email' => '',
            'buttonView' => ($this->data['attrs']['options_count'] === 2),
        );
        $this->data['rateLabels'] = $this->getRateLabels();

        $answerID = \RightNow\Utils\Url::getParameter('a_id');
        if($answerID) {
            $answerData = $this->CI->model('Answer')->get($answerID);
            if($answerData->error){
                return false;
            }
            $this->data['js']['answerID'] = $answerID;
        }
        else {
            $this->data['js']['answerID'] = null;
        }

        if(\RightNow\Utils\Framework::isLoggedIn()){
            $this->data['js']['email'] = $this->CI->session->getProfileData('email');
            $this->data['js']['isProfile'] = true;
        }
        else if($previousEmail = $this->CI->session->getSessionData('previouslySeenEmail')) {
            $this->data['js']['email'] = $previousEmail;
        }
    }

    /**
     * Get the Answer feedback rating labels
     * @return array An array containing the labels if the 'options_count' is 3 to 5, otherwise an empty array
     */
    protected function getRateLabels() {
        switch($this->data['attrs']['options_count']) {
            case 3:
                return array(null, RANK_0_LBL, RANK_50_LBL, RANK_100_LBL);
            case 4:
                return array(null, RANK_0_LBL, RANK_25_LBL, RANK_75_LBL, RANK_100_LBL);
            case 5:
                return array(null, RANK_0_LBL, RANK_25_LBL, RANK_50_LBL, RANK_75_LBL, RANK_100_LBL);
            default:
                return array();
        }
    }
}
