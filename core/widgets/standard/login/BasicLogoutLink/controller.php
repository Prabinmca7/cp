<?php
namespace RightNow\Widgets;

class BasicLogoutLink extends \RightNow\Widgets\LogoutLink {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (parent::getData() === false) {
            return false;
        }
        $this->data['redirectUrl'] = $redirectUrl = $this->data['js']['redirectLocation'];
        if ($redirectUrl && \RightNow\Utils\Text::beginsWith($redirectUrl, '/')) {
            $this->data['redirectUrl'] = substr($redirectUrl, 1);
        }
    }
}
