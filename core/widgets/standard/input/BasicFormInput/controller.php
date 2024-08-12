<?php
namespace RightNow\Widgets;

class BasicFormInput extends \RightNow\Libraries\Widget\Input {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (parent::getDataType() === false)
            return false;

        if ($this->dataType === 'ServiceProduct' || $this->dataType === 'ServiceCategory') {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_FLD_TYPE_PRD_CAT_PLS_INPUT_S_MSG), $this->fieldName));
            return false;
        }

        if ($this->dataType === 'FileAttachmentIncident') {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_FLD_TYPE_FLE_ATTACH_UNSUP_MSG), $this->fieldName));
            return false;
        }

    }
}