<?php
namespace RightNow\Widgets;
use \RightNow\Utils\Url,
    \RightNow\Libraries\Search,
    \RightNow\Utils\Framework,
    \RightNow\Utils\Config,
    RightNow\Utils\Text;

class OkcsRecommendContent extends \RightNow\Libraries\Widget\Base {
    private $contentTypeApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this) || !(Framework::isLoggedIn() || $this->data['attrs']['display_to_anonymous'])) {
            return false;
        }

        if($this->data['attrs']['source_id']) {
            $search = Search::getInstance($this->data['attrs']['source_id']);
            $this->data['js'] = array('sources' => $search->getSources());
        }

        $this->data['js']['valid_file_extensions'] = \RightNow\Utils\Config::getConfig(VALID_FILE_EXTENSIONS);

        $channelRecordID = Url::getParameter('channelRecordID');
        $this->data['js']['isRecommendChange'] = false;
        $this->data['button_label'] = $this->data['attrs']['label_recommend_content'];        
        $answerID = Url::getParameter('a_id');
        $this->data['js']['title'] = '';
        $this->data['js']['r_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);//recommendContent Token

        if($answerID !== null) {
            $this->data['js']['answerId'] = $answerID;
            $this->data['js']['isRecommendChange'] = true;
            $this->data['button_label'] = $this->data['attrs']['label_recommend_change'];
        
            $searchSession = Text::getSubstringAfter(Url::getParameter('s'), '_');
            $searchData = array('answerId' => $answerID, 'searchSession' => $searchSession, 'prTxnId' => Url::getParameter('prTxnId'), 'txnId' => Url::getParameter('txnId'));
            $answer = $this->CI->model('Okcs')->getAnswerViewData(Url::getParameter('a_id'), Url::getParameter('loc'), $searchData, Url::getParameter('answer_data'), $this->contentTypeApiVersion);
            if(isset($answer->errors) && $answer->errors) {
                echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($answer->error));
                return false;
            }
            if(!empty($answer)){
                $this->data['js']['title'] = $this->data['attrs']['label_title_prefix'] . $answer['title'];
                $this->data['js']['contentRecordID'] = isset($answer['contentRecordID']) ? $answer['contentRecordID'] : null;
                $this->data['js']['docID'] = $answer['docID'];
                $this->data['js']['versionID'] = $answer['versionID'];
                $contentTypeReferenceKey = isset($answer['contentTypeReferenceKey']) ? $answer['contentTypeReferenceKey'] : null;
            }
        }

        $defaultChannel = ($channelRecordID !== null) ? $channelRecordID : $this->CI->model('Okcs')->getDefaultChannel();
        $this->data['js']['selectedContentType'] = $this->data['js']['defaultContentType'] = ($defaultChannel === null) ? '' : $defaultChannel;
        $this->data['js']['fieldIsRequired'] = ' ' . Config::getMessage(FIELD_IS_REQUIRED_MSG);
        $this->data['recommendationClass'] = 'rn_Hidden';
        // Set the channel name for Recommend Change flow
        if($this->data['js']['isRecommendChange']) {
            $defaultChannel = $contentTypeReferenceKey;
            // set the button to visible depending on answer object's flag
            if(isset($answer['allowRecommendations']) && $answer['allowRecommendations'])
                $this->data['recommendationClass'] = '';
        }

        if(empty($defaultChannel)) {
            $this->data['recommendationClass'] = '';
            return;
        }
        //Invoke getValidContentTypes only for recommend content flow; NOT for recommend change
        if(!$this->data['js']['isRecommendChange']) {
            $this->data['js']['contentTypes'] = $this->getValidContentTypes();
            if(count($this->data['js']['contentTypes']) > 0) {
                foreach($this->data['js']['contentTypes'] as $item) {
                    if(strtoupper($item->referenceKey) === strtoupper(trim($defaultChannel))) {
                        $this->data['recommendationClass'] = '';
                        break;
                    }
                }
            }
            else {
                $this->data['recommendationClass'] = 'rn_Hidden';
            }
        }
    }
    
    /**
    * This method returns a list of content-types for which recommendations are allowed
    * @return Array|null List of the content-types for which recommendations are allowed
    */
    function getValidContentTypes(){
        $userPreferredList = explode(",", trim($this->data['attrs']['content_type_list']));
        $allContentTypes = $this->CI->model('Okcs')->getChannels($this->contentTypeApiVersion);
        if (isset($allContentTypes->error)) {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($allContentTypes->error));
            return null;
        }

        if(empty($this->data['attrs']['content_type_list'])) {
            $validContentTypes = array();
            foreach ($allContentTypes->items as $item) {
                if($item->allowRecommendations) {
                    array_push($validContentTypes, $item);
                }
            }
            return $validContentTypes;
        }

        if ($allContentTypes->items !== null) {
            $validUserPreferredList = array();
            $invalidChannel = array();
            for($i = 0; $i < count($userPreferredList); $i++) {
                $isValidChannel = false;
                foreach ($allContentTypes->items as $item) {
                    if(strtoupper($item->referenceKey) === strtoupper(trim($userPreferredList[$i]))) {
                        $isValidChannel = true;
                        if($item->allowRecommendations) {
                            array_push($validUserPreferredList, $item);
                        }
                        break;
                    }
                }
                if(!$isValidChannel)
                    array_push($invalidChannel, $userPreferredList[$i]);
            }
            if(count($invalidChannel) == 0) {
                return $validUserPreferredList;
            }
            else {
                echo $this->reportError(sprintf(Config::getMessage(PCT_S_NOT_FND_FOR_THE_CONTENT_TYPE_LBL), implode(", ", $invalidChannel)));
            }
        }
    }
}
