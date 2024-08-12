<?php
namespace RightNow\Widgets;

class BasicFormSubmit extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $this->data['format'] = array(
            'on_success_url' => $this->data['attrs']['on_success_url'],
            'add_params_to_url' => $this->data['attrs']['add_params_to_url'],
        );
    }
}