<?php
namespace Custom\Widgets\extended;

class GrandParentWidget extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        return parent::getData();
    }
}
