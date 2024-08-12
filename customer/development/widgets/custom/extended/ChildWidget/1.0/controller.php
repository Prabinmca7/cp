<?php
namespace Custom\Widgets\extended;

class ChildWidget extends \Custom\Widgets\extended\ParentWidget {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        return parent::getData();
    }
}
