<?php
namespace RightNow\Widgets;

class OracleLogo extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        // @codingStandardsIgnoreStart
        $this->data['link'] = \RightNow\Utils\Config::getConfig(rightnow_url);
        // @codingStandardsIgnoreEnd
        $this->data['title'] = 'Powered By Oracle'; //intentionally untranslated
    }
}