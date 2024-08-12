<?php
namespace Custom\Widgets\extended;

class ParentWidget extends \Custom\Widgets\extended\GrandParentWidget {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        return parent::getData();
    }
}
