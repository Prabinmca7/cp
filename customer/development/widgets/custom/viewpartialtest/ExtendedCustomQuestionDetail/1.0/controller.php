<?php
namespace Custom\Widgets\viewpartialtest;

class ExtendedCustomQuestionDetail extends \Custom\Widgets\viewpartialtest\CustomQuestionDetail {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        return parent::getData();
    }
}