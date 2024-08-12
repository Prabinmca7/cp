<?php
namespace Custom\Widgets\viewpartialtest;

class WidgetsInViewPartials extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'ajax_ajax' => 'yoyoyo',
        ));
    }

    function getData() {
        return parent::getData();
    }

    function yoyoyo () {
        echo $this->render('partial');
    }
}
