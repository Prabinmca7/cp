<?php
namespace RightNow\Widgets;
use RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Api,
    RightNow\Utils\Config,
    RightNow\Utils\Okcs;

class AnswerField extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $className = null;
        $index = null;
        if(!is_null($this->data['attrs']['answer_key']) && empty($this->data['attrs']['label'])){
            $this->getLabel($this->data['attrs']['answer_key']);
        }
        if($this->data['attrs']['type'] === 'FILE'){
            $file = Text::getSubstringBefore($this->data['attrs']['value'], '#');
            $this->data['fileName'] = Text::getSubstringAfter($this->data['attrs']['value'], '#');
            $this->data['value'] = Url::getParameter('a_id') . '_' . $file;
            $this->getAttachmentType($this->data['fileName']);
        }
        if($this->data['attrs']['xpath'] !== '') {
            $className = ucwords(strtolower($this->data['attrs']['xpath']));
            $index = 0;
            foreach(array('_', '/') as $delimiter) {
                if(strpos($className, $delimiter))
                    $className = implode('_', array_map('ucfirst', explode($delimiter, $className)));
                $index++;
            }
            $this->classList->add('rn_AnswerField_' . $className);
        }
        if($this->data['attrs']['answer_key'] === 'user_group') {
            $usergrouparray = array_filter(explode(',', $this->data['attrs']['value']));
            $this->data['attrs']['value'] = $usergrouparray;
        }
        $this->data['fieldData'] = array(
            'label' => $this->data['attrs']['label'],
            'value' => $this->data['attrs']['value'],
            'type' => $this->data['attrs']['type'],
            'answer_key' => $this->data['attrs']['answer_key'],
            'className' => isset($className) ? $className : null,
            'index' => isset($index) ? $index : null
        );
    }

    /**
    * Updates the widget attribute label with the config message value.
    * @param string $fieldName Field Name
    */
    private function getLabel($fieldName){
        switch($fieldName) {
            case 'docID':
                $this->data['attrs']['label'] = Config::getMessage(DOCUMENT_ID_LBL);
                break;
            case 'version':
                $this->data['attrs']['label'] = Config::getMessage(VERSION_LBL);
                break;
            case 'status':
                $this->data['attrs']['label'] = Config::getMessage(STATUS_LBL);
                break;
            case 'publishedDate':
                $this->data['attrs']['label'] = Config::getMessage(PUBLISHED_DATE_LBL);
        }
    }
    
    /**
    * Gets the attachment type based on the file extention.
    * @param string $fileName File Name
    */
    private function getAttachmentType($fileName){
        $mediaArray = $mediaTypeArray = $mediaTypes = array();
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if($this->data['attrs']['display_media_inline'] === false) {
            $this->data['fileType'] = 'NONE';
            return;
        } 
        else {
            if((substr_count($this->data['attrs']['supported_formats_media_inline'], '=')) - (substr_count($this->data['attrs']['supported_formats_media_inline'], '|')) === 1 ) {
                if(substr_count($this->data['attrs']['supported_formats_media_inline'], '|') > 0) {
                    $mediaArray = explode('|', $this->data['attrs']['supported_formats_media_inline']);
                    foreach($mediaArray as $mediaArrayItem) {
                        $mediaTypeArray = explode('=', $mediaArrayItem);
                        $mediaTypes[$mediaTypeArray[0]] = $mediaTypeArray[1];
                    }
                    foreach($mediaTypes as $mediaTypeKey => $mediaTypeValues) {
                        if (strpos(strtolower(trim($mediaTypeValues)), strtolower(trim($fileType))) !== false) {
                            $this->data['fileType'] = trim($mediaTypeKey);
                        }   
                    }
                }
                else {
                    if(substr_count($this->data['attrs']['supported_formats_media_inline'], '=') > 0) {
                        $mediaTypeArray = explode('=', $mediaArrayItem);
                        $mediaTypes[$mediaTypeArray[0]] = $mediaTypeArray[1];
                        foreach($mediaTypes as $mediaTypeKey => $mediaTypeValues) {
                            if (strpos(strtolower(trim($mediaTypeValues)), strtolower(trim($fileType))) !== false) {
                                $this->data['fileType'] = trim($mediaTypeKey);
                            }   
                        }
                    }
                    else {
                        echo $this->reportError('Format of supported_formats_media_inline attribute value is invalid');
                    }
                }
            }
            else {
                echo $this->reportError('Format of supported_formats_media_inline attribute value is invalid');
            }
        }
    }
}
