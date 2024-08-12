<?php
namespace RightNow\Widgets;

use \RightNow\Utils\Url;

class FavoritesButton extends \RightNow\Libraries\Widget\Base {
    private $favoriteApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $answerId = !is_null(Url::getParameter('a_id')) ? Url::getParameter('a_id') : null;
        $this->data['js']['enabled'] = true;
        $searchSession = isset($searchSession) ? $searchSession : null;
        $searchData = array('answerId' => $answerId, 'searchSession' => $searchSession, 'prTxnId' => Url::getParameter('prTxnId'), 'txnId' => Url::getParameter('txnId'));
        $answer = $this->CI->model('Okcs')->getAnswerViewData($answerId, Url::getParameter('loc'), $searchData, Url::getParameter('answer_data'), $this->favoriteApiVersion);
        if($answer['published'] !== \RightNow\Utils\Config::getMessage(PUBLISHED_LBL))
            $this->data['js']['enabled'] = false;
        $favoriteContent = $this->CI->model('Okcs')->getFavoriteList();
        $this->data['js']['answerID'] = $answerId;
        $this->data['js']['favoriteData'] = $favoriteContent;
        if($favoriteContent) {
            $favIds = $favoriteContent->value;
            if($favIds) {
                $favoriteIdArr = explode(",", $favIds);
                foreach($favoriteIdArr as $key => $favId) {
                    if($favId === $answerId) {
                        $this->data['js']['favoriteID'] = $favoriteContent->recordId;
                    }
                }
            }
        }
    }
}
