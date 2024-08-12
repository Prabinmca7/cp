<?php
namespace RightNow\Widgets;
require_once CPCORE . 'Libraries/PostRequest.php';

class BasicFormStatusDisplay extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['messages'] = \RightNow\Libraries\PostRequest::getMessages();
    }
}
