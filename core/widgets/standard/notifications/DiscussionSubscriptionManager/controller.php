<?php

namespace RightNow\Widgets;

use RightNow\Utils\Framework;

class DiscussionSubscriptionManager extends \RightNow\Widgets\Multiline {

    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        if (!Framework::isSocialUser()) {
            return false;
        }

        parent::getData();
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

}