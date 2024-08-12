<?php
namespace RightNow\Widgets;
use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class AnswerContent extends \RightNow\Libraries\Widget\Base {
    private $answerViewApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        if(Url::getParameter('a_id') == ''){
            echo $this->reportError('AnswerID is not available');
            return false;
        }
        $answerID = Text::getSubstringBefore(Url::getParameter('s'), '_');
        $searchSession = Text::getSubstringAfter(Url::getParameter('s'), '_');
        $searchData = array('answerId' => $answerID, 'searchSession' => $searchSession, 'prTxnId' => Url::getParameter('prTxnId'), 'txnId' => Url::getParameter('txnId'));
        $answer = $this->CI->model('Okcs')->getAnswerViewData(Url::getParameter('a_id'), Url::getParameter('loc'), $searchData, Url::getParameter('answer_data'), $this->answerViewApiVersion);
        $noTitleLabel = isset($this->data['attrs']['label_no_title']) ? trim($this->data['attrs']['label_no_title']) : '';
        if (isset($answer->errors) && $answer->errors) {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($answer->error));
            return false;
        }
        $this->data['answer'] = $answer;
        if(($answer['title'] === Config::getMessage(NO_TTLE_LBL)) && $noTitleLabel !== '') {
            $this->data['js']['answerTitle'] = $noTitleLabel;
        }
        else {
            $this->data['js']['answerTitle'] = Text::escapeHtml($answer['title']);
        }
        $this->data['js']['answerID'] = Url::getParameter('a_id');
        $this->CI->model('Okcs')->setRecentlyViewedAnswers(Url::getParameter('a_id'));
    }
}
