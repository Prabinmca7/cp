<?php
namespace Custom\Widgets\viewhelpertest;

class HelperExtender extends \Custom\Widgets\viewhelpertest\ViewHelper {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        return parent::getData();
    }
}