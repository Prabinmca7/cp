<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search,
    RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class ContentType extends \RightNow\Libraries\Widget\Base {
    private $contentTypeApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $defaultContentTypeExists = null;
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }

        if($this->data['attrs']['source_id']) {
            $search = Search::getInstance($this->data['attrs']['source_id']);
            $this->data['js'] = array('sources' => $search->getSources());
        }

        $this->data['js']['contentTypeRecommendationArray'] = array();
        $this->data['contentTypes'] = $this->getContentTypes();
        $channelRecordID = Url::getParameter('channelRecordID');

        if ($this->data['attrs']['display_all_content']) {
            $this->data['defaultContentType'] = $this->data['attrs']['label_display_all_content'];
            $defaultChannelToSet = '';
        }
        else {
            $this->data['defaultContentType'] = trim($this->data['attrs']['default_content_type']);
            $defaultChannelToSet = $this->data['defaultContentType'];
        }
        if($channelRecordID !== null) {
            $defaultChannelToSet = $this->data['defaultContentType'] = $channelRecordID;
        }
        else if($contentType = \RightNow\Utils\Url::getParameter('ct')) {
            $defaultChannelToSet = $this->data['defaultContentType'] = $contentType;
        }
        if ($this->data['contentTypes'] !== null) {
            foreach ($this->data['contentTypes'] as $item) {
                if ($item->name === null) {
                    echo $this->reportError(Config::getMessage(RES_OBJECT_PROPERTY_NAME_IS_NOT_MSG));
                    $this->data['errorDetail'] = array('errorCode' => '','externalMessage' => Config::getMessage(RES_OBJECT_PROPERTY_NAME_IS_NOT_MSG));
                    return false;
                }
                if ($item->recordId === null) {
                    echo $this->reportError(Config::getMessage(RES_OBJECT_PROPERTY_RECORDID_IS_NOT_MSG));
                    $this->data['errorDetail'] = array('errorCode' => '','externalMessage' => Config::getMessage(RES_OBJECT_PROPERTY_RECORDID_IS_NOT_MSG));
                    return false;
                }
                if (!isset($defaultContentTypeExists))
                    $defaultContentTypeExists = (strtoupper($item->referenceKey) === strtoupper($this->data['defaultContentType']));
            }
        }
        if(!$defaultContentTypeExists && isset($this->data['contentTypes']) && is_array($this->data['contentTypes']) && count($this->data['contentTypes']) > 0 && !$this->data['attrs']['display_all_content'])
            $defaultChannelToSet = $this->data['defaultContentType'] = $this->data['contentTypes'][0]->referenceKey;
        $this->CI->model('Okcs')->setDefaultChannel($defaultChannelToSet);
    }

    /**
    * This method returns a list of content-types
    * @return Array|null List of the content-types
    */
    function getContentTypes(){
        $displayOnlyContentTypes = explode(",", trim($this->data['attrs']['content_type_list']));
        $allContentTypes = $this->CI->model('Okcs')->getChannels($this->contentTypeApiVersion);
        if (isset($allContentTypes->error) && $allContentTypes->error !== null) {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($allContentTypes->error));
            return null;
        }

        if(empty($this->data['attrs']['content_type_list'])) {
            if(isset($allContentTypes->items) && is_array($allContentTypes->items)){
                foreach ($allContentTypes->items as $item) {
                    if($item->allowRecommendations) {
                        array_push($this->data['js']['contentTypeRecommendationArray'], $item);
                    }
                }
            }
            return $allContentTypes->items;
        }
        if ($allContentTypes->items !== null) {
            $contentType = array();
            $invalidChannel = array();
            for($i = 0; $i < count($displayOnlyContentTypes); $i++) {
                $isValidChannel = false;
                foreach ($allContentTypes->items as $item) {
                    if(strtoupper($item->referenceKey) === strtoupper(trim($displayOnlyContentTypes[$i]))) {
                        $isValidChannel = true;
                        if($item->allowRecommendations) {
                            array_push($this->data['js']['contentTypeRecommendationArray'], $item);
                        }
                        array_push($contentType, $item);
                        break;
                    }
                }
                if(!$isValidChannel)
                    array_push($invalidChannel, $displayOnlyContentTypes[$i]);
            }
            if(count($invalidChannel) == 0)
                return $contentType;
            else{
                echo $this->reportError(sprintf(Config::getMessage(PCT_S_NOT_FND_FOR_THE_CONTENT_TYPE_LBL), implode(", ", $invalidChannel)));
            }
        }
    }
}
