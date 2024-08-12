<?php
namespace Custom\Widgets\viewhelpertest;

class ViewHelper extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->loadHelper('Sample');
        return parent::getData();
    }
}
