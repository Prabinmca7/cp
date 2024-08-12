<?php
namespace RightNow\Widgets;
use RightNow\Utils\Url,
    RightNow\Utils\Config;

class OkcsTranslatedAnswerSelector extends \RightNow\Libraries\Widget\Base {
    private $answerViewApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }

        $answerId = $this->data['js']['answerId'] = Url::getParameter('a_id');
        $searchData = array('answerId' => $answerID, 'searchSession' => $searchSession, 'prTxnId' => Url::getParameter('prTxnId'), 'txnId' => Url::getParameter('txnId'));

        if(is_null($answerId)) {
            echo $this->reportError(Config::getMessage(ANSWERID_IS_NOT_AVAILABLE_LBL));
            return false;
        }
        $answer = $this->CI->model('Okcs')->getAnswerViewData($answerId, Url::getParameter('loc'), $searchData, Url::getParameter('answer_data'), $this->answerViewApiVersion);
        $this->data['locale'] = $answer['locale'];
        $this->data['error'] = $answer['error']->externalMessage;
    }
}
