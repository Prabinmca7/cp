<?php
namespace RightNow\Widgets;

class OkcsSmartAssistant extends \RightNow\Widgets\SmartAssistantDialog {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        parent::getData();
        $this->data['js']['published'] = \RightNow\Utils\Config::getMessage(PUBLISHED_LBL);
    }
}