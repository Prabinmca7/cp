<?php
namespace RightNow\Widgets;

class BasicTextInput extends \RightNow\Widgets\TextInput {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(parent::getData() === false) {
            return false;
        }

        if($this->data['inputName'] === 'Contact.Login' && !\RightNow\Utils\Config::getConfig(CP_COOKIES_ENABLED)){
            //Session cookies are disabled, so set a cookie when they hit a page with this widget so the login 
            //request can determine if the user has cookies enabled
            \RightNow\Utils\Framework::setTemporaryLoginCookie();
        }

        if ($mask = $this->data['js']['mask']) {
            if (isset($this->data['maskedValue']) && $_POST['validationToken']) {
                $this->data['value'] = $this->data['maskedValue'];
            }
            if ($this->data['attrs']['always_show_mask']) {
                $this->data['mask_hint'] = \RightNow\Utils\Text::getSimpleMaskString($mask);
            }
        }
    }
}
