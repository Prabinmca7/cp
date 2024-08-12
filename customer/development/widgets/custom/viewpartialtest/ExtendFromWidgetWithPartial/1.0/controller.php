<?php
namespace Custom\Widgets\viewpartialtest;

class ExtendFromWidgetWithPartial extends \Custom\Widgets\viewpartialtest\WidgetsInViewPartials {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        return parent::getData();
    }
}