<?php
namespace RightNow\Widgets;

class ContactNameInput extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['names'] = array('Contact.Name.First', 'Contact.Name.Last');
        $this->data['labels'] = array(\RightNow\Utils\Config::getMessage(FIRST_NAME_LBL), \RightNow\Utils\Config::getMessage(LAST_NAME_LBL));
        if (\RightNow\Utils\Config::getConfig(intl_nameorder)) {
            $this->data['names'] = array_reverse($this->data['names']);
            $this->data['labels'] = array_reverse($this->data['labels']);
        }
    }
}
