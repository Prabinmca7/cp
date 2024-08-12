<?php
namespace RightNow\Widgets;

class FormInput extends \RightNow\Libraries\Widget\Input {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (parent::getDataType() === false)
            return false;
        if ($this->dataType === 'ServiceProduct' || $this->dataType === 'ServiceCategory') {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_FLD_TYPE_PROD_CAT_PLS_INPUT_S_MSG), $this->fieldName));
            return false;
        }
        if ($this->dataType === 'FileAttachmentIncident') {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_FLD_TYPE_FILE_ATTACH_PLS_MSG), $this->fieldName));
            return false;
        }
        if ($this->dataType === 'SalesProduct') {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_FLD_TYPE_SA_PROD_PLS_INPUT_S_MSG), $this->fieldName));
            return false;
        }
    }
}
