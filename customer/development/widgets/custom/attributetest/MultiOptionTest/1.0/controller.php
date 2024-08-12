<?php
namespace Custom\Widgets\attributetest;

class MultiOptionTest extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        var_export($this->data['attrs']['multioption1']);
        var_export($this->data['attrs']['multioption2']);
        var_export($this->data['attrs']['multioption3']);
        var_export($this->data['attrs']['multioption4']);
    }
}
