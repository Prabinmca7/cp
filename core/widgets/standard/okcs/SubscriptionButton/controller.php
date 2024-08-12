<?php
namespace RightNow\Widgets;

use \RightNow\Utils\Url,
    \RightNow\Utils\Text,
    \RightNow\Utils\Config;

class SubscriptionButton extends \RightNow\Libraries\Widget\Base {
    private $subscriptionApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $locale = Url::getParameter('loc');
        $searchCacheData = Url::getParameter('s');
        $answerData = Url::getParameter('answer_data');
        $noTitleLabel = isset($this->data['attrs']['label_no_title']) ? trim($this->data['attrs']['label_no_title']) : '';
        $answerID = Text::getSubstringBefore(Url::getParameter('s'), '_');
        $searchSession = Text::getSubstringAfter(Url::getParameter('s'), '_');
        $searchData = array('answerId' => $answerID, 'searchSession' => $searchSession, 'prTxnId' => Url::getParameter('prTxnId'), 'txnId' => Url::getParameter('txnId'));
        $answer = $this->CI->model('Okcs')->getAnswerViewData(Url::getParameter('a_id'), $locale, $searchData, $answerData, $this->subscriptionApiVersion);
        if (is_array($answer) && !empty($answer)) {
            $this->data['js'] = array( 'locale' => Url::getParameter('loc'), 'answerID' => Url::getParameter('a_id'),'docId' => $answer['docID'],'versionID' => $answer['versionID']);
            $subscriptionData = $this->CI->model('Okcs')->getSubscriptionList(Url::getParameter('a_id'));
            $this->data['js']['subscriptionData'] = $subscriptionData;
            
            $length = isset($subscriptionData->items) && is_array($subscriptionData->items) ? count($subscriptionData->items) : 0; 
            for ($x = 0; $x < $length; $x++) {
                $subscriptionContent = $subscriptionData->items[$x];
                $ansID = (string) $subscriptionContent->content->answerId;
                if($this->data['js']['answerID'] === $ansID){
                    $this->data['js']['versionID'] = $subscriptionContent->content->versionId;
                    $this->data['js']['subscriptionID'] = $subscriptionContent->recordId;
                    $this->data['js']['failureMsgOnUnsub'] = Config::getMessage(SUBSCRIPTION_DOES_NOT_EXIST_LBL);
                    break;
                }
            }
        }
        else {
            $this->data['js']['subscriptionData'] = null;
        }
    }
}
